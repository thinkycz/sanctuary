<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Thinkycz\LaravelCore\Support\Config;

class TutorAgent implements Agent, Conversational
{
    use Promptable;
    use RemembersConversations;

    /**
     * The context description for the tutor (collection/lesson info).
     */
    private string $context = '';

    /**
     * Set the tutor context.
     */
    public function withContext(string $context): static
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        $base = 'You are a friendly, expert AI tutor. You help the user understand concepts, solve problems, and master the subject matter. Be concise, clear, and encouraging. Respond in the user\'s language unless the user asks otherwise.';

        if ($this->context !== '') {
            return $base . "\n\nCurrent context:\n" . $this->context;
        }

        return $base;
    }

    /**
     * Get the model for the agent.
     */
    public function model(): string
    {
        return Config::inject()->assertString('ai.tutor.model');
    }

    /**
     * Get the provider for the agent.
     */
    public function provider(): string
    {
        return Config::inject()->assertString('ai.tutor.provider');
    }
}
