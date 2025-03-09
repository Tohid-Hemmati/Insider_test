<template>
    <div class="container">
        <h1>Insider Champions League</h1>
        <button v-if="!league" @click="startSimulation">Start Simulation</button>
        <div v-if="league">
            <h2>Standings</h2>
            <table class="table">
                <thead>
                <tr>
                    <th>Team</th>
                    <th>P</th>
                    <th>W</th>
                    <th>D</th>
                    <th>L</th>
                    <th>GF</th>
                    <th>GA</th>
                    <th>GD</th>
                    <th>Pts</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="team in standings" :key="team.name">
                    <td>{{ team.name }}</td>
                    <td>{{ team.matches_played }}</td>
                    <td>{{ team.wins }}</td>
                    <td>{{ team.draws }}</td>
                    <td>{{ team.losses }}</td>
                    <td>{{ team.goals_for }}</td>
                    <td>{{ team.goals_against }}</td>
                    <td>{{ team.goals_for - team.goals_against }}</td>
                    <td>{{ team.points }}</td>
                </tr>
                </tbody>
            </table>

            <h2>Matches</h2>
            <div v-for="(round, roundIndex) in schedule" :key="roundIndex" class="round">
                <h3>Round {{ roundIndex + 1 }}</h3>
                <div v-for="(match, matchIndex) in round" :key="matchIndex" class="match">
        <span>{{ match.homeTeam.name }} vs {{ match.awayTeam.name }}:
            {{
                match.home_goals !== null && match.away_goals !== null ? `${match.home_goals} - ${match.away_goals}` : 'To Be Decided'
            }}</span>
                    <button v-if="match.home_goals !== null && match.away_goals !== null"
                            @click="openEditModal(roundIndex, matchIndex)">Edit
                    </button>
                </div>
            </div>
            <button @click="simulateNextRound" :disabled="currentRound >= 6">Simulate Next Round</button>
            <button @click="simulateAll" :disabled="currentRound >= 6">Play All</button>

            <div v-if="currentRound >= 4">
                <h2>Champion Probabilities</h2>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Team</th>
                        <th>Probability (%)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="team in championProbabilities" :key="team.name">
                        <td>{{ team.name }}</td>
                        <td>{{ team.probability.toFixed(1) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="showModal" class="modal">
            <div class="modal-content">
                <h3>Edit Match</h3>
                <label>Home Goals:</label>
                <input v-model.number="editHomeGoals" type="number" min="0">
                <label>Away Goals:</label>
                <input v-model.number="editAwayGoals" type="number" min="0">
                <button @click="updateMatch">Save</button>
                <button @click="showModal = false">Cancel</button>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        return {
            league: null,
            standings: [],
            schedule: [],
            currentRound: 0,
            estimate: [],
            championProbabilities: [],
            showModal: false,
            editRoundIndex: null,
            editMatchIndex: null,
            editHomeGoals: 0,
            editAwayGoals: 0
        };
    },
    methods: {
        async startSimulation() {
            await axios.post('/api/start-simulation');
            this.league = true;
            this.fetchData();
        },
        async fetchData() {
            try {
                const [scheduleRes, standingsRes] = await Promise.all([
                    axios.get('/api/schedule'),
                    axios.get('/api/standings')
                ]);
                this.schedule = scheduleRes.data.schedule;
                this.currentRound = scheduleRes.data.currentRound;
                this.standings = standingsRes.data;

                if (this.currentRound >= 4) {
                    const probsRes = await axios.get('/api/champion-probabilities');
                    this.championProbabilities = probsRes.data || [];
                } else {
                    this.championProbabilities = [];
                }
            } catch (error) {
                console.error('Fetch error:', error.response?.data || error.message);
            }
        },
        async simulateNextRound() {
            await axios.post('/api/simulate-next-round');
            this.fetchData();
        },
        async simulateAll() {
            await axios.post('/api/simulate-all');
            this.fetchData();
        },
        openEditModal(roundIndex, matchIndex) {
            const match = this.schedule[roundIndex][matchIndex];
            this.editRoundIndex = roundIndex;
            this.editMatchIndex = matchIndex;
            this.editHomeGoals = match.home_goals;
            this.editAwayGoals = match.away_goals;
            this.showModal = true;
        },
        async updateMatch() {
            await axios.post('/api/update-match', {
                roundIndex: this.editRoundIndex,
                matchIndex: this.editMatchIndex,
                home_goals: this.editHomeGoals,
                away_goals: this.editAwayGoals
            });
            this.showModal = false;
            this.fetchData();
        }
    }
};
</script>

<style>
body {
    font-family: 'Arial', sans-serif;
    background: #f4f4f4;
    color: #333;
    text-align: center;
    margin: 0;
    padding: 20px;
}

.container {
    max-width: 800px;
    margin: auto;
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
}

h1 {
    color: #2c3e50;
}

button {
    background: #3498db;
    color: white;
    margin: 5px;
    padding: 8px 15px;
    font-size: 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s ease-in-out;
}

button:not(:last-child) {
    margin-right: 10px;
}
button:hover {
    background: #2980b9;
}

button:disabled {
    background: #bdc3c7;
    cursor: not-allowed;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.table th, .table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
}

.table th {
    background: #34495e;
    color: white;
}

.table tbody tr:nth-child(even) {
    background: #f9f9f9;
}

.round {
    margin-top: 20px;
    padding: 10px;
    background: #ecf0f1;
    border-radius: 5px;
}

.match {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: white;
    padding: 10px;
    border-radius: 5px;
    margin-top: 5px;
    box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    padding: 20px;
    border-radius: 10px;
    width: 300px;
    box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.2);
}

.modal-content input {
    width: 100%;
    padding: 8px;
    margin: 5px 0;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.modal-content button {
    width: 100%;
    margin-top: 10px;
}
</style>
