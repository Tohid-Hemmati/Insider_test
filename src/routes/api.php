<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeagueController;

Route::middleware('web')->group(function () {
    Route::post('/start-simulation', [LeagueController::class, 'startSimulation']);
    Route::get('/standings', [LeagueController::class, 'getStandings']);
    Route::get('/schedule', [LeagueController::class, 'getSchedule']);
    Route::post('/simulate-next-round', [LeagueController::class, 'simulateNextRound']);
    Route::post('/simulate-all', [LeagueController::class, 'simulateAllRemainingRounds']);
    Route::post('/update-match', [LeagueController::class, 'updateMatch']);
    Route::get('/champion-probabilities', [LeagueController::class, 'getChampionProbabilities']);
});
