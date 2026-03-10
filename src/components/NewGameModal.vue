<template>
  <div class="modal-backdrop" @click.self="$emit('cancel')">
    <div class="modal">

      <div class="modal-header">
        <h2>🏀 New Game</h2>
        <button class="btn-close" @click="$emit('cancel')">✕</button>
      </div>

      <div class="modal-body">

        <!-- ── Roster ─────────────────────────────────── -->
        <section class="section">
          <h3 class="section-label">Roster</h3>

          <div class="roster-options">
            <label
              v-for="opt in rosterOptions"
              :key="opt.value"
              class="roster-option"
              :class="{ selected: rosterChoice === opt.value }"
            >
              <input v-model="rosterChoice" type="radio" :value="opt.value" class="sr-only" />
              <span class="option-icon">{{ opt.icon }}</span>
              <span class="option-text">
                <strong>{{ opt.label }}</strong>
                <small>{{ opt.desc }}</small>
              </span>
            </label>
          </div>

          <!-- Team picker when "existing team" is chosen -->
          <div v-if="rosterChoice === 'team'" class="team-picker-block">
            <div v-if="teamsLoading" class="state-msg">Loading teams…</div>
            <div v-else-if="teams.length === 0" class="state-msg muted">
              No teams found. <router-link :to="{ name: 'teams', params: { orgSlug: $route.params.orgSlug } }" class="link">Create a team first</router-link>.
            </div>
            <template v-else>
              <select v-model="selectedTeamId" class="form-input" @change="loadTeamPlayers">
                <option value="">— Select a team —</option>
                <option v-for="team in teams" :key="team.id" :value="team.id">
                  {{ team.name }} · {{ team.category }}
                </option>
              </select>

              <!-- Home / Away toggle -->
              <div v-if="selectedTeamId" class="side-toggle">
                <label
                  class="side-option"
                  :class="{ selected: teamSide === 'home' }"
                >
                  <input v-model="teamSide" type="radio" value="home" class="sr-only" />
                  🏠 We play at Home
                </label>
                <label
                  class="side-option"
                  :class="{ selected: teamSide === 'away' }"
                >
                  <input v-model="teamSide" type="radio" value="away" class="sr-only" />
                  🚌 We play Away
                </label>
              </div>

              <!-- Opponent input -->
              <div v-if="selectedTeamId" class="form-group">
                <label>Opponent</label>
                <input
                  v-model="opponentName"
                  type="text"
                  class="form-input"
                  placeholder="Opponent team name"
                  maxlength="100"
                />
              </div>

              <!-- Matchup preview -->
              <div v-if="selectedTeam && opponentName" class="matchup-preview">
                <span class="matchup-side" :class="{ 'matchup-ours': teamSide === 'home' }">
                  {{ teamSide === 'home' ? selectedTeam.shortName : opponentName }}
                </span>
                <span class="matchup-vs">vs</span>
                <span class="matchup-side" :class="{ 'matchup-ours': teamSide === 'away' }">
                  {{ teamSide === 'away' ? selectedTeam.shortName : opponentName }}
                </span>
              </div>

              <!-- Players preview -->
              <div v-if="playersLoading" class="state-msg">Loading players…</div>
              <div v-else-if="selectedTeamId && teamPlayers.length === 0" class="state-msg muted">
                This team has no players yet.
              </div>
              <div v-else-if="teamPlayers.length > 0" class="players-preview">
                <div class="players-preview-header">
                  <span class="preview-count">{{ teamPlayers.length }} player{{ teamPlayers.length !== 1 ? 's' : '' }}</span>
                  <span class="preview-note">Jersey numbers can be changed in-game</span>
                </div>
                <div class="players-grid">
                  <div v-for="p in teamPlayers" :key="p.id" class="player-chip">
                    <span class="player-chip-number">#{{ p.jerseyNumber ?? '?' }}</span>
                    <span class="player-chip-name">{{ p.name }}</span>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </section>

        <div class="divider"></div>

        <!-- ── Game Info ───────────────────────────────── -->
        <section class="section">
          <h3 class="section-label">Game Info</h3>

          <!-- Manual team names — only when not using a team roster -->
          <div v-if="rosterChoice !== 'team'" class="form-row">
            <div class="form-group">
              <label>Home Team</label>
              <input v-model="form.homeTeam" type="text" class="form-input" placeholder="Home Team" maxlength="100" />
            </div>
            <div class="form-group">
              <label>Opposition</label>
              <input v-model="form.oppositionTeam" type="text" class="form-input" placeholder="Opposition" maxlength="100" />
            </div>
          </div>

          <div class="form-group">
            <label>Date</label>
            <input v-model="form.date" type="date" class="form-input form-input--date" />
          </div>
        </section>

        <div v-if="error" class="alert-error">{{ error }}</div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" @click="$emit('cancel')">Cancel</button>
        <button class="btn btn-primary" :disabled="!canConfirm" @click="confirm">
          Start New Game
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { teamService } from '../utils/teamService'
import { gameState } from '../store/gameStore'

const emit = defineEmits(['confirm', 'cancel'])

const today = new Date().toISOString().slice(0, 10)

const form = ref({
  homeTeam: gameState.homeTeam || 'Home Team',
  oppositionTeam: gameState.oppositionTeam || 'Opposition',
  date: today,
})

const rosterChoice = ref('team')
const teamSide = ref('home')
const opponentName = ref('')
const selectedTeamId = ref('')
const teams = ref([])
const teamPlayers = ref([])
const teamsLoading = ref(false)
const playersLoading = ref(false)
const error = ref(null)

const rosterOptions = [
  { value: 'team',  icon: '🏀', label: 'Use existing team',   desc: 'Load a team roster from your database' },
  { value: 'keep',  icon: '👥', label: 'Keep current players', desc: 'Same roster, stats reset to zero' },
  { value: 'fresh', icon: '✨', label: 'Fresh start',          desc: 'Reset to 12 default placeholder players' },
]

const selectedTeam = computed(() => teams.value.find(t => t.id === selectedTeamId.value) ?? null)

const canConfirm = computed(() => {
  if (rosterChoice.value === 'team') {
    return !!selectedTeamId.value && teamPlayers.value.length > 0 && !!opponentName.value.trim()
  }
  return true
})

async function loadTeamPlayers() {
  if (!selectedTeamId.value) { teamPlayers.value = []; return }
  playersLoading.value = true
  error.value = null
  try {
    teamPlayers.value = await teamService.listPlayers(selectedTeamId.value)
  } catch (e) {
    error.value = 'Failed to load team players: ' + e.message
  } finally {
    playersLoading.value = false
  }
}

function confirm() {
  let homeTeam, oppositionTeam, players

  if (rosterChoice.value === 'team') {
    const short = selectedTeam.value?.shortName || selectedTeam.value?.name || 'Home'
    homeTeam     = teamSide.value === 'home' ? short : opponentName.value.trim()
    oppositionTeam = teamSide.value === 'away' ? short : opponentName.value.trim()
    players = teamPlayers.value.map((p, i) => ({
      playerId: p.id,
      jerseyNumber: p.jerseyNumber ?? (i + 1),
      name: p.name,
      totalPoints: 0,
      totalFouls: 0,
      statistics: [],
    }))
  } else {
    homeTeam = form.value.homeTeam.trim() || 'Home Team'
    oppositionTeam = form.value.oppositionTeam.trim() || 'Opposition'
    players = rosterChoice.value === 'keep' ? null : []
  }

  emit('confirm', {
    homeTeam,
    oppositionTeam,
    date: new Date(form.value.date).toISOString(),
    teamId: rosterChoice.value === 'team' ? selectedTeamId.value : null,
    rosterChoice: rosterChoice.value,
    players,
  })
}

onMounted(async () => {
  teamsLoading.value = true
  try {
    teams.value = await teamService.list()
    // Auto-select if only one team
    if (teams.value.length === 1) {
      selectedTeamId.value = teams.value[0].id
      loadTeamPlayers()
    }
  } catch {
    // silently ignore — API may not be running
  } finally {
    teamsLoading.value = false
  }
})
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: var(--spacing-md);
}

.modal {
  background: var(--bg-light);
  border-radius: var(--radius-lg);
  width: 100%;
  max-width: 540px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-md) var(--spacing-lg);
  border-bottom: 1px solid var(--border-color);
  flex-shrink: 0;
}

.modal-header h2 { font-size: 1.25rem; font-weight: 700; }

.modal-body {
  padding: var(--spacing-lg);
  overflow-y: auto;
  flex: 1;
}

.section { margin-bottom: var(--spacing-md); }

.section-label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text-muted);
  margin-bottom: var(--spacing-sm);
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-md);
}

.form-group { margin-bottom: var(--spacing-sm); }

.form-group label {
  display: block;
  font-weight: 600;
  font-size: 0.85rem;
  margin-bottom: var(--spacing-xs);
}

.form-input {
  width: 100%;
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  background: var(--bg-light);
  color: var(--text-light);
  font-size: 0.95rem;
  box-sizing: border-box;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
}

.divider {
  height: 1px;
  background: var(--border-color);
  margin: var(--spacing-md) 0;
}

/* ── Roster options ── */
.roster-options {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-md);
}

.roster-option {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-sm) var(--spacing-md);
  border: 2px solid var(--border-color);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: border-color var(--transition-fast), background var(--transition-fast);
}

.roster-option:hover { border-color: var(--primary-color); background: rgba(255, 107, 53, 0.04); }
.roster-option.selected { border-color: var(--primary-color); background: rgba(255, 107, 53, 0.08); }

.option-icon { font-size: 1.4rem; flex-shrink: 0; }
.option-text { display: flex; flex-direction: column; gap: 2px; }
.option-text strong { font-size: 0.95rem; }
.option-text small { font-size: 0.8rem; color: var(--text-muted); }

/* ── Team picker ── */
.team-picker-block {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

/* ── Home / Away toggle ── */
.side-toggle {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--spacing-sm);
}

.side-option {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-xs);
  padding: var(--spacing-sm);
  border: 2px solid var(--border-color);
  border-radius: var(--radius-md);
  cursor: pointer;
  font-weight: 600;
  font-size: 0.9rem;
  transition: border-color var(--transition-fast), background var(--transition-fast);
  text-align: center;
}

.side-option:hover { border-color: var(--primary-color); background: rgba(255, 107, 53, 0.04); }
.side-option.selected { border-color: var(--primary-color); background: rgba(255, 107, 53, 0.08); color: var(--primary-color); }

/* ── Matchup preview ── */
.matchup-preview {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-md);
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--bg-card);
  border-radius: var(--radius-md);
  font-size: 0.9rem;
  font-weight: 600;
}

.matchup-side { color: var(--text-muted); }
.matchup-ours { color: var(--primary-color); }
.matchup-vs { color: var(--text-muted); font-size: 0.8rem; font-weight: 400; }

/* ── Players preview ── */
.players-preview {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  padding: var(--spacing-sm) var(--spacing-md);
}

.players-preview-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-sm);
}

.preview-count { font-weight: 700; font-size: 0.85rem; }
.preview-note { font-size: 0.75rem; color: var(--text-muted); }

.players-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: var(--spacing-xs);
}

.player-chip {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  padding: 4px var(--spacing-sm);
  background: var(--bg-light);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  font-size: 0.8rem;
}

.player-chip-number { font-weight: 700; color: var(--secondary-color); flex-shrink: 0; }
.player-chip-name { color: var(--text-light); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* ── Misc ── */
.state-msg { font-size: 0.9rem; padding: var(--spacing-sm) 0; }
.muted { color: var(--text-muted); }
.link { color: var(--primary-color); text-decoration: underline; }

.alert-error {
  background: #fdecea;
  color: var(--error-color);
  padding: var(--spacing-sm) var(--spacing-md);
  border-radius: var(--radius-md);
  font-size: 0.9rem;
  margin-top: var(--spacing-sm);
}

.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }

/* ── Footer ── */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm);
  padding: var(--spacing-md) var(--spacing-lg);
  border-top: 1px solid var(--border-color);
  flex-shrink: 0;
}

.btn {
  padding: var(--spacing-sm) var(--spacing-lg);
  border: none;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  font-size: 0.95rem;
}

.btn:disabled { opacity: 0.45; cursor: not-allowed; }
.btn-primary { background: var(--primary-color); color: white; }
.btn-secondary { background: var(--bg-card); color: var(--text-light); border: 1px solid var(--border-color); }

.btn-close { background: none; border: none; cursor: pointer; font-size: 1rem; color: var(--text-muted); }

[data-theme="terminal"] .modal { border-radius: 0; border: 2px solid var(--border-color); }
[data-theme="terminal"] .roster-option, [data-theme="terminal"] .side-option { border-radius: 0; }
</style>
