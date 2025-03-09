<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contest extends Model
{
    use HasFactory;

    protected $fillable = ['home_team_id', 'away_team_id', 'home_goals', 'away_goals'];

    public $homeTeam;
    public $awayTeam;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

public static function createWithTeams($homeTeam, $awayTeam)
{
    $contest = new self();
    $contest->homeTeam = $homeTeam;
    $contest->awayTeam = $awayTeam;
    $contest->setAttribute('home_team_id', $homeTeam->id);
    $contest->setAttribute('away_team_id', $awayTeam->id);
    return $contest;
}

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function simulate()
    {
        $homeGoals = self::poissonSample($this->getLambdaHome());
        $awayGoals = self::poissonSample($this->getLambdaAway());

        $this->home_goals = $homeGoals;
        $this->away_goals = $awayGoals;

        $this->homeTeam->updateStats($homeGoals, $awayGoals);
        $this->awayTeam->updateStats($awayGoals, $homeGoals);

        $this->save();
    }

    public function getLambdaHome()
    {
        $a = 1.1;
        $b = 0.5;
        return $a * pow($this->homeTeam->strength / $this->awayTeam->strength, 0.5) + $b;
    }

    public function getLambdaAway()
    {
        $a = 1;
        return $a * pow($this->awayTeam->strength / $this->homeTeam->strength, 0.5);
    }

    public static function poissonSample($lambda)
    {
        $L = exp(-$lambda);
        $k = 0;
        $p = 1.0;
        do {
            $k++;
            $p *= mt_rand() / mt_getrandmax();
        } while ($p > $L);
        return $k - 1;
    }

    public function serialize()
    {
        return serialize([
            'homeTeam' => $this->homeTeam,
            'awayTeam' => $this->awayTeam,
            'home_team_id' => $this->home_team_id,
            'away_team_id' => $this->away_team_id,
            'home_goals' => $this->home_goals,
            'away_goals' => $this->away_goals,
            'attributes' => $this->attributes,
        ]);
    }

    public function unserialize($data)
    {
        $unserialized = unserialize($data);
        $this->homeTeam = $unserialized['homeTeam'];
        $this->awayTeam = $unserialized['awayTeam'];
        $this->setAttribute('home_team_id', $unserialized['home_team_id']);
        $this->setAttribute('away_team_id', $unserialized['away_team_id']);
        $this->home_goals = $unserialized['home_goals'];
        $this->away_goals = $unserialized['away_goals'];
        $this->attributes = $unserialized['attributes'];
    }
}
