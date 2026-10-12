<template>
  <div class="timing-panel" id="timing-comparison">
    <div class="timing-header">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
      </svg>
      <span>Search Engine Timing</span>
    </div>
    <p class="timing-note">Elasticsearch shows server-reported execution time (took). MySQL shows API-side elapsed time.</p>

    <!-- MySQL row -->
    <div v-if="mysqlMs != null" class="timing-row">
      <div class="timing-label">
        <span class="engine-dot engine-dot--mysql"></span>
        <span>MySQL FULLTEXT</span>
        <span class="timing-method">{{ method || 'MATCH' }}</span>
      </div>
      <div class="timing-bar-wrap">
        <div
          class="timing-bar timing-bar--mysql"
          :style="{ width: mysqlWidth + '%' }"
          :title="mysqlMs + 'ms'"
        >
          <span class="timing-bar-label">{{ mysqlMs != null ? mysqlMs + ' ms' : '—' }}</span>
        </div>
      </div>
      <div class="timing-value timing-value--mysql">{{ mysqlMs != null ? mysqlMs + ' ms' : '—' }}</div>
    </div>

    <!-- ES row -->
    <div v-if="esRequested" class="timing-row">
      <div class="timing-label">
        <span class="engine-dot engine-dot--es"></span>
        <span>Elasticsearch took</span>
        <span v-if="esAvailable === false" class="timing-offline">offline</span>
      </div>
      <div class="timing-bar-wrap">
        <div
          v-if="esMs != null"
          class="timing-bar timing-bar--es"
          :style="{ width: esWidth + '%' }"
          :title="esMs + 'ms'"
        >
          <span class="timing-bar-label">{{ esMs + ' ms' }}</span>
        </div>
        <div v-else class="timing-bar-empty">
          {{ esAvailable === false ? 'Offline — start Elasticsearch to compare' : 'No timing data yet' }}
        </div>
      </div>
      <div class="timing-value timing-value--es">{{ esMs != null ? esMs + ' ms' : '—' }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'

const store = useStore()
const timing = computed(() => store.state.search.timing)

const mysqlMs    = computed(() => timing.value.mysql_ms)
const esMs       = computed(() => timing.value.es_ms)
const esAvailable = computed(() => timing.value.es_available)
const esRequested = computed(() => timing.value.es_requested)
const method     = computed(() => timing.value.mysql_method)

const maxMs = computed(() => Math.max(mysqlMs.value ?? 0, esMs.value ?? 0, 1))

const mysqlWidth = computed(() => mysqlMs.value != null ? Math.min(100, (mysqlMs.value / maxMs.value) * 100) : 0)
const esWidth    = computed(() => esMs.value    != null ? Math.min(100, (esMs.value    / maxMs.value) * 100) : 0)

</script>

<style scoped>
.timing-panel {
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--space-4) var(--space-5);
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.timing-header {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.timing-note {
  margin: calc(var(--space-3) * -1) 0 0;
  font-size: 11px;
  line-height: 1.4;
  color: var(--color-text-faint);
}

.timing-row {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.timing-label {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  width: 180px;
  flex-shrink: 0;
  font-size: 13px;
  font-weight: 500;
  color: var(--color-text-muted);
}

.engine-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.engine-dot--mysql { background: var(--color-mysql); box-shadow: 0 0 6px var(--color-mysql); }
.engine-dot--es    { background: var(--color-es);    box-shadow: 0 0 6px var(--color-es); }

.timing-method {
  font-family: var(--font-mono);
  font-size: 10px;
  color: var(--color-text-faint);
  background: var(--color-surface-3);
  padding: 1px 5px;
  border-radius: 3px;
}

.timing-offline {
  font-size: 10px;
  color: var(--color-warning);
  background: var(--color-mysql-dim);
  padding: 1px 5px;
  border-radius: 3px;
}

.timing-bar-wrap {
  flex: 1;
  background: var(--color-surface-3);
  border-radius: var(--radius-full);
  height: 24px;
  overflow: hidden;
  position: relative;
}

.timing-bar {
  height: 100%;
  border-radius: var(--radius-full);
  min-width: 40px;
  display: flex;
  align-items: center;
  padding-right: var(--space-2);
  justify-content: flex-end;
  animation: bar-grow 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
  position: relative;
  overflow: hidden;
}

.timing-bar::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.12) 100%);
}

.timing-bar--mysql {
  background: linear-gradient(90deg, #b45309, var(--color-mysql));
}

.timing-bar--es {
  background: linear-gradient(90deg, #065f46, var(--color-es));
}

.timing-bar-label {
  font-family: var(--font-mono);
  font-size: 10px;
  font-weight: 600;
  color: rgba(255,255,255,0.85);
  position: relative;
  z-index: 1;
}

.timing-bar-empty {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  font-size: 12px;
  color: var(--color-text-faint);
  padding: 0 var(--space-3);
}

.timing-value {
  width: 64px;
  text-align: right;
  font-family: var(--font-mono);
  font-size: 13px;
  font-weight: 600;
  flex-shrink: 0;
}

.timing-value--mysql { color: var(--color-mysql); }
.timing-value--es    { color: var(--color-es); }
</style>
