<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FlashcardDifficultyEnum;
use App\Enums\LessonDifficultyEnum;
use App\Enums\LessonProgressStatusEnum;
use App\Enums\LessonStatusEnum;
use App\Enums\QuizStatusEnum;
use App\Enums\TermDifficultyEnum;
use App\Enums\TutorMessageRoleEnum;
use App\Models\Collection;
use App\Models\Flashcard;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\Term;
use App\Models\TutorMessage;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Thinkycz\LaravelCore\Support\Config;

class DemoSeeder extends Seeder
{
    /**
     * Seed the application's database with demo data.
     */
    public function run(): void
    {
        if (Config::inject()->appEnvIs(['staging', 'production'])) {
            return;
        }

        $user = $this->ensureUser();

        if (Collection::query()->where('user_id', $user->getKey())->exists()) {
            return;
        }

        DB::transaction(function () use ($user): void {
            $this->seedCalculusBasics($user);
            $this->seedPythonProgramming($user);
            $this->seedWorldHistory($user);
        });
    }

    /**
     * Ensure the demo user exists.
     */
    private function ensureUser(): User
    {
        $user = User::query()->where('email', 'test@test.com')->first();

        if ($user instanceof User) {
            return $user;
        }

        return UserFactory::new()->password()->createOne(['email' => 'test@test.com']);
    }

    /**
     * Seed a Calculus Basics collection.
     */
    private function seedCalculusBasics(User $user): void
    {
        $collection = Collection::create([
            'user_id' => $user->getKey(),
            'title' => 'Calculus Basics',
            'description' => 'Introduction to limits, derivatives, and integrals',
            'icon' => '🧮',
            'subject' => 'Mathematics',
        ]);

        $lesson1 = $this->createReadyLesson($collection, $user, [
            'title' => 'Introduction to Limits',
            'source_text' => 'The limit of a function f(x) as x approaches a is the value that f(x) gets closer to as x gets closer to a. We write lim(x->a) f(x) = L.',
            'difficulty' => LessonDifficultyEnum::Beginner->value,
            'quick_summary' => [
                'A limit describes the value a function approaches',
                'Limits are written as lim(x->a) f(x) = L',
                'Limits exist even when the function is undefined at the point',
            ],
            'simple_explanation' => 'A limit tells us what value a function is approaching as the input gets closer to some value. It is one of the foundational concepts in calculus.',
            'deep_explanation' => [
                'key_concepts' => [
                    [
                        'title' => 'Definition of a Limit',
                        'explanation' => 'We say lim(x->a) f(x) = L if f(x) can be made arbitrarily close to L by choosing x sufficiently close to a (but not equal to a).',
                        'examples' => ['lim(x->2) (3x) = 6', 'lim(x->0) (sin(x)/x) = 1'],
                    ],
                    [
                        'title' => 'One-Sided Limits',
                        'explanation' => 'The left-hand limit lim(x->a-) f(x) approaches from values less than a, while the right-hand limit lim(x->a+) f(x) approaches from values greater than a.',
                        'examples' => ['lim(x->0+) (1/x) = +infinity', 'lim(x->0-) (1/x) = -infinity'],
                    ],
                ],
                'worked_examples' => [
                    [
                        'problem' => 'Find lim(x->3) (x^2 + 2x)',
                        'solution' => '16',
                        'steps' => ['Substitute x = 3: 3^2 + 2(3)', 'Compute: 9 + 6 = 15', 'Wait, recheck: 9 + 6 = 15. Answer is 15.'],
                    ],
                ],
                'notes' => ['A limit can exist even if the function is undefined at that point.'],
                'common_mistakes' => ['Confusing the limit value with the function value at the point', 'Forgetting to check one-sided limits for piecewise functions'],
            ],
        ]);

        $this->createTerms($collection, $user, $lesson1, [
            ['term' => 'limit', 'definition' => 'The value a function approaches as the input approaches some value', 'category' => 'concept', 'example' => 'lim(x->2) f(x) = 4'],
            ['term' => 'one-sided limit', 'definition' => 'A limit approached from only one direction (left or right)', 'category' => 'concept', 'example' => 'lim(x->0+) 1/x = +inf'],
            ['term' => 'continuous', 'definition' => 'A function is continuous at a point if its limit equals its value there', 'category' => 'property', 'example' => 'f(x) = x^2 is continuous everywhere'],
            ['term' => 'indeterminate form', 'definition' => 'An expression like 0/0 or inf/inf that requires further analysis', 'category' => 'concept', 'example' => 'lim(x->0) sin(x)/x is 0/0'],
        ]);

        $this->createFlashcards($collection, $user, $lesson1, [
            ['front' => 'limit', 'back' => 'value a function approaches', 'example' => 'lim(x->a) f(x) = L'],
            ['front' => 'continuous', 'back' => 'limit equals function value', 'example' => 'f(x) = x is continuous'],
            ['front' => 'one-sided limit', 'back' => 'limit from one direction', 'example' => 'lim(x->a+) f(x)'],
            ['front' => 'indeterminate form', 'back' => '0/0 or inf/inf', 'example' => 'sin(x)/x as x->0'],
        ]);

        $this->createQuiz($collection, $user, $lesson1, 'Limits Fundamentals', [
            [
                'type' => 'multiple_choice',
                'question' => 'What does lim(x->a) f(x) = L mean?',
                'options' => ['f(a) = L', 'f(x) approaches L as x approaches a', 'f(x) = L for all x', 'f is undefined at a'],
                'correct_answer' => 'f(x) approaches L as x approaches a',
                'explanation' => 'A limit describes the value the function approaches, not necessarily the value at the point.',
            ],
            [
                'type' => 'multiple_choice',
                'question' => 'Evaluate: lim(x->2) (x + 3)',
                'options' => ['2', '3', '5', 'undefined'],
                'correct_answer' => '5',
                'explanation' => 'Substitute x = 2: 2 + 3 = 5.',
            ],
            [
                'type' => 'fill_blank',
                'question' => 'A function is ___ at a point if its limit equals its value there.',
                'options' => null,
                'correct_answer' => 'continuous',
                'explanation' => 'Continuity requires lim(x->a) f(x) = f(a).',
            ],
        ]);

        $this->createTutorMessages($collection, $user, $lesson1, [
            ['role' => TutorMessageRoleEnum::User->value, 'content' => 'Can you explain why limits matter?'],
            ['role' => TutorMessageRoleEnum::Assistant->value, 'content' => 'Great question! Limits are the foundation of calculus because they let us define derivatives (instantaneous rate of change) and integrals (accumulated area). Without limits, we cannot rigorously handle "infinitely small" changes or "infinitely many" pieces.'],
        ]);

        // A generating lesson
        Lesson::create([
            'user_id' => $user->getKey(),
            'collection_id' => $collection->getId(),
            'title' => 'Generating: Derivatives',
            'source_type' => 'text',
            'source_text' => 'The derivative of f(x) is the rate of change of f with respect to x.',
            'difficulty' => LessonDifficultyEnum::Intermediate->value,
            'status' => LessonStatusEnum::Generating->value,
            'progress_status' => LessonProgressStatusEnum::New->value,
        ]);

        // A failed lesson
        Lesson::create([
            'user_id' => $user->getKey(),
            'collection_id' => $collection->getId(),
            'title' => 'Failed: Integration by Parts',
            'source_type' => 'text',
            'source_text' => 'Integration by parts: integral of u dv = uv - integral of v du.',
            'difficulty' => LessonDifficultyEnum::Advanced->value,
            'status' => LessonStatusEnum::Failed->value,
            'progress_status' => LessonProgressStatusEnum::New->value,
            'error_message' => 'The AI service could not generate the lesson. Please try again.',
        ]);
    }

    /**
     * Seed a Python Programming collection.
     */
    private function seedPythonProgramming(User $user): void
    {
        $collection = Collection::create([
            'user_id' => $user->getKey(),
            'title' => 'Python Programming',
            'description' => 'Core Python concepts and syntax',
            'icon' => '💻',
            'subject' => 'Computer Science',
        ]);

        $lesson = $this->createReadyLesson($collection, $user, [
            'title' => 'Lists and Dictionaries',
            'source_text' => 'Lists hold ordered collections of items. Dictionaries hold key-value pairs. my_list = [1, 2, 3]; my_dict = {"name": "Alice", "age": 30}.',
            'difficulty' => LessonDifficultyEnum::Beginner->value,
            'quick_summary' => [
                'Lists are ordered, mutable sequences',
                'Dictionaries store key-value pairs',
                'Both support iteration and common methods',
            ],
            'simple_explanation' => 'Lists and dictionaries are two of the most used data structures in Python. Lists hold ordered items accessed by index, while dictionaries hold items accessed by unique keys.',
            'deep_explanation' => [
                'key_concepts' => [
                    [
                        'title' => 'Lists',
                        'explanation' => 'A list is an ordered, mutable collection. Items are accessed by zero-based index.',
                        'examples' => ['fruits = ["apple", "banana", "cherry"]', 'fruits.append("date")', 'fruits[0] returns "apple"'],
                    ],
                    [
                        'title' => 'Dictionaries',
                        'explanation' => 'A dictionary maps unique keys to values. Keys must be hashable (e.g. strings, numbers).',
                        'examples' => ['person = {"name": "Alice", "age": 30}', 'person["name"] returns "Alice"', 'person["city"] = "Prague"'],
                    ],
                ],
                'worked_examples' => [
                    [
                        'problem' => 'Count word frequencies in a list',
                        'solution' => 'Use a dictionary to accumulate counts',
                        'steps' => ['Create empty dict', 'Loop over words', 'For each word, increment its count'],
                    ],
                ],
                'notes' => ['Dictionaries preserve insertion order in Python 3.7+.'],
                'common_mistakes' => ['Using a list as a dict key (lists are not hashable)', 'Modifying a list while iterating over it'],
            ],
        ]);

        $this->createTerms($collection, $user, $lesson, [
            ['term' => 'list', 'definition' => 'An ordered, mutable sequence of items', 'category' => 'data structure', 'example' => 'nums = [1, 2, 3]'],
            ['term' => 'dictionary', 'definition' => 'A collection of key-value pairs accessed by key', 'category' => 'data structure', 'example' => 'd = {"k": "v"}'],
            ['term' => 'mutable', 'definition' => 'An object whose value can change after creation', 'category' => 'property', 'example' => 'Lists are mutable; tuples are not'],
            ['term' => 'hashable', 'definition' => 'An object with a hash value that never changes during its lifetime', 'category' => 'property', 'example' => 'Strings and ints are hashable'],
        ]);

        $this->createFlashcards($collection, $user, $lesson, [
            ['front' => 'list', 'back' => 'ordered, mutable sequence', 'example' => '[1, 2, 3]'],
            ['front' => 'dictionary', 'back' => 'key-value pairs', 'example' => '{"a": 1}'],
            ['front' => 'mutable', 'back' => 'can change after creation', 'example' => 'lists are mutable'],
            ['front' => 'hashable', 'back' => 'has stable hash value', 'example' => 'strings are hashable'],
        ]);

        $this->createQuiz($collection, $user, $lesson, 'Python Data Structures', [
            [
                'type' => 'multiple_choice',
                'question' => 'Which data structure stores key-value pairs?',
                'options' => ['list', 'dictionary', 'tuple', 'set'],
                'correct_answer' => 'dictionary',
                'explanation' => 'Dictionaries map keys to values.',
            ],
            [
                'type' => 'multiple_choice',
                'question' => 'What does my_list[0] return for my_list = [10, 20, 30]?',
                'options' => ['10', '20', '0', 'Error'],
                'correct_answer' => '10',
                'explanation' => 'List indexing is zero-based, so index 0 is the first element.',
            ],
        ]);
    }

    /**
     * Seed a World History collection.
     */
    private function seedWorldHistory(User $user): void
    {
        $collection = Collection::create([
            'user_id' => $user->getKey(),
            'title' => 'World History',
            'description' => 'Key events and figures in world history',
            'icon' => '🌍',
            'subject' => 'History',
        ]);

        $lesson = $this->createReadyLesson($collection, $user, [
            'title' => 'The Industrial Revolution',
            'source_text' => 'The Industrial Revolution began in Britain in the late 18th century, transforming agrarian societies into industrial ones through mechanization, factory systems, and urbanization.',
            'difficulty' => LessonDifficultyEnum::Intermediate->value,
            'quick_summary' => [
                'Began in Britain in the late 1700s',
                'Marked shift from manual labor to machine production',
                'Drove urbanization and social change',
            ],
            'simple_explanation' => 'The Industrial Revolution was a period of major industrialization that transformed how goods were produced, moving from hand production methods to machines.',
            'deep_explanation' => [
                'key_concepts' => [
                    [
                        'title' => 'Mechanization',
                        'explanation' => 'The replacement of manual labor with machines, dramatically increasing productivity.',
                        'examples' => ['The steam engine (James Watt)', 'The spinning jenny', 'The power loom'],
                    ],
                    [
                        'title' => 'Urbanization',
                        'explanation' => 'People moved from rural areas to cities to work in factories, reshaping society.',
                        'examples' => ['Manchester grew from 75,000 to 300,000 in 50 years', 'London became the largest city in the world'],
                    ],
                ],
                'worked_examples' => [],
                'notes' => ['The revolution spread from Britain to Europe and North America in the 19th century.'],
                'common_mistakes' => ['Treating it as a single event rather than a decades-long process', 'Ignoring the social and environmental costs'],
            ],
        ]);

        $this->createTerms($collection, $user, $lesson, [
            ['term' => 'Industrial Revolution', 'definition' => 'Period of major industrialization starting in late 18th-century Britain', 'category' => 'event', 'example' => 'Began around 1760'],
            ['term' => 'mechanization', 'definition' => 'Replacing manual labor with machines', 'category' => 'concept', 'example' => 'Steam-powered looms'],
            ['term' => 'urbanization', 'definition' => 'The growth of cities as people moved from rural areas', 'category' => 'concept', 'example' => 'Rise of factory towns'],
            ['term' => 'factory system', 'definition' => 'A method of production using centralized workplaces with machinery', 'category' => 'concept', 'example' => 'Textile mills in Manchester'],
        ]);

        $this->createFlashcards($collection, $user, $lesson, [
            ['front' => 'Industrial Revolution', 'back' => 'major industrialization from late 1700s', 'example' => 'started in Britain'],
            ['front' => 'mechanization', 'back' => 'machines replace manual labor', 'example' => 'steam engine'],
            ['front' => 'urbanization', 'back' => 'growth of cities', 'example' => 'factory towns grew'],
            ['front' => 'factory system', 'back' => 'centralized machine production', 'example' => 'textile mills'],
        ]);
    }

    /**
     * Create a ready lesson with full content.
     *
     * @param array<string, mixed> $overrides
     */
    private function createReadyLesson(Collection $collection, User $user, array $overrides): Lesson
    {
        return Lesson::create([
            'user_id' => $user->getKey(),
            'collection_id' => $collection->getId(),
            'source_type' => 'text',
            'status' => LessonStatusEnum::Ready->value,
            'progress_status' => LessonProgressStatusEnum::Learning->value,
            'completed_at' => Carbon::now(),
            ...$overrides,
        ]);
    }

    /**
     * Create terms for a lesson.
     *
     * @param array<int, array{term: string, definition: string, category: string|null, example: string|null}> $items
     */
    private function createTerms(Collection $collection, User $user, Lesson $lesson, array $items): void
    {
        foreach ($items as $item) {
            Term::create([
                'user_id' => $user->getKey(),
                'collection_id' => $collection->getId(),
                'lesson_id' => $lesson->getId(),
                'term' => $item['term'],
                'definition' => $item['definition'],
                'category' => $item['category'],
                'example' => $item['example'],
                'difficulty' => TermDifficultyEnum::Learning->value,
            ]);
        }
    }

    /**
     * Create flashcards for a lesson.
     *
     * @param array<int, array{front: string, back: string, example: string|null}> $cards
     */
    private function createFlashcards(Collection $collection, User $user, Lesson $lesson, array $cards): void
    {
        foreach ($cards as $card) {
            Flashcard::create([
                'user_id' => $user->getKey(),
                'collection_id' => $collection->getId(),
                'lesson_id' => $lesson->getId(),
                'front' => $card['front'],
                'back' => $card['back'],
                'example' => $card['example'],
                'difficulty' => FlashcardDifficultyEnum::Hard->value,
                'review_count' => 1,
                'due_at' => Carbon::now()->addHours(4),
                'last_reviewed_at' => Carbon::now()->subDay(),
            ]);
        }
    }

    /**
     * Create a quiz with questions.
     *
     * @param array<int, array{type: string, question: string, options: array<int, string>|null, correct_answer: string, explanation: string|null}> $questions
     */
    private function createQuiz(Collection $collection, User $user, Lesson $lesson, string $title, array $questions): void
    {
        $quiz = Quiz::create([
            'user_id' => $user->getKey(),
            'collection_id' => $collection->getId(),
            'lesson_id' => $lesson->getId(),
            'title' => $title,
            'status' => QuizStatusEnum::Completed->value,
            'score' => 67,
            'total_questions' => \count($questions),
            'completed_at' => Carbon::now(),
        ]);

        foreach ($questions as $index => $question) {
            QuizQuestion::create([
                'quiz_id' => $quiz->getId(),
                'type' => $question['type'],
                'question' => $question['question'],
                'options' => $question['options'],
                'correct_answer' => $question['correct_answer'],
                'explanation' => $question['explanation'],
                'order' => $index,
            ]);
        }

        QuizAttempt::create([
            'user_id' => $user->getKey(),
            'quiz_id' => $quiz->getId(),
            'score' => 67,
            'answers' => [],
            'mistakes' => [],
            'completed_at' => Carbon::now(),
        ]);
    }

    /**
     * Create tutor messages for a lesson.
     *
     * @param array<int, array{role: string, content: string}> $messages
     */
    private function createTutorMessages(Collection $collection, User $user, Lesson $lesson, array $messages): void
    {
        foreach ($messages as $message) {
            TutorMessage::create([
                'user_id' => $user->getKey(),
                'collection_id' => $collection->getId(),
                'lesson_id' => $lesson->getId(),
                'role' => $message['role'],
                'content' => $message['content'],
            ]);
        }
    }
}
