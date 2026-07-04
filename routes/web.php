<?php

declare(strict_types=1);

use App\Http\Controllers\Web\Agent\AgentRunCancelController;
use App\Http\Controllers\Web\Agent\AgentRunStartController;
use App\Http\Controllers\Web\Agent\AgentRunStreamController;
use App\Http\Controllers\Web\AppController;
use App\Http\Controllers\Web\Auth\EmailVerificationConfirmController;
use App\Http\Controllers\Web\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\LogoutController;
use App\Http\Controllers\Web\Auth\RegisterController;
use App\Http\Controllers\Web\Auth\ResetPasswordController;
use App\Http\Controllers\Web\Auth\VerifyEmailController;
use App\Http\Controllers\Web\CollectionController;
use App\Http\Controllers\Web\CollectionFlashcardsController;
use App\Http\Controllers\Web\CollectionLessonsController;
use App\Http\Controllers\Web\CollectionProgressController;
use App\Http\Controllers\Web\CollectionQuizzesController;
use App\Http\Controllers\Web\CollectionTermsController;
use App\Http\Controllers\Web\CollectionTutorController;
use App\Http\Controllers\Web\ConversationController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\LessonController;
use App\Http\Controllers\Web\LessonTutorController;
use App\Http\Controllers\Web\Settings\SettingsController;
use App\Http\Middleware\EnsureInertiaUserIsAuthenticated;
use App\Models\User;
use Illuminate\Routing\Router;
use Thinkycz\LaravelCore\Support\Resolver;

Resolver::resolveRouteRegistrar()->get('/', static function () {
    if (User::auth() instanceof User) {
        return Resolver::resolveRedirector()->to('/app');
    }

    return Resolver::resolveRedirector()->to('/login');
});

Resolver::resolveRouteRegistrar()
    ->middleware('guest:users')
    ->group(static function (Router $router): void {
        $router->get('login', [LoginController::class, 'create']);
        $router->post('login', [LoginController::class, 'store']);
        $router->get('register', [RegisterController::class, 'create']);
        $router->post('register', [RegisterController::class, 'store']);
        $router->get('forgot-password', [ForgotPasswordController::class, 'create']);
        $router->post('forgot-password', [ForgotPasswordController::class, 'store']);
        $router->get('reset-password', [ResetPasswordController::class, 'create']);
        $router->post('reset-password', [ResetPasswordController::class, 'store']);
    });

Resolver::resolveRouteRegistrar()->get('email/verify', EmailVerificationConfirmController::class);

Resolver::resolveRouteRegistrar()
    ->middleware(EnsureInertiaUserIsAuthenticated::class)
    ->group(static function (Router $router): void {
        $router->post('logout', LogoutController::class);

        $router->get('dashboard', DashboardController::class);
        $router->get('app', AppController::class);

        $router->get('verify-email', [VerifyEmailController::class, 'create']);
        $router->post('verify-email', [VerifyEmailController::class, 'store']);

        $router->get('settings', [SettingsController::class, 'edit']);
        $router->post('settings/profile', [SettingsController::class, 'updateProfile']);
        $router->post('settings/password', [SettingsController::class, 'updatePassword']);

        $router->get('collections/{id}', [CollectionController::class, 'show']);
        $router->post('collections', [CollectionController::class, 'store']);
        $router->put('collections/{id}', [CollectionController::class, 'update']);
        $router->delete('collections/{id}', [CollectionController::class, 'destroy']);

        $router->get('collections/{id}/lessons', [CollectionLessonsController::class, 'index']);
        $router->post('collections/{id}/lessons', [CollectionLessonsController::class, 'store']);

        $router->get('lessons/{id}', [LessonController::class, 'show']);
        $router->put('lessons/{id}', [LessonController::class, 'update']);
        $router->delete('lessons/{id}', [LessonController::class, 'destroy']);
        $router->post('lessons/{id}/regenerate', [LessonController::class, 'regenerate']);
        $router->post('lessons/{id}/tutor', [LessonTutorController::class, 'store']);

        $router->get('collections/{id}/terms', [CollectionTermsController::class, 'index']);
        $router->put('collections/{id}/terms/{item}', [CollectionTermsController::class, 'update']);

        $router->get('collections/{id}/flashcards', [CollectionFlashcardsController::class, 'index']);
        $router->post('collections/{id}/flashcards/{card}/review', [CollectionFlashcardsController::class, 'review']);

        $router->get('collections/{id}/quizzes', [CollectionQuizzesController::class, 'index']);
        $router->get('collections/{id}/quizzes/{quiz}', [CollectionQuizzesController::class, 'show']);
        $router->post('collections/{id}/quizzes/{quiz}/attempt', [CollectionQuizzesController::class, 'attempt']);

        $router->get('collections/{id}/tutor', [CollectionTutorController::class, 'index']);
        $router->post('collections/{id}/tutor', [CollectionTutorController::class, 'store']);

        $router->get('collections/{id}/progress', CollectionProgressController::class);

        $router->get('conversations/{id}', [ConversationController::class, 'show']);
        $router->delete('conversations/{id}', [ConversationController::class, 'destroy']);

        $router->post('agent/runs', AgentRunStartController::class);
        $router->post('agent/runs/cancel', AgentRunCancelController::class);
        $router->get('agent/runs/stream', AgentRunStreamController::class);
    });
