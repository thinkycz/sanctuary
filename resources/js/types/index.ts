export interface AuthUser {
    id: number;
    email: string;
    locale: string;
    email_verified_at: string | null;
}

export interface AppMeta {
    name: string;
    locale: string;
    locales: string[];
}

export interface FlashProps {
    success: string | null;
    error: string | null;
}

export interface ConversationItem {
    id: string;
    title: string;
    updated_at: string;
}

export interface ChatMessage {
    role: string;
    content: string | null;
}

export interface ChatConversation {
    id: string;
    title: string;
    messages: ChatMessage[];
}

export interface AgentClarification {
    question: string;
    options: string[];
    recommended_option: string | null;
}

export interface AgentRunSnapshot {
    id: string;
    conversation_id: string;
    status: 'queued' | 'running' | 'completed' | 'failed' | 'cancelled';
    assistant_content: string;
    last_event_id: number | null;
    error: string | null;
}

// ── Learning Domain Types ───────────────────────────────────────────

export interface CollectionSidebarItem {
    id: number;
    title: string;
    icon: string | null;
    updated_at: string | null;
}

export interface Collection {
    id: number;
    title: string;
    description: string | null;
    icon: string | null;
    subject: string | null;
    created_at: string;
    updated_at: string;
}

export type LessonSourceType = 'text';
export type LessonDifficulty = 'beginner' | 'intermediate' | 'advanced';
export type LessonStatus = 'pending' | 'generating' | 'ready' | 'failed';
export type LessonProgressStatus = 'new' | 'learning' | 'mastered';

export interface LessonSummary {
    id: number;
    collection_id: number;
    title: string;
    source_type: LessonSourceType;
    difficulty: LessonDifficulty;
    status: LessonStatus;
    progress_status: LessonProgressStatus;
    error_message: string | null;
    completed_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface KeyConcept {
    title: string;
    explanation: string;
    examples: string[];
}

export interface WorkedExample {
    problem: string;
    solution: string;
    steps: string[];
}

export interface DeepExplanation {
    key_concepts?: KeyConcept[];
    worked_examples?: WorkedExample[];
    notes?: string[];
    common_mistakes?: string[];
    [key: string]: unknown;
}

export interface LessonDetail extends LessonSummary {
    source_text: string | null;
    quick_summary: string[] | null;
    simple_explanation: string | null;
    deep_explanation: DeepExplanation | null;
    ai_raw_response: Record<string, unknown> | null;
}

export type TermDifficulty = 'unknown' | 'learning' | 'mastered';

export interface Term {
    id: number;
    collection_id: number;
    lesson_id: number | null;
    term: string;
    definition: string;
    category: string | null;
    example: string | null;
    difficulty: TermDifficulty;
    last_reviewed_at: string | null;
    created_at: string;
}

export type FlashcardDifficulty = 'again' | 'hard' | 'easy';

export interface Flashcard {
    id: number;
    collection_id: number;
    lesson_id: number | null;
    term_id: number | null;
    front: string;
    back: string;
    example: string | null;
    difficulty: FlashcardDifficulty;
    review_count: number;
    due_at: string | null;
    last_reviewed_at: string | null;
    created_at: string;
}

export type QuizStatus = 'not_started' | 'in_progress' | 'completed';

export interface QuizSummary {
    id: number;
    collection_id: number;
    lesson_id: number | null;
    lesson_title: string | null;
    title: string;
    status: QuizStatus;
    score: number | null;
    total_questions: number;
    completed_at: string | null;
    created_at: string;
}

export type QuizQuestionType = 'multiple_choice' | 'fill_blank';

export interface QuizQuestion {
    id: number;
    quiz_id: number;
    type: QuizQuestionType;
    question: string;
    options: string[] | null;
    correct_answer: string;
    explanation: string | null;
    order: number;
}

export interface QuizAttempt {
    id: number;
    quiz_id: number;
    score: number;
    answers: Record<string, unknown>;
    mistakes: unknown[] | null;
    completed_at: string | null;
    created_at: string;
}

export type TutorMessageRole = 'user' | 'assistant';

export interface TutorMessage {
    id: number;
    collection_id: number;
    lesson_id: number | null;
    role: TutorMessageRole;
    content: string;
    created_at: string;
}

export interface CollectionOverviewStats {
    lessons_ready: number;
    terms_learned: number;
    flashcards_due: number;
    average_quiz_score: number | null;
}

export interface CollectionProgressStats {
    lessons_ready: number;
    lessons_mastered: number;
    terms_learned: number;
    terms_mastered: number;
    flashcards_reviewed: number;
    average_quiz_score: number | null;
}

export interface SharedProps {
    [key: string]: unknown;

    app: AppMeta;
    auth: {
        user: AuthUser | null;
    };
    collections: CollectionSidebarItem[];
    conversations?: ConversationItem[];
    flash: FlashProps;
    errors: Record<string, string>;
}
