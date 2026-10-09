<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company', 'position', 'url', 'location', 'status', 'applied_at', 'notes'])]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => ApplicationStatus::Wishlist->value,
    ];

    protected static function booted(): void
    {
        // FR-7: moving to applied without a date stamps today.
        static::saving(function (Application $application) {
            if ($application->isDirty('status')
                && $application->status === ApplicationStatus::Applied
                && $application->applied_at === null) {
                $application->applied_at = today();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'applied_at' => 'date',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
