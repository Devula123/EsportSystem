<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('match_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('match_id')->nullable();
            $table->unsignedBigInteger('tournament_id')->nullable();
            $table->string('tournament_name');
            $table->unsignedBigInteger('home_team_id')->nullable();
            $table->unsignedBigInteger('away_team_id')->nullable();
            $table->string('home_team_name');
            $table->string('away_team_name');
            $table->integer('home_score');
            $table->integer('away_score');
            $table->unsignedBigInteger('winner_team_id')->nullable();
            $table->string('winner_team_name')->nullable();
            $table->integer('round_number');
            $table->integer('match_number');
            $table->timestamp('played_at')->nullable();
            $table->timestamps();
        });

        Schema::create('match_history_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_history_id')->constrained('match_histories')->onDelete('cascade');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('team_name');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('username');
            $table->string('role');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('match_history_players');
        Schema::dropIfExists('match_histories');
    }
};
