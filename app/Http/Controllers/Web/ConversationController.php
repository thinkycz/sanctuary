<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Ai\Agents\ChatAgent;
use App\Ai\ConversationRepository;
use App\Http\Controllers\Web\Concerns\ThrottlesWebRequests;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Streaming\Events\StreamEvent;
use Symfony\Component\HttpFoundation\StreamedResponse as SymfonyStreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Thinkycz\LaravelCore\Http\RequestSignature;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Typer;
use Throwable;

class ConversationController
{
    use ThrottlesWebRequests;

    /**
     * Constructor.
     */
    public function __construct(private readonly ConversationRepository $conversations) {}

    /**
     * Show a specific conversation.
     */
    public function show(string $id): RedirectResponse|Response
    {
        $user = User::mustAuth();

        $conversation = $this->conversations->findOwned($id, $user);

        if ($conversation === null) {
            return Resolver::resolveRedirector()->to('/dashboard');
        }

        $agent = ChatAgent::make()->continue($this->conversations->conversationId($conversation), $user);

        return Inertia::render('Dashboard', [
            'conversation' => $this->conversations->dashboardPayload($conversation, $agent->messages()),
        ]);
    }

    /**
     * Start a new conversation and post the first message.
     */
    public function store(Request $request): SymfonyStreamedResponse
    {
        $user = User::mustAuth();

        $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $clearThrottle = $this->hit($this->limit());

        $message = Typer::assertString($request->input('message'));

        $conversation = $this->conversations->createForUser($user, $message);
        $conversationId = $this->conversations->conversationId($conversation);

        $agent = ChatAgent::make()->continue($conversationId, $user);
        $stream = $agent->stream($message);

        return new SymfonyStreamedResponse(function () use ($stream, $conversation, $conversationId, $message, $clearThrottle): void {
            $this->prepareStream();

            try {
                foreach ($stream as $event) {
                    if ($event instanceof StreamEvent) {
                        $this->emitStreamData($event->toArray());
                    }
                }
            } catch (Throwable) {
                $this->conversations->deleteIfEmpty($conversation);
                $this->emitStreamError();
                $this->flushStream();

                return;
            }

            $clearThrottle();

            $this->emitStreamDone($conversationId);
            $this->flushStream();

            // Generate a friendly title after the client has been released.
            $this->generateTitle($conversation, $message);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, must-revalidate',
            'X-Accel-Buffering' => 'no',
            'X-Conversation-ID' => $conversationId,
        ]);
    }

    /**
     * Append a message to an existing conversation.
     */
    public function storeMessage(Request $request, string $id): SymfonyStreamedResponse
    {
        $user = User::mustAuth();

        $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $conversation = $this->conversations->findOwned($id, $user);

        if ($conversation === null) {
            throw new NotFoundHttpException();
        }

        $clearThrottle = $this->hit($this->limit());

        $message = Typer::assertString($request->input('message'));

        $conversationId = $this->conversations->conversationId($conversation);
        $agent = ChatAgent::make()->continue($conversationId, $user);
        $stream = $agent->stream($message);

        return new SymfonyStreamedResponse(function () use ($stream, $conversationId, $clearThrottle): void {
            $this->prepareStream();

            try {
                foreach ($stream as $event) {
                    if ($event instanceof StreamEvent) {
                        $this->emitStreamData($event->toArray());
                    }
                }
            } catch (Throwable) {
                $this->emitStreamError();
                $this->flushStream();

                return;
            }

            $clearThrottle();

            $this->emitStreamDone($conversationId);
            $this->flushStream();
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

        $conversation = $this->conversations->findOwned($id, $user);

        if ($conversation === null) {
            return Resolver::resolveRedirector()->to('/dashboard');
        }

        $this->conversations->delete($conversation);

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
     * Prepare the streamed response output buffers.
     */
    private function prepareStream(): void
    {
        if (!App::runningUnitTests()) {
            \ob_implicit_flush(true);
            while (\ob_get_level() > 0) {
                \ob_end_flush();
            }
        }
    }

    /**
     * Emit a successful stream completion event.
     */
    private function emitStreamDone(string $conversationId): void
    {
        $this->emitStreamData([
            'type' => 'done',
            'conversation_id' => $conversationId,
        ]);
    }

    /**
     * Emit a stream error event.
     */
    private function emitStreamError(): void
    {
        $this->emitStreamData([
            'type' => 'error',
            'message' => 'Failed to generate response. Please try again.',
        ]);
    }

    /**
     * Emit an SSE data event.
     *
     * @param array<string, mixed> $payload
     */
    private function emitStreamData(array $payload): void
    {
        echo 'data: ' . Typer::assertString(\json_encode($payload)) . "\n\n";
    }

    /**
     * Flush streamed output when running outside unit tests.
     */
    private function flushStream(): void
    {
        if (!App::runningUnitTests()) {
            \flush();
        }
    }

    /**
     * Generate a concise AI title for the conversation.
     *
     * The user's opening message is bounded before interpolation to limit
     * the prompt size and blunt prompt-injection. The 35-character title
     * limit matches the snippet fallback in ConversationRepository.
     * Failures are logged at debug level so a misbehaving provider stays
     * diagnosable while the snippet title remains intact.
     */
    private function generateTitle(Conversation $conversation, string $message): void
    {
        try {
            $bounded = Str::limit($message, 200);

            $titleResponse = ChatAgent::make()->prompt(
                "Generate a concise 3-4 word title for a conversation that starts with the following message. Respond with only the title, no quotes or punctuation: '{$bounded}'",
            );

            $conversation->update([
                'title' => Str::limit($titleResponse->text, 35),
            ]);
        } catch (Throwable $exception) {
            Resolver::resolveLogManager()->debug('AI title generation failed', [
                'exception' => $exception,
            ]);
        }
    }
}
