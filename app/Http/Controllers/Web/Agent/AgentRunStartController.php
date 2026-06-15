<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Agent;

use App\Ai\AgentRunAlreadyActiveException;
use App\Ai\AgentRunService;
use App\Http\Controllers\Web\Concerns\ThrottlesWebRequests;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Thinkycz\LaravelCore\Http\RequestSignature;
use Thinkycz\LaravelCore\Support\Typer;

class AgentRunStartController
{
    use ThrottlesWebRequests;
    use ValidatesWebRequests;

    /**
     * Start a durable background agent run.
     */
    public function __invoke(Request $request, AgentRunService $runs): JsonResponse
    {
        $clearThrottle = $this->hit($this->limit());

        $user = User::mustAuth();

        $validated = $this->validateRequest($request, [
            'prompt' => 'required|string',
            'conversation_id' => 'nullable|string',
        ]);

        try {
            $result = $runs->start(
                $user,
                $validated->parseString('prompt'),
                $validated->parseNullableString('conversation_id'),
            );
        } catch (AgentRunAlreadyActiveException $exception) {
            $clearThrottle();

            return \response()->json([
                'run_id' => Typer::assertString($exception->run->getId()),
                'conversation_id' => $exception->run->getConversationId(),
                'status' => $exception->run->getStatus(),
            ], 409);
        }

        $clearThrottle();

        return \response()->json([
            'run_id' => Typer::assertString($result['run']->getId()),
            'conversation_id' => Typer::assertString($result['conversation']->getKey()),
            'status' => $result['run']->getStatus(),
        ]);
    }

    /**
     * Throttle limit keyed by the current request signature.
     *
     * 30 runs/minute/user matches the rate cap the previous inline streaming
     * path enforced on `POST /conversations`.
     */
    protected function limit(RequestSignature|null $signature = null): Limit
    {
        $signature = $signature instanceof RequestSignature ? $signature : RequestSignature::default();

        return Limit::perMinutes(1, 30)->by($signature->hash());
    }
}
