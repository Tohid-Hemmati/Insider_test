<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Team;
use App\Services\LeagueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

class LeagueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Session::flush();
        $this->artisan('config:clear');

        $this->app->make('config')->set('app.env', 'testing');
        putenv('APP_ENV=testing');
        }

    /** @test */
    public function it_can_start_simulation()
    {
        $response = $this->json('POST', '/api/start-simulation');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Simulation started']);

        $this->assertNotNull(session('league'), 'League should be stored in session');
        $this->assertCount(4, Team::all(), 'Four teams should be saved to the database');
    }

    /** @test */
    public function it_returns_error_when_getting_standings_without_active_league()
    {
        $response = $this->json('GET', '/api/standings');

        $response->assertStatus(400)
            ->assertJson(['error' => 'No active league']);
    }

    /** @test */
    public function it_can_get_standings_with_active_league()
    {
        $this->startLeagueSimulation();

        $response = $this->json('GET', '/api/standings');
        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['name', 'points', 'wins', 'draws', 'losses', 'goals_for', 'goals_against', 'matches_played']
            ])
            ->assertJsonCount(4);
    }

    /** @test */
    public function it_returns_error_when_getting_schedule_without_active_league()
    {
        $response = $this->json('GET', '/api/schedule');

        $response->assertStatus(400)
            ->assertJson(['error' => 'No active league']);
    }

    /** @test */
    public function it_can_get_schedule_with_active_league()
    {
        $this->startLeagueSimulation();

        $response = $this->json('GET', '/api/schedule');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'schedule' => [
                    '*' => [
                        '*' => ['homeTeam' => ['name'], 'awayTeam' => ['name'], 'home_goals', 'away_goals']
                    ]
                ],
                'currentRound'
            ])
            ->assertJsonFragment(['currentRound' => 0]);
    }

    /** @test */
    public function it_can_simulate_next_round()
    {
        $this->startLeagueSimulation();

        $response = $this->json('POST', '/api/simulate-next-round');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Next round simulated']);

        $league = session('league');
        $this->assertEquals(1, $league->getCurrentRound(), 'Current round should increment to 1');
    }

    /** @test */
    public function it_returns_error_when_simulating_next_round_without_active_league()
    {
        $response = $this->json('POST', '/api/simulate-next-round');

        $response->assertStatus(400)
            ->assertJson(['error' => 'No active league']);
    }

    /** @test */
    public function it_can_simulate_all_remaining_rounds()
    {
        $this->startLeagueSimulation();

        $response = $this->json('POST', '/api/simulate-all');

        $response->assertStatus(200)
            ->assertJson(['message' => 'All rounds simulated']);

        $league = session('league');
        $this->assertEquals(6, $league->getCurrentRound(), 'All 6 rounds should be simulated');

    }

    /** @test */
    public function it_returns_error_when_simulating_all_without_active_league()
    {
        $response = $this->json('POST', '/api/simulate-all');

        $response->assertStatus(400)
            ->assertJson(['error' => 'No active league']);
    }

    /** @test */
    public function it_returns_null_estimate_before_round_4()
    {
        $this->startLeagueSimulation();

        $response = $this->json('GET', '/api/champion-probabilities');

           $this->assertEquals('{}', $response->getContent());
    }

    /** @test */
    public function it_can_get_estimate_after_round_4()
    {
        $this->startLeagueSimulation();
        $league = session('league');
        for ($i = 0; $i < 4; $i++) {
            $league->simulateNextRound();
        }
        session(['league' => $league]);

        $response = $this->json('GET', '/api/champion-probabilities');
        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['name', 'probability']
            ])
            ->assertJsonCount(4);
    }

    /** @test */
    public function it_returns_error_when_getting_estimate_without_active_league()
    {
        $response = $this->json('GET', '/api/champion-probabilities');

        $response->assertStatus(400)
            ->assertJson(['error' => 'No active league']);
    }


    /** @test */
    public function it_can_get_champion_probabilities_after_round_4()
    {
        $this->startLeagueSimulation();
        $league = session('league');
        for ($i = 0; $i < 4; $i++) {
            $league->simulateNextRound();
        }
        session(['league' => $league]);

        $response = $this->json('GET', '/api/champion-probabilities');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['name', 'probability']
            ])
            ->assertJsonCount(4);

        $probabilities = $response->json();
        $this->assertTrue(max(array_column($probabilities, 'probability')) < 90, 'No team should have >90% probability');
        $totalProbability = array_sum(array_column($probabilities, 'probability'));
        $this->assertEqualsWithDelta(100, $totalProbability, 1, 'Probabilities should sum to ~100%');
    }

    /** @test */
    public function it_returns_error_when_getting_probabilities_without_active_league()
    {
        $response = $this->json('GET', '/api/champion-probabilities');

        $response->assertStatus(400)
            ->assertJson(['error' => 'No active league']);
    }

    /** @test */
    public function it_can_update_match_and_adjust_stats()
    {
        $this->startLeagueSimulation();
        $league = session('league');
        $league->simulateNextRound(); // Simulate one round to have a match to update
        session(['league' => $league]);

        $teamA = Team::where('name', 'Team A')->first();
        $initialPointsA = $teamA->points;

        $response = $this->json('POST', '/api/update-match', [
            'roundIndex' => 0,
            'matchIndex' => 0,
            'home_goals' => 3,
            'away_goals' => 1
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Match updated']);

        $teamA->refresh();
        $this->assertNotEquals($initialPointsA, $teamA->points, 'Team A points should change after update');
    }

    /** @test */
    public function it_returns_error_when_updating_match_without_active_league()
    {
        $response = $this->json('POST', '/api/update-match', [
            'roundIndex' => 0,
            'matchIndex' => 0,
            'home_goals' => 3,
            'away_goals' => 1
        ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'No active league']);
    }

    /** @test */
    public function it_handles_invalid_match_update()
    {
        $this->startLeagueSimulation();

        $response = $this->json('POST', '/api/update-match', [
            'roundIndex' => 10, // Out of bounds
            'matchIndex' => 0,
            'home_goals' => 3,
            'away_goals' => 1
        ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Invalid round or match index']);
    }

    // Helper method to start a league simulation
    private function startLeagueSimulation()
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
    }
}
