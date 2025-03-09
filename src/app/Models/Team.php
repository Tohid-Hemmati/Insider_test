<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'strength', 'points', 'goals_for', 'goals_against', 'matches_played', 'wins', 'draws', 'losses'];

    public function updateStats($goalsFor, $goalsAgainst) {
        $this->goals_for += $goalsFor;
        $this->goals_against += $goalsAgainst;
        $this->matches_played += 1;

        if ($goalsFor > $goalsAgainst) {
            $this->points += 3;
            $this->wins += 1;
        } elseif ($goalsFor == $goalsAgainst) {
            $this->points += 1;
            $this->draws += 1;
        } else {
            $this->losses += 1;
        }

    }
    public function serialize()
    {
        return serialize([
            'attributes' => $this->attributes,
            'name' => $this->name,
            'strength' => $this->strength,
            'points' => $this->points,
            'goals_for' => $this->goals_for,
            'goals_against' => $this->goals_against,
            'matchesPlayed' => $this->matchesPlayed,
            'wins' => $this->wins,
            'draws' => $this->draws,
            'losses' => $this->losses,
        ]);
    }

    public function unserialize($data)
    {
        $unserialized = unserialize($data);
        $this->attributes = $unserialized['attributes'];
        $this->name = $unserialized['name'];
        $this->strength = $unserialized['strength'];
        $this->points = $unserialized['points'];
        $this->goals_for = $unserialized['goals_for'];
        $this->goals_against = $unserialized['goals_against'];
        $this->matchesPlayed = $unserialized['matchesPlayed'];
        $this->wins = $unserialized['wins'];
        $this->draws = $unserialized['draws'];
        $this->losses = $unserialized['losses'];
    }
}
