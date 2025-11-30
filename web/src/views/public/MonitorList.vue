<script setup>
import { ref, onMounted } from 'vue'
import { NCard, NGrid, NGridItem, NTag, NStatistic, NSpin } from 'naive-ui'
import PublicHeader from '../../components/PublicHeader.vue'
import PublicFooter from '../../components/PublicFooter.vue'
import { getPublicMonitorList, getPublicMonitorOverview } from '../../api/public'

const loading = ref(false)
const monitors = ref([])
const overview = ref({})

const fetchData = async () => {
  loading.value = true
  try {
    const [listRes, overviewRes] = await Promise.all([
      getPublicMonitorList({ pageSize: 100 }),
      getPublicMonitorOverview()
    ])
    monitors.value = listRes.items || []
    overview.value = overviewRes
  } catch (error) {
    console.error('获取数据失败', error)
  } finally {
    loading.value = false
  }
}

const getUptimeColor = (uptime) => {
  if (uptime >= 99) return '#18a058'
  if (uptime >= 95) return '#f0a020'
  return '#d03050'
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <div class="monitor-list-page">
    <PublicHeader />

    <main class="main-content">
      <n-grid :cols="4" :x-gap="16" :y-gap="16" responsive="screen" class="stats-grid">
        <n-grid-item>
          <n-card class="stat-card">
            <n-statistic label="监控总数" :value="overview.total_tasks || 0" />
          </n-card>
        </n-grid-item>
        <n-grid-item>
          <n-card class="stat-card">
            <n-statistic label="已启用" :value="overview.enabled_tasks || 0" />
          </n-card>
        </n-grid-item>
        <n-grid-item>
          <n-card class="stat-card">
            <n-statistic label="24h 可用率">
              <template #default>
                <span :style="{ color: getUptimeColor(overview.overall_uptime_24h || 0) }">
                  {{ (overview.overall_uptime_24h || 0).toFixed(1) }}%
                </span>
              </template>
            </n-statistic>
          </n-card>
        </n-grid-item>
        <n-grid-item>
          <n-card class="stat-card">
            <n-statistic label="平均响应" :value="overview.avg_response_time_24h || 0">
              <template #suffix>ms</template>
            </n-statistic>
          </n-card>
        </n-grid-item>
      </n-grid>

      <n-spin :show="loading">
        <n-grid :cols="3" :x-gap="16" :y-gap="16" responsive="screen" class="monitor-grid">
          <n-grid-item v-for="monitor in monitors" :key="monitor.id">
            <n-card class="monitor-card" hoverable>
              <div class="card-header">
                <span class="name">{{ monitor.name }}</span>
                <n-tag :type="monitor.enabled ? 'success' : 'warning'" size="small">
                  {{ monitor.enabled ? '运行中' : '已暂停' }}
                </n-tag>
              </div>
              <div class="card-content">
                <div class="target">{{ monitor.target }}</div>
                <div class="meta">
                  <n-tag size="small" :bordered="false">{{ monitor.type.toUpperCase() }}</n-tag>
                  <span class="interval">间隔 {{ monitor.interval }}s</span>
                </div>
                <div v-if="monitor.uptime_24h !== undefined" class="uptime">
                  <span class="uptime-label">24h 可用率</span>
                  <span
                    class="uptime-value"
                    :style="{ color: getUptimeColor(monitor.uptime_24h || 0) }"
                  >
                    {{ (monitor.uptime_24h || 0).toFixed(1) }}%
                  </span>
                </div>
              </div>
            </n-card>
          </n-grid-item>
        </n-grid>

        <div v-if="!loading && monitors.length === 0" class="empty-state">
          <p>暂无监控任务</p>
        </div>
      </n-spin>
    </main>

    <PublicFooter />
  </div>
</template>

<style scoped>
.monitor-list-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f8fafc;
}

.main-content {
  flex: 1;
  max-width: 1280px;
  width: 100%;
  margin: 0 auto;
  padding: 24px;
}

.stats-grid {
  margin-bottom: 24px;
}

.stat-card {
  border-radius: 12px;
}

.monitor-grid {
  margin-bottom: 24px;
}

.monitor-card {
  border-radius: 12px;
  transition: all 0.2s;
}

.monitor-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.name {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
}

.card-content .target {
  font-size: 13px;
  color: #64748b;
  margin-bottom: 12px;
  word-break: break-all;
}

.meta {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.interval {
  font-size: 12px;
  color: #94a3b8;
}

.uptime {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 12px;
  border-top: 1px solid #f1f5f9;
}

.uptime-label {
  font-size: 12px;
  color: #94a3b8;
}

.uptime-value {
  font-size: 14px;
  font-weight: 600;
}

.empty-state {
  text-align: center;
  padding: 48px;
  color: #94a3b8;
}

@media (max-width: 768px) {
  .main-content {
    padding: 16px;
  }
}
</style>
