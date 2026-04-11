<?php

use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

test('requires authentication for user routes', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

test('can list users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('users/Index')
            ->has('users', 1)
        );
});

test('can view create form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('users.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('users/Create'));
});

test('can store a user', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])
        ->assertRedirect(route('users.index'));

    expect(User::where('email', 'newuser@example.com')->exists())->toBeTrue();
});

test('store validates required fields', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'password']);
});

test('store validates unique email', function () {
    $admin = User::factory()->create();
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Test',
            'email' => 'taken@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])
        ->assertSessionHasErrors(['email']);
});

test('can view edit form', function () {
    $admin = User::factory()->create();
    $targetUser = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('users.edit', $targetUser))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('users/Edit')
            ->has('user')
        );
});

test('can update user name and email', function () {
    $admin = User::factory()->create();
    $targetUser = User::factory()->create();

    $this->actingAs($admin)
        ->put(route('users.update', $targetUser), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ])
        ->assertRedirect(route('users.index'));

    expect($targetUser->fresh()->name)->toBe('Updated Name')
        ->and($targetUser->fresh()->email)->toBe('updated@example.com');
});

test('can set new password for user', function () {
    $admin = User::factory()->create();
    $targetUser = User::factory()->create();
    $oldPasswordHash = $targetUser->password;

    $this->actingAs($admin)
        ->put(route('users.update', $targetUser), [
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertRedirect(route('users.index'));

    expect($targetUser->fresh()->password)->not->toBe($oldPasswordHash);
});

test('update allows same email for own record', function () {
    $admin = User::factory()->create();
    $targetUser = User::factory()->create(['email' => 'keep@example.com']);

    $this->actingAs($admin)
        ->put(route('users.update', $targetUser), [
            'name' => 'Same Email User',
            'email' => 'keep@example.com',
        ])
        ->assertSessionHasNoErrors();
});

test('can delete a user', function () {
    $admin = User::factory()->create();
    $targetUser = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('users.destroy', $targetUser))
        ->assertRedirect(route('users.index'));

    expect(User::find($targetUser->id))->toBeNull();
});

test('cannot delete own account', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('users.destroy', $admin))
        ->assertSessionHasErrors(['user']);

    expect(User::find($admin->id))->not->toBeNull();
});
