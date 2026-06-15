import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { effectScope, nextTick, type EffectScope } from 'vue';
import {
    useAgentRunBridge,
    type AgentRunBridge,
    type AgentRunBridgeOptions,
} from '@/composables/useAgentRunBridge';
import type { AgentRunSnapshot } from '@/types';

interface BridgeCallLog {
    onRunStarted: ReturnType<typeof vi.fn>;
    onTextDelta: ReturnType<typeof vi.fn>;
    onToolActivity: ReturnType<typeof vi.fn>;
    onTerminal: ReturnType<typeof vi.fn>;
    onError: ReturnType<typeof vi.fn>;
}

function makeSnapshot(
    overrides: Partial<AgentRunSnapshot> = {},
): AgentRunSnapshot {
    return {
        id: 'run-123',
        conversation_id: 'conv-456',
        status: 'queued',
        assistant_content: '',
        last_event_id: 0,
        error: null,
        ...overrides,
    };
}

interface MountedBridge {
    scope: EffectScope;
    bridge: AgentRunBridge;
    calls: BridgeCallLog;
    stop: () => void;
}

/**
 * Run the bridge composable inside an `effectScope` so its reactive state
 * and refs have a host. The vitest environment is `node` (no DOM) so we
 * cannot mount a real Vue component, and the project does not pull in
 * jsdom/happy-dom as a dev dep.
 *
 * `onBeforeUnmount` registered by the composable is a no-op without a
 * component instance; tests use `bridge.cancel()` (which calls abort())
 * to release polling fetches.
 */
function mountBridge(
    options: Partial<AgentRunBridgeOptions> = {},
): MountedBridge {
    const calls: BridgeCallLog = {
        onRunStarted: vi.fn(),
        onTextDelta: vi.fn(),
        onToolActivity: vi.fn(),
        onTerminal: vi.fn(),
        onError: vi.fn(),
    };

    const scope = effectScope();
    let bridge: AgentRunBridge | null = null;

    scope.run(() => {
        bridge = useAgentRunBridge({
            initialRun: null,
            onRunStarted: calls.onRunStarted,
            onTextDelta: calls.onTextDelta,
            onToolActivity: calls.onToolActivity,
            onTerminal: calls.onTerminal,
            onError: calls.onError,
            ...options,
        });
    });

    return {
        scope,
        bridge: bridge as unknown as AgentRunBridge,
        calls,
        stop: () => {
            scope.stop();
        },
    };
}

/**
 * Build a fake SSE response body. The last chunk is always
 * `data: [DONE]\n\n` plus a terminal event so the bridge's poll loop
 * exits cleanly via `onTerminal()` and never retries with an extra fetch.
 *
 * Note: the `Response` constructor requires the body to be a real
 * `ReadableStream` (or one of the other valid `BodyInit` types). A plain
 * object that mimics the stream API silently coerces to an empty body
 * and triggers the bridge's empty-body retry path — so we use a real
 * `ReadableStream` here.
 */
function makeStreamBody(events: string[]): ReadableStream<Uint8Array> {
    const encoder = new TextEncoder();
    const chunks: string[] = [
        ...events.map((e) => `data: ${e}\n\n`),
        'data: [DONE]\n\n',
    ];

    return new ReadableStream<Uint8Array>({
        start(controller) {
            for (const chunk of chunks) {
                controller.enqueue(encoder.encode(chunk));
            }
            controller.close();
        },
    });
}

function sseResponse(events: string[]): Response {
    return new Response(makeStreamBody(events), {
        status: 200,
        headers: { 'Content-Type': 'text/event-stream' },
    });
}

function startResponse(
    runId: string,
    conversationId: string,
    status = 'queued',
): Response {
    return new Response(
        JSON.stringify({
            run_id: runId,
            conversation_id: conversationId,
            status,
        }),
        { status: 200 },
    );
}

const flushMicrotasks = (): Promise<void> =>
    new Promise((resolve) => setTimeout(resolve, 0));

describe('useAgentRunBridge', () => {
    let fetchMock: ReturnType<typeof vi.fn>;
    let warnSpy: ReturnType<typeof vi.spyOn>;

    beforeEach(() => {
        fetchMock = vi.fn();
        vi.stubGlobal('fetch', fetchMock);
        vi.stubGlobal('document', {
            cookie: 'XSRF-TOKEN=test',
        });
        // Silence the expected `onBeforeUnmount outside a component` warning
        // produced by running the composable in an effectScope.
        warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        warnSpy.mockRestore();
    });

    test('start() happy path sets activeRun, fires onRunStarted, and streams text deltas through to the terminal', async () => {
        const mounted = mountBridge();

        fetchMock
            .mockResolvedValueOnce(startResponse('run-1', 'conv-1'))
            .mockResolvedValueOnce(
                sseResponse([
                    '{"id":1,"type":"text_delta","delta":"Hi"}',
                    '{"id":2,"type":"run_completed"}',
                ]),
            );

        const started = await mounted.bridge.start({
            prompt: 'hello',
            conversationId: null,
        });

        expect(started.id).toBe('run-1');
        expect(started.conversation_id).toBe('conv-1');
        expect(mounted.bridge.activeRun.value?.id).toBe('run-1');
        expect(mounted.calls.onRunStarted).toHaveBeenCalledWith(started);

        await flushMicrotasks();
        await nextTick();

        expect(mounted.calls.onTextDelta).toHaveBeenCalledWith('Hi');
        expect(mounted.calls.onTerminal).toHaveBeenCalledWith(
            'completed',
            null,
        );
        expect(mounted.bridge.isPolling.value).toBe(false);

        mounted.stop();
    });

    test('start() with an existing run on the server resumes polling from the existing run', async () => {
        const mounted = mountBridge();

        fetchMock
            .mockResolvedValueOnce(
                startResponse('run-existing', 'conv-1', 'running'),
            )
            .mockResolvedValueOnce(
                sseResponse([
                    '{"id":42,"type":"text_delta","delta":"cont"}',
                    '{"id":43,"type":"run_completed"}',
                ]),
            );

        await mounted.bridge.start({
            prompt: 'second',
            conversationId: 'conv-1',
        });
        await flushMicrotasks();

        const streamCall = fetchMock.mock.calls[1];
        const url = String(streamCall[0]);
        expect(url).toContain('run_id=run-existing');
        expect(url).toContain('after_event_id=0');
        expect(mounted.calls.onTextDelta).toHaveBeenCalledWith('cont');
        expect(mounted.calls.onTerminal).toHaveBeenCalledWith(
            'completed',
            null,
        );

        mounted.stop();
    });

    test('poll() propagates tool_activity tool_name to onToolActivity', async () => {
        const mounted = mountBridge();

        fetchMock
            .mockResolvedValueOnce(startResponse('run-2', 'conv-2'))
            .mockResolvedValueOnce(
                sseResponse([
                    '{"id":1,"type":"tool_activity","stream_type":"tool_call","tool_name":"AskClarifyingQuestionsTool"}',
                    '{"id":2,"type":"run_completed"}',
                ]),
            );

        await mounted.bridge.start({ prompt: 'hi', conversationId: null });
        await flushMicrotasks();

        expect(mounted.calls.onToolActivity).toHaveBeenCalledWith(
            'AskClarifyingQuestionsTool',
        );

        mounted.stop();
    });

    test('cancel() posts to /agent/runs/cancel and flips the local activeRun status to cancelled', async () => {
        const mounted = mountBridge();

        fetchMock
            .mockResolvedValueOnce(startResponse('run-3', 'conv-3'))
            .mockResolvedValueOnce(
                sseResponse([
                    '{"id":1,"type":"text_delta","delta":"partial"}',
                    '{"id":2,"type":"run_completed"}',
                ]),
            )
            .mockResolvedValueOnce(new Response('{}', { status: 200 }));

        await mounted.bridge.start({ prompt: 'hi', conversationId: null });
        await flushMicrotasks();
        // The run is completed, so onTerminal has fired.
        // Now call cancel — it should still POST to /agent/runs/cancel.
        await mounted.bridge.cancel();

        expect(mounted.bridge.activeRun.value?.status).toBe('cancelled');

        const cancelCall = fetchMock.mock.calls[2];
        expect(String(cancelCall[0])).toBe('/agent/runs/cancel');
        const body = JSON.parse(String(cancelCall[1]?.body));
        expect(body).toEqual({ run_id: 'run-3' });

        mounted.stop();
    });

    test('start() propagates HTTP error as a thrown error', async () => {
        const mounted = mountBridge();

        fetchMock.mockResolvedValueOnce(
            new Response('forbidden', { status: 403 }),
        );

        await expect(
            mounted.bridge.start({ prompt: 'hi', conversationId: null }),
        ).rejects.toThrow(/HTTP 403/);

        mounted.stop();
    });

    test('auto-resume: initialRun that is queued triggers polling on mount', async () => {
        fetchMock.mockResolvedValueOnce(
            sseResponse([
                '{"id":10,"type":"text_delta","delta":"resumed"}',
                '{"id":11,"type":"run_completed"}',
            ]),
        );

        const initialRun = makeSnapshot({
            id: 'run-resume',
            status: 'running',
            last_event_id: 7,
        });

        const mounted = mountBridge({ initialRun });

        await flushMicrotasks();
        await nextTick();

        const url = String(fetchMock.mock.calls[0][0]);
        expect(url).toContain('run_id=run-resume');
        expect(url).toContain('after_event_id=7');
        expect(mounted.calls.onTextDelta).toHaveBeenCalledWith('resumed');
        expect(mounted.calls.onTerminal).toHaveBeenCalledWith(
            'completed',
            null,
        );

        mounted.stop();
    });

    test('auto-resume: completed initialRun does NOT trigger polling', async () => {
        const initialRun = makeSnapshot({ status: 'completed' });
        const mounted = mountBridge({ initialRun });

        await flushMicrotasks();

        expect(fetchMock).not.toHaveBeenCalled();

        mounted.stop();
    });
});
