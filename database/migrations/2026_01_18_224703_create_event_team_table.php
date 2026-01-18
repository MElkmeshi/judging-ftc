<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite (typically in tests), we need a different approach
        // because of foreign key constraints
        if (DB::connection()->getDriverName() === 'sqlite') {
            // Disable foreign key checks
            DB::statement('PRAGMA foreign_keys = OFF');

            // Check if there's any data to migrate
            $hasData = DB::table('teams')->whereNotNull('event_id')->exists();

            if ($hasData) {
                // Store existing team-event relationships
                $teamEvents = DB::table('teams')
                    ->whereNotNull('event_id')
                    ->get(['id', 'event_id', 'is_active', 'created_at', 'updated_at']);
            }

            // Drop all tables that reference teams (we'll recreate them)
            Schema::dropIfExists('scores');
            Schema::dropIfExists('award_assignments');
            Schema::dropIfExists('teams');

            // Recreate teams table without event_id
            Schema::create('teams', function (Blueprint $table) {
                $table->id();
                $table->integer('team_number');
                $table->string('team_name');
                $table->string('school_organization')->nullable();
                $table->string('city')->nullable();
                $table->string('state_province')->nullable();
                $table->string('country')->nullable();
                $table->boolean('is_rookie')->default(false);
                $table->timestamps();
            });

            // Create the pivot table
            Schema::create('event_team', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained()->cascadeOnDelete();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['event_id', 'team_id']);
            });

            // Recreate scores table
            Schema::create('scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('award_id')->constrained()->cascadeOnDelete();
                $table->foreignId('criterion_id')->constrained()->cascadeOnDelete();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->foreignId('judge_id')->constrained('users')->cascadeOnDelete();
                $table->integer('score');
                $table->text('notes')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->unique(['award_id', 'criterion_id', 'team_id', 'judge_id']);
                $table->index(['award_id', 'team_id']);
            });

            // Recreate award_assignments table
            Schema::create('award_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('award_id')->constrained()->cascadeOnDelete();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->string('level');
                $table->decimal('calculated_score', 8, 4)->nullable();
                $table->integer('rank')->nullable();
                $table->boolean('is_finalized')->default(false);
                $table->boolean('is_override')->default(false);
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['award_id', 'team_id']);
                $table->index(['award_id', 'level']);
            });

            // Migrate data if we had any
            if ($hasData && isset($teamEvents)) {
                foreach ($teamEvents as $team) {
                    DB::table('event_team')->insert([
                        'event_id' => $team->event_id,
                        'team_id' => $team->id,
                        'is_active' => $team->is_active,
                        'created_at' => $team->created_at,
                        'updated_at' => $team->updated_at,
                    ]);
                }
            }

            // Re-enable foreign key checks
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            // For MySQL/PostgreSQL, we can handle this more gracefully

            // Create the pivot table first
            Schema::create('event_team', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained()->cascadeOnDelete();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['event_id', 'team_id']);
            });

            // Migrate existing data from teams.event_id to pivot table
            DB::statement('
                INSERT INTO event_team (event_id, team_id, is_active, created_at, updated_at)
                SELECT event_id, id, is_active, created_at, updated_at
                FROM teams
                WHERE event_id IS NOT NULL
            ');

            // Drop the columns from teams table
            Schema::table('teams', function (Blueprint $table) {
                $table->dropForeign(['event_id']);
                $table->dropColumn(['event_id', 'is_active']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // For SQLite, recreate tables with the old structure
            DB::statement('PRAGMA foreign_keys = OFF');

            // Store pivot data
            $teamEvents = DB::table('event_team')->get();

            // Drop tables
            Schema::dropIfExists('scores');
            Schema::dropIfExists('award_assignments');
            Schema::dropIfExists('event_team');
            Schema::dropIfExists('teams');

            // Recreate teams with event_id
            Schema::create('teams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->nullable()->constrained()->cascadeOnDelete();
                $table->integer('team_number');
                $table->string('team_name');
                $table->string('school_organization')->nullable();
                $table->string('city')->nullable();
                $table->string('state_province')->nullable();
                $table->string('country')->nullable();
                $table->boolean('is_rookie')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Recreate scores table
            Schema::create('scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('award_id')->constrained()->cascadeOnDelete();
                $table->foreignId('criterion_id')->constrained()->cascadeOnDelete();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->foreignId('judge_id')->constrained('users')->cascadeOnDelete();
                $table->integer('score');
                $table->text('notes')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->unique(['award_id', 'criterion_id', 'team_id', 'judge_id']);
                $table->index(['award_id', 'team_id']);
            });

            // Recreate award_assignments table
            Schema::create('award_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('award_id')->constrained()->cascadeOnDelete();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->string('level');
                $table->decimal('calculated_score', 8, 4)->nullable();
                $table->integer('rank')->nullable();
                $table->boolean('is_finalized')->default(false);
                $table->boolean('is_override')->default(false);
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['award_id', 'team_id']);
                $table->index(['award_id', 'level']);
            });

            // Restore data (take first event for each team)
            foreach ($teamEvents->groupBy('team_id') as $teamId => $events) {
                $firstEvent = $events->first();
                DB::table('teams')
                    ->where('id', $teamId)
                    ->update([
                        'event_id' => $firstEvent->event_id,
                        'is_active' => $firstEvent->is_active,
                    ]);
            }

            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            // Add back event_id to teams table
            Schema::table('teams', function (Blueprint $table) {
                $table->foreignId('event_id')->nullable()->constrained()->cascadeOnDelete();
                $table->boolean('is_active')->default(true);
            });

            // Migrate data back (take the first event for each team)
            $teams = DB::table('event_team')
                ->select('team_id', DB::raw('MIN(event_id) as event_id'), DB::raw('MAX(is_active) as is_active'))
                ->groupBy('team_id')
                ->get();

            foreach ($teams as $team) {
                DB::table('teams')
                    ->where('id', $team->team_id)
                    ->update([
                        'event_id' => $team->event_id,
                        'is_active' => $team->is_active,
                    ]);
            }

            Schema::dropIfExists('event_team');
        }
    }
};
