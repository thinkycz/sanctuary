import { onBeforeUnmount, ref, type Ref } from 'vue';
import type { AgentRunSnapshot } from '@/types';
import { parseTextDeltaSseChunk } from '@/lib/sse';

export type RunTerminalStatus = 'completed' | 'failed' | 'cancelled';

export interface AgentRunBridgeOptions {
    initialRun: AgentRunSnapshot | null;
    onTextDelta: (delta: string) => void;
    onToolActivity: (tool: string | null) => void;
    onTerminal: (status: RunTerminalStatus, error: string | null) => void;
    onRunStarted: (run: AgentRunSnapshot) => void;
    onError: (message: string) => void;
}

export interface AgentRunBridge {
    activeRun: Ref<AgentRunSnapshot | null>;
    isPolling: Ref<boolean>;
    start: (params: {
        prompt: string;
        conversationId: string | null;
    }) => Promise<AgentRunSnapshot>;
    cancel: () => Promise<void>;
}

const MAX_RETRIES = 5;
const BASE_BACKOFF_MS = 1000;
const MAX_BACKOFF_MS = 8000;

function getXsrfToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }

    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));

    return match ? decodeURIComponent(match.split('=')[1] ?? '') : '';
}

function sleep(ms: number, signal: AbortSignal): Promise<void> {
    return new Promise((resolve, reject) => {
        if (signal.aborted) {
            reject(new DOMException('Aborted', 'AbortError'));

            return;
        }

        const timer = setTimeout(() => {
            signal.removeEventListener('abort', onAbort);
            resolve();
        }, ms);
        const onAbort = (): void => {
            clearTimeout(timer);
            reject(new DOMException('Aborted', 'AbortError'));
        };

        signal.addEventListener('abort', onAbort, { once: true });
    });
}

export function useAgentRunBridge(
    options: AgentRunBridgeOptions,
): AgentRunBridge {
    const activeRun = ref<AgentRunSnapshot | null>(options.initialRun);
    const isPolling = ref(false);
    let currentAbort: AbortController | null = null;

    if (
        options.initialRun !== null &&
        (options.initialRun.status === 'queued' ||
            options.initialRun.status === 'running')
    ) {
        void runPollLoop(
            options.initialRun.id,
            options.initialRun.last_event_id ?? 0,
        );
    }

    async function runPollLoop(
        runId: string,
        startAfterEventId: number,
    ): Promise<void> {
        isPolling.value = true;
        let lastEventId = startAfterEventId;
        let retries = 0;
        let backoff = BASE_BACKOFF_MS;
        let lastReportedTool: string | null = null;
        const abort = currentAbort ?? new AbortController();
        currentAbort = abort;

        try {
            while (!abort.signal.aborted) {
                const url = `/agent/runs/stream?run_id=${encodeURIComponent(runId)}&after_event_id=${lastEventId}`;
                let response: Response;

                try {
                    response = await fetch(url, {
                        signal: abort.signal,
                        headers: {
                            Accept: 'text/event-stream',
                            'X-XSRF-TOKEN': getXsrfToken(),
                        },
                    });
                } catch (err) {
                    if (
                        err instanceof DOMException &&
                        err.name === 'AbortError'
                    ) {
                        return;
                    }

                    if (retries >= MAX_RETRIES) {
                        options.onError('Lost connection to the agent run.');

                        return;
                    }

                    try {
                        await sleep(backoff, abort.signal);
                    } catch {
                        return;
                    }

                    retries++;
                    backoff = Math.min(backoff * 2, MAX_BACKOFF_MS);
                    continue;
                }

                if (!response.ok || !response.body) {
                    if (response.status === 404) {
                        options.onError(
                            'The agent run is no longer available.',
                        );

                        return;
                    }

                    if (retries >= MAX_RETRIES) {
                        options.onError(
                            `Stream error: HTTP ${String(response.status)}`,
                        );

                        return;
                    }

                    try {
                        await sleep(backoff, abort.signal);
                    } catch {
                        return;
                    }

                    retries++;
                    backoff = Math.min(backoff * 2, MAX_BACKOFF_MS);
                    continue;
                }

                retries = 0;
                backoff = BASE_BACKOFF_MS;

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let sseBuffer = '';

                try {
                    while (!abort.signal.aborted) {
                        const { done, value } = await reader.read();

                        if (done) {
                            break;
                        }

                        const chunk = decoder.decode(value, { stream: true });
                        const parsed = parseTextDeltaSseChunk(chunk, sseBuffer);
                        sseBuffer = parsed.buffer;

                        for (const delta of parsed.deltas) {
                            options.onTextDelta(delta);
                        }

                        if (parsed.lastEventId !== null) {
                            lastEventId = parsed.lastEventId;
                        }

                        if (
                            parsed.toolName !== null &&
                            parsed.toolName !== lastReportedTool
                        ) {
                            lastReportedTool = parsed.toolName;
                            options.onToolActivity(parsed.toolName);
                        }

                        if (parsed.terminalType !== null) {
                            let normalized: RunTerminalStatus;
                            if (parsed.terminalType === 'run_completed') {
                                normalized = 'completed';
                            } else if (parsed.terminalType === 'run_failed') {
                                normalized = 'failed';
                            } else {
                                normalized = 'cancelled';
                            }
                            const base: AgentRunSnapshot = activeRun.value ?? {
                                id: runId,
                                conversation_id: '',
                                status: normalized,
                                assistant_content: '',
                                last_event_id: lastEventId,
                                error: null,
                            };
                            const finalRun: AgentRunSnapshot = {
                                ...base,
                                id: runId,
                                status: normalized,
                                error: parsed.error,
                                last_event_id: lastEventId,
                            };
                            activeRun.value = finalRun;
                            options.onTerminal(normalized, parsed.error);

                            return;
                        }

                        if (parsed.done) {
                            break;
                        }
                    }
                } catch (err) {
                    if (
                        err instanceof DOMException &&
                        err.name === 'AbortError'
                    ) {
                        return;
                    }

                    if (retries >= MAX_RETRIES) {
                        options.onError('Lost connection to the agent run.');

                        return;
                    }

                    try {
                        await sleep(backoff, abort.signal);
                    } catch {
                        return;
                    }

                    retries++;
                    backoff = Math.min(backoff * 2, MAX_BACKOFF_MS);
                }
            }
        } finally {
            isPolling.value = false;
        }
    }

    async function start(params: {
        prompt: string;
        conversationId: string | null;
    }): Promise<AgentRunSnapshot> {
        currentAbort?.abort();
        const abort = new AbortController();
        currentAbort = abort;

        const response = await fetch('/agent/runs', {
            method: 'POST',
            signal: abort.signal,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': getXsrfToken(),
            },
            body: JSON.stringify({
                prompt: params.prompt,
                conversation_id: params.conversationId,
            }),
        });

        if (!response.ok) {
            const text = await response.text().catch(() => '');

            throw new Error(
                `Failed to start agent run: HTTP ${String(response.status)} ${text}`,
            );
        }

        const data = (await response.json()) as {
            run_id: string;
            conversation_id: string;
            status: string;
        };

        const run: AgentRunSnapshot = {
            id: data.run_id,
            conversation_id: data.conversation_id,
            status:
                (data.status as AgentRunSnapshot['status'] | undefined) ??
                'queued',
            assistant_content: '',
            last_event_id: 0,
            error: null,
        };

        activeRun.value = run;
        options.onRunStarted(run);
        void runPollLoop(run.id, 0);

        return run;
    }

    async function cancel(): Promise<void> {
        const current = activeRun.value;

        if (current === null) {
            return;
        }

        activeRun.value = { ...current, status: 'cancelled' };
        currentAbort?.abort();
        currentAbort = null;

        try {
            await fetch('/agent/runs/cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
                body: JSON.stringify({ run_id: current.id }),
            });
        } catch {
            // Best-effort: the server will mark the run as cancelled on the
            // next poll cycle, and our local state has already been flipped.
        }
    }

    onBeforeUnmount(() => {
        currentAbort?.abort();
        currentAbort = null;
    });

    return {
        activeRun,
        isPolling,
        start,
        cancel,
    };
}
