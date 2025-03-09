<?php

namespace App\Http\Controllers;

use App\Services\LeagueService;
use App\Models\Team;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function startSimulation()
    {
        $teams = [
            new Team(['name' => "Team A", 'strength' => 1.1]),
            new Team(['name' => "Team B", 'strength' => 1.0]),
            new Team(['name' => "Team C", 'strength' => 0.9]),
            new Team(['name' => "Team D", 'strength' => 0.8]),
        ];
        foreach ($teams as $team) {
            $team->save();
        }
        $league = new LeagueService($teams);
        session(['league' => $league]);
        return response()->json(['message' => 'Simulation started']);
    }

    public function getStandings()
    {
        $league = session('league');
        if (!$league) {
            return response()->json(['error' => 'No active league'], 400);
        }
        return response()->json($league->getStandings());
    }

    public function getSchedule()
    {
        $league = session('league');
        if (!$league) {
            return response()->json(['error' => 'No active league'], 400);
        }
        return response()->json([
            'schedule' => $league->getSchedule(),
            'currentRound' => $league->getCurrentRound()
        ]);
    }

    public function simulateNextRound()
    {
        $league = session('league');
        if (!$league) {
            return response()->json(['error' => 'No active league'], 400);
        }
        $league->simulateNextRound();
        session(['league' => $league]);
        return response()->json(['message' => 'Next round simulated']);
    }

    public function simulateAllRemainingRounds()
    {
        $league = session('league');
        if (!$league) {
            return response()->json(['error' => 'No active league'], 400);
        }
        while ($league->getCurrentRound() < count($league->getSchedule())) {
            $league->simulateNextRound();
        }
        session(['league' => $league]);
        return response()->json(['message' => 'All rounds simulated']);
    }

    public function getEstimate()
    {
        $league = session('league');
        if (!$league) {
            return response()->json(['error' => 'No active league'], 400);
        }
        return response()->json($league->estimateFinalStandings());
    }

    public function getChampionProbabilities()
    {
        $league = session('league');
        if (!$league) {
            return response()->json(['error' => 'No active league'], 400);
        }
        $probabilities = $league->getChampionProbabilities();
        return response()->json($probabilities);
    }

    public function updateMatch(Request $request)
    {
        $league = session('league');
        if (!$league) {
            return response()->json(['error' => 'No active league'], 400);
        }

        $roundIndex = $request->input('roundIndex');
        $matchIndex = $request->input('matchIndex');
        $homeGoals = $request->input('home_goals');
        $awayGoals = $request->input('away_goals');

        $schedule = $league->getRawSchedule();
        if (!isset($schedule[$roundIndex]) || !isset($schedule[$roundIndex][$matchIndex])) {
            return response()->json(['error' => 'Invalid round or match index'], 400);
        }
        $match = $schedule[$roundIndex][$matchIndex];

        $oldHomeGoals = $match->home_goals;
        $oldAwayGoals = $match->away_goals;

        $match->home_goals = $homeGoals;
        $match->away_goals = $awayGoals;
        $match->save();

        $homeTeam = $match->homeTeam;
        $awayTeam = $match->awayTeam;

        $this->reverseTeamStats($homeTeam, $oldHomeGoals, $oldAwayGoals);
        $this->reverseTeamStats($awayTeam, $oldAwayGoals, $oldHomeGoals);

        $homeTeam->updateStats($homeGoals, $awayGoals);
        $awayTeam->updateStats($awayGoals, $homeGoals);

        $homeTeam->save();
        $awayTeam->save();

        session(['league' => $league]);
        return response()->json(['message' => 'Match updated']);
    }

    private function reverseTeamStats($team, $oldGoalsFor, $oldGoalsAgainst)
    {
        if ($oldGoalsFor !== null && $oldGoalsAgainst !== null) {
            $team->goals_for -= $oldGoalsFor;
            $team->goals_against -= $oldGoalsAgainst;
            $team->matches_played -= 1;

            if ($oldGoalsFor > $oldGoalsAgainst) {
                $team->points -= 3;
                $team->wins -= 1;
            } elseif ($oldGoalsFor == $oldGoalsAgainst) {
                $team->points -= 1;
                $team->draws -= 1;
            } else {
                $team->losses -= 1;
            }
        }
    }
}
