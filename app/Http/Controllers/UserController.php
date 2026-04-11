<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /** Display a listing of users. */
    public function index(): Response
    {
        return Inertia::render('users/Index', [
            'users' => User::orderBy('name')->get(['id', 'name', 'email', 'created_at']),
        ]);
    }

    /** Show the form for creating a new user. */
    public function create(): Response
    {
        return Inertia::render('users/Create');
    }

    /** Store a newly created user. */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('users.index');
    }

    /** Show the form for editing the specified user. */
    public function edit(User $user): Response
    {
        return Inertia::render('users/Edit', [
            'user' => $user,
        ]);
    }

    /** Update the specified user. */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('users.index');
    }

    /** Remove the specified user. */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('users.index');
    }
}
