<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('icon', 16);
            $table->string('color', 9)->default('#22d3ee');
            $table->string('tagline');
            $table->text('description');
            $table->text('family_tip')->nullable();
            $table->timestamps();
        });

        Schema::create('scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('synopsis');
            $table->string('channel', 40)->default('WhatsApp');
            $table->unsignedTinyInteger('difficulty')->default(1);
            $table->unsignedSmallInteger('xp_reward')->default(100);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('scenario_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_id')->constrained('scenarios')->cascadeOnDelete();
            $table->string('node_key', 40);
            $table->boolean('is_start')->default(false);
            // story = adegan biasa; good/neutral/bad = akhir cerita
            $table->string('kind', 10)->default('story');
            $table->string('speaker')->nullable();
            $table->string('title')->nullable();
            $table->text('body');
            $table->text('lesson')->nullable();
            $table->timestamps();
            $table->unique(['scenario_id', 'node_key']);
        });

        Schema::create('scenario_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained('scenario_nodes')->cascadeOnDelete();
            $table->foreignId('next_node_id')->nullable()
                ->constrained('scenario_nodes')->nullOnDelete();
            $table->text('label');
            $table->smallInteger('points')->default(0);
            $table->text('feedback')->nullable();
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('red_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_id')->constrained('scenarios')->cascadeOnDelete();
            $table->string('title');
            $table->text('explanation');
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('summary');
            $table->longText('body');
            $table->unsignedTinyInteger('read_minutes')->default(3);
            $table->unsignedSmallInteger('xp_reward')->default(20);
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('safety_actions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('icon', 16)->default('✅');
            $table->unsignedSmallInteger('xp_reward')->default(25);
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('description');
            $table->string('icon', 16);
            // xp, scenarios, perfect, category, good_end, lessons, actions
            $table->string('type', 30);
            $table->unsignedInteger('threshold')->default(1);
            $table->foreignId('category_id')->nullable()
                ->constrained('categories')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
        Schema::dropIfExists('safety_actions');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('red_flags');
        Schema::dropIfExists('scenario_choices');
        Schema::dropIfExists('scenario_nodes');
        Schema::dropIfExists('scenarios');
        Schema::dropIfExists('categories');
    }
};
