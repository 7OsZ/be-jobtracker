<?php

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('links an application to its user', function () {
    $user = User::factory()->create();
    $application = Application::factory()->for($user)->create();

    expect($user->applications)->toHaveCount(1)
        ->and($application->status)->toBe(ApplicationStatus::Wishlist);
});

it('rejects an unknown status at the database level', function () {
    $application = Application::factory()->create();

    DB::table('applications')->where('id', $application->id)->update(['status' => 'ghosted']);
})->throws(QueryException::class);

it('deletes applications with their user', function () {
    $application = Application::factory()->create();

    $application->user->delete();

    expect(Application::count())->toBe(0);
});
