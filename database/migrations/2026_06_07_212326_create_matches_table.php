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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            
            $table->foreignId('home_team_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->foreignId('away_team_id')->nullable()->constrained('teams')->onDelete('set null');
            
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();
            
            $table->integer('round_number');
            $table->integer('match_number');
            
            $table->foreignId('winner_id')->nullable()->constrained('teams')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
