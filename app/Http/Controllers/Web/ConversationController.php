<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Ai\Agents\ChatAgent;
use App\Http\Controllers\Web\Concerns\ThrottlesWebRequests;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Models\Conversation;
use Symfony\Component\HttpFoundation\StreamedResponse as SymfonyStreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Thinkycz\LaravelCore\Http\RequestSignature;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;
use Throwable;

class ConversationController
{
    use ThrottlesWebRequests;

    /**
     * Show a specific conversation.
     */
    public function show(string $id): RedirectResponse|Response
    {
        $user = User::mustAuth();

        $conversation = $this->findOwned($id, $user);

        if ($conversation === null) {
            return Resolver::resolveRedirector()->to('/dashboard');
        }

        $agent = ChatAgent::make()->continue($conversation->id, $user);

        $messagesIterable = $agent->messages();
        $messagesArray = \is_array($messagesIterable) ? $messagesIterable : \iterator_to_array($messagesIterable);

        $messages = \collect($messagesArray)->map(fn($message) => [
            'role' => $message->role->value,
            'content' => Typer::assertNullableString($message->content),
        ])->toArray();

        $convTitle = Typer::assertString($conversation->getAttribute('title'));

        return Inertia::render('Dashboard', [
            'conversation' => [
                'id' => $conversation->id,
                'title' => $convTitle,
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * Start a new conversation and post the first message.
     */
    public function store(Request $request): SymfonyStreamedResponse
    {
        $user = User::mustAuth();

        $this->hit($this->limit());

        $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = Typer::assertString($request->input('message'));

        // Create the conversation in the database immediately with a snippet title.
        $conversation = Conversation::create([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'title' => Str::limit($message, 35),
        ]);

        $agent = ChatAgent::make()->continue($conversation->id, $user);
        $stream = $agent->stream($message);

        return new SymfonyStreamedResponse(function () use ($stream, $conversation, $message): void {
            if (!App::runningUnitTests()) {
                \ob_implicit_flush(true);
                while (\ob_get_level() > 0) {
                    \ob_end_flush();
                }
            }

            foreach ($stream as $event) {
                if ($event instanceof \Laravel\Ai\Streaming\Events\StreamEvent) {
                    echo 'data: ' . $event->__toString() . "\n\n";
                }
            }

            // Send the done event immediately so the client can navigate away.
            // Title generation happens afterwards and does not block the client.
            echo 'data: ' . \json_encode([
                'type' => 'done',
                'conversation_id' => $conversation->id,
            ]) . "\n\n";

            if (!App::runningUnitTests()) {
                \flush();
            }

            // Generate a friendly 3-4 word title after the client has been released.
            try {
                $titleResponse = ChatAgent::make()->prompt(
                    "Generate a concise 3-4 word title for a conversation that starts with the following message. Respond with only the title, no quotes or punctuation: '{$message}'",
                );
                $conversation->update([
                    'title' => Str::limit($titleResponse->text, 100),
                ]);
            } catch (Throwable) {
                // Ignore title generation errors; the snippet title remains.
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, must-revalidate',
            'X-Accel-Buffering' => 'no',
            'X-Conversation-ID' => $conversation->id,
        ]);
    }

    /**
     * Append a message to an existing conversation.
     */
    public function storeMessage(Request $request, string $id): SymfonyStreamedResponse
    {
        $user = User::mustAuth();

        $conversation = Conversation::find($id);

        if ($conversation === null) {
            throw new NotFoundHttpException();
        }

        $convUserId = Typer::assertNullableInt($conversation->getAttribute('user_id'));

        if ($user->id !== $convUserId) {
            throw new AccessDeniedHttpException();
        }

        $this->hit($this->limit());

        $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = Typer::assertString($request->input('message'));

        $agent = ChatAgent::make()->continue($conversation->id, $user);
        $stream = $agent->stream($message);

        return new SymfonyStreamedResponse(function () use ($stream, $conversation): void {
            if (!App::runningUnitTests()) {
                \ob_implicit_flush(true);
                while (\ob_get_level() > 0) {
                    \ob_end_flush();
                }
            }

            foreach ($stream as $event) {
                if ($event instanceof \Laravel\Ai\Streaming\Events\StreamEvent) {
                    echo 'data: ' . $event->__toString() . "\n\n";
                }
            }

            echo 'data: ' . \json_encode([
                'type' => 'done',
                'conversation_id' => $conversation->id,
            ]) . "\n\n";
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Delete an existing conversation.
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        $user = User::mustAuth();

        $conversation = $this->findOwned($id, $user);

        if ($conversation === null) {
            return Resolver::resolveRedirector()->to('/dashboard');
        }

        DB::transaction(function () use ($conversation): void {
            DB::table('agent_conversation_messages')
                ->where('conversation_id', $conversation->id)
                ->delete();

            $conversation->delete();
        });

        $referer = $request->header('referer');
        $isDeletingCurrent = false;
        if (\is_string($referer)) {
            $path = \parse_url($referer, \PHP_URL_PATH);
            if (\is_string($path) && $path === "/conversations/{$id}") {
                $isDeletingCurrent = true;
            }
        }

        if ($isDeletingCurrent) {
            return Resolver::resolveRedirector()->to('/dashboard');
        }

        return Resolver::resolveRedirector()->back();
    }

    /**
     * Override throttle limit for conversation endpoints.
     */
    protected function limit(RequestSignature|null $signature = null): Limit
    {
        $signature = $signature instanceof RequestSignature ? $signature : RequestSignature::default();

        return Limit::perMinutes(1, 30)->by($signature->hash());
    }

    /**
     * Find a conversation that belongs to the given user.
     * Returns null if not found or if the conversation belongs to a different user.
     */
    private function findOwned(string $id, User $user): Conversation|null
    {
        $conversation = Conversation::find($id);

        if ($conversation === null) {
            return null;
        }

        $convUserId = Typer::assertNullableInt($conversation->getAttribute('user_id'));

        if ($user->id !== $convUserId) {
            return null;
        }

        return $conversation;
    }
}
