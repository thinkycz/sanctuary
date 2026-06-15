<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Ai\AgentRunService;
use App\Ai\Agents\ChatAgent;
use App\Ai\ConversationRepository;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;

class ConversationController
{
    /**
     * Constructor.
     */
    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly AgentRunService $runs,
    ) {}

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
        $conversationId = $this->conversations->conversationId($conversation);

        return Inertia::render('Dashboard', [
            'conversation' => $this->conversations->dashboardPayload($conversation, $agent->messages()),
            'active_run' => $this->runs->serializeActiveRun($conversationId, $user),
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
}
