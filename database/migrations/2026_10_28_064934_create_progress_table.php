<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('scenario_id')->constrained('scenarios')->cascadeOnDelete();
            $table->foreignId('current_node_id')->nullable()
                ->constrained('scenario_nodes')->nullOnDelete();
            $table->string('status', 12)->default('playing'); // playing | finished
            $table->smallInteger('score')->default(0);
            $table->string('ending_kind', 10)->nullable();
            $table->unsignedSmallInteger('xp_gained')->default(0);
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'scenario_id']);
        });

        Schema::create('attempt_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('attempts')->cascadeOnDelete();
            $table->foreignId('node_id')->constrained('scenario_nodes')->cascadeOnDelete();
            $table->foreignId('choice_id')->constrained('scenario_choices')->cascadeOnDelete();
            $table->smallInteger('points')->default(0);
            $table->timestamps();
        });

        Schema::create('lesson_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['lesson_id', 'user_id']);
        });

        Schema::create('safety_action_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('safety_action_id')->constrained('safety_actions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['safety_action_id', 'user_id']);
        });

        Schema::create('badge_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_id')->constrained('badges')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['badge_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badge_user');
        Schema::dropIfExists('safety_action_user');
        Schema::dropIfExists('lesson_user');
        Schema::dropIfExists('attempt_steps');
        Schema::dropIfExists('attempts');
    }
};
