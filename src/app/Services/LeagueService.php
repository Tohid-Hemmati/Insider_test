<?php

namespace App\Services;

use App\Models\Contest;

class LeagueService
{
    protected $teams;
    protected $schedule;
    protected $currentRound = 0;

    public function __construct($teams)
    {
        $this->teams = $teams;
        $this->generateSchedule();
    }

    private function generateSchedule()
    {
        $this->schedule = [
            [Contest::createWithTeams($this->teams[0], $this->teams[1]), Contest::createWithTeams($this->teams[2], $this->teams[3])],
            [Contest::createWithTeams($this->teams[0], $this->teams[2]), Contest::createWithTeams($this->teams[1], $this->teams[3])],
            [Contest::createWithTeams($this->teams[0], $this->teams[3]), Contest::createWithTeams($this->teams[1], $this->teams[2])],
            [Contest::createWithTeams($this->teams[1], $this->teams[0]), Contest::createWithTeams($this->teams[3], $this->teams[2])],
            [Contest::createWithTeams($this->teams[2], $this->teams[0]), Contest::createWithTeams($this->teams[3], $this->teams[1])],
            [Contest::createWithTeams($this->teams[3], $this->teams[0]), Contest::createWithTeams($this->teams[2], $this->teams[1])]
        ];
    }

    public function simulateNextRound()
    {
        if ($this->currentRound < count($this->schedule)) {
            foreach ($this->schedule[$this->currentRound] as $index => $contest) {
                $contest->simulate();
            }
            $this->currentRound++;
        }
    }

    public function getStandings()
    {
        $standings = $this->teams;
        usort($standings, function ($a, $b) {
            if ($a->points != $b->points) {
                return $b->points - $a->points;
            }
            $gdA = $a->goals_for - $a->goals_against;
            $gdB = $b->goals_for - $b->goals_against;
            if ($gdA != $gdB) {
                return $gdB - $gdA;
            }
            return $b->goals_for - $a->goals_for;
        });

        $formattedStandings = array_map(function ($team) {
            return [
                'name' => $team->name,
                'points' => $team->points ?? 0,
                'wins' => $team->wins ?? 0,
                'draws' => $team->draws ?? 0,
                'losses' => $team->losses ?? 0,
                'goals_for' => $team->goals_for ?? 0,
                'goals_against' => $team->goals_against ?? 0,
                'matches_played' => $team->matches_played ?? 0,
            ];
        }, $standings);
        return $formattedStandings;
    }

    public function getChampionProbabilities($numSimulations = 1000)
    {
        if ($this->currentRound < 4) {
            return null;
        }

        $remainingRounds = array_slice($this->schedule, $this->currentRound);
        $championCounts = array_fill(0, count($this->teams), 0);

        for ($sim = 0; $sim < $numSimulations; $sim++) {
            $simTeams = [];
            foreach ($this->teams as $index => $team) {
                $simTeams[$index] = clone $team;
            }

        foreach ($remainingRounds as $round) {
            foreach ($round as $contest) {
                $homeIndex = array_search($contest->homeTeam, $this->teams);
                $awayIndex = array_search($contest->awayTeam, $this->teams);
                $homeTeam = $simTeams[$homeIndex];
                $awayTeam = $simTeams[$awayIndex];
                $lambdaHome = $contest->getLambdaHome();
                $lambdaAway = $contest->getLambdaAway();

                    $homeGoals = Contest::poissonSample($lambdaHome);
                    $awayGoals = Contest::poissonSample($lambdaAway);

                    $homeTeam->updateStats($homeGoals, $awayGoals);
                    $awayTeam->updateStats($awayGoals, $homeGoals);
                }
            }

            usort($simTeams, function ($a, $b) {
                if ($a->points != $b->points) {
                    return $b->points - $a->points;
                }
                $gdA = $a->goals_for - $a->goals_against;
                $gdB = $b->goals_for - $b->goals_against;
                return $gdB - $gdA;
            });

        $winnerName = $simTeams[0]->name;
        $winnerIndex = array_search($winnerName, array_column($this->teams, 'name'));
        $championCounts[$winnerIndex]++;
    }

        $probabilities = [];
        foreach ($this->teams as $index => $team) {
            $probabilities[] = [
                'name' => $team->name,
                'probability' => ($championCounts[$index] / $numSimulations) * 100 // Percentage
            ];
        }

        usort($probabilities, function ($a, $b) {
            return $b['probability'] - $a['probability'];
        });

        return $probabilities;
    }
    public function serialize()
    {
        return serialize([
            'teams' => $this->teams,
            'schedule' => $this->schedule,
            'currentRound' => $this->currentRound,
        ]);
    }

    public function unserialize($data)
    {
        $unserialized = unserialize($data);
        $this->teams = $unserialized['teams'];
        $this->schedule = $unserialized['schedule'];
        $this->currentRound = $unserialized['currentRound'];
    }

    public function getSchedule()
    {
        $rawSchedule = $this->schedule;
        $formattedSchedule = [];

        foreach ($rawSchedule as $round) {
            $formattedRound = [];
            foreach ($round as $contest) {
                $formattedRound[] = [
                    'homeTeam' => ['name' => $contest->homeTeam->name],
                    'awayTeam' => ['name' => $contest->awayTeam->name],
                    'home_goals' => $contest->home_goals,
                    'away_goals' => $contest->away_goals,
                ];
            }
            $formattedSchedule[] = $formattedRound;
        }

        return $formattedSchedule;
    }

    public function getRawSchedule()
    {
        return $this->schedule;
    }

    public function getCurrentRound()
    {
        return $this->currentRound;
    }
}
