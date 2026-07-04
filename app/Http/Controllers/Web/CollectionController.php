<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Ai\CollectionRepository;
use App\Http\Controllers\Web\Concerns\ValidatesWebRequests;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Thrower;
use Thinkycz\LaravelCore\Support\Typer;

class CollectionController
{
    use ValidatesWebRequests;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly CollectionRepository $collections,
    ) {}

    /**
     * Show the collection overview page.
     */
    public function show(int $id): RedirectResponse|Response
    {
        $user = User::mustAuth();

        $collection = $this->collections->findOwnedCollection($id, $user);

        if ($collection === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        return Inertia::render('Collections/Show', $this->collections->collectionOverview($collection));
    }

    /**
     * Store a new collection.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = User::mustAuth();

        $validated = $this->validateRequest($request, [
            'title' => 'required|string|min:1|max:120',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:10',
            'subject' => 'nullable|string|max:80',
        ]);

        $title = $validated->assertString('title');

        if (\mb_trim($title) === '') {
            Thrower::default()->message('title', Typer::assertString(\__('collections.title_required')))->throw();
        }

        $collection = Collection::create([
            'user_id' => $user->getKey(),
            'title' => $title,
            'description' => $validated->assertNullableString('description'),
            'icon' => $validated->assertNullableString('icon'),
            'subject' => $validated->assertNullableString('subject'),
        ]);

        Inertia::flash('success', \__('collections.created'));

        return Resolver::resolveRedirector()->to('/collections/' . $collection->getId());
    }

    /**
     * Update an existing collection.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::mustAuth();

        $collection = $this->collections->findOwnedCollection($id, $user);

        if ($collection === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        $validated = $this->validateRequest($request, [
            'title' => 'required|string|min:1|max:120',
            'description' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:10',
            'subject' => 'nullable|string|max:80',
        ]);

        $collection->update([
            'title' => $validated->assertString('title'),
            'description' => $validated->assertNullableString('description'),
            'icon' => $validated->assertNullableString('icon'),
            'subject' => $validated->assertNullableString('subject'),
        ]);

        Inertia::flash('success', \__('collections.updated'));

        return Resolver::resolveRedirector()->back();
    }

    /**
     * Delete a collection.
     */
    public function destroy(int $id): RedirectResponse
    {
        $user = User::mustAuth();

        $collection = $this->collections->findOwnedCollection($id, $user);

        if ($collection === null) {
            return Resolver::resolveRedirector()->to('/app');
        }

        $collection->delete();

        Inertia::flash('success', \__('collections.deleted'));

        return Resolver::resolveRedirector()->to('/app');
    }
}
