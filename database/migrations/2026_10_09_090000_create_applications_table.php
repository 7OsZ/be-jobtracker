<?php

use App\Enums\ApplicationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('company', 120);
            $table->string('position', 120);
            $table->string('url', 2048)->nullable();
            $table->string('location', 120)->nullable();
            $table->string('status', 20);
            $table->date('applied_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Blueprint has no check() helper; enum() would force varchar(255).
        $allowed = implode(', ', array_map(fn ($v) => "'{$v}'", ApplicationStatus::values()));
        DB::statement("ALTER TABLE applications ADD CONSTRAINT applications_status_check CHECK (status IN ({$allowed}))");
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
