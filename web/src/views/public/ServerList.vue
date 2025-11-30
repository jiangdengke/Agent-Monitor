<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { NCard, NGrid, NGridItem, NTag, NProgress, NSpin } from 'naive-ui'
import PublicHeader from '../../components/PublicHeader.vue'
import PublicFooter from '../../components/PublicFooter.vue'
import { getPublicAgentList, getPublicAgentStatistics } from '../../api/public'
import { useMetricsChannel, useAgentsChannel } from '../../composables/useMetricsChannel'

const router = useRouter()

const loading = ref(false)
const agents = ref([])
const statistics = ref({ total: 0, online: 0, offline: 0 })
const viewMode = ref('grid')

// WebSocket 实时更新
const handleMetricsUpdate = (data) => {
  const index = agents.value.findIndex(a => a.id === data.agent_id)
  if (index !== -1) {
    const metrics = data.metrics
    agents.value[index] = {
      ...agents.value[index],
      cpu_usage: metrics.cpu_usage,
      memory_usage: metrics.memory_usage,
      disk_usage: metrics.disk_usage,
      network_tx_rate: metrics.network_tx_rate,
      network_rx_rate: metrics.network_rx_rate,
      network_tx_total: metrics.network_tx_total,
      network_rx_total: metrics.network_rx_total,
    }
  }
}

const handleStatusChange = (data) => {
  const index = agents.value.findIndex(a => a.id === data.agent_id)
  if (index !== -1) {
    agents.value[index].status = data.status
  }
  // 更新统计
  fetchStatistics()
}

// 订阅 WebSocket 频道
useMetricsChannel(handleMetricsUpdate)
useAgentsChannel(handleStatusChange)

const fetchStatistics = async () => {
  try {
    const statsRes = await getPublicAgentStatistics()
    statistics.value = statsRes
  } catch (error) {
    console.error('获取统计数据失败', error)
  }
}

const fetchData = async () => {
  loading.value = true
  try {
    const [listRes, statsRes] = await Promise.all([
      getPublicAgentList({ pageSize: 100 }),
      getPublicAgentStatistics()
    ])
    agents.value = listRes.items || []
    statistics.value = statsRes
  } catch (error) {
    console.error('获取数据失败', error)
  } finally {
    loading.value = false
  }
}

const goToDetail = (id) => {
  router.push(`/servers/${id}`)
}

const formatBytes = (bytes) => {
  if (!bytes || bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatSpeed = (bytesPerSec) => {
  if (!bytesPerSec || bytesPerSec === 0) return '0 B/s'
  return formatBytes(bytesPerSec) + '/s'
}

const getProgressColor = (percent) => {
  if (percent >= 90) return '#d03050'
  if (percent >= 70) return '#f0a020'
  return '#18a058'
}

const gridCols = computed(() => {
  return viewMode.value === 'grid' ? 3 : 1
})

onMounted(() => {
  fetchData()
})
</script>

<template>
  <div class="server-list-page">
    <PublicHeader v-model:viewMode="viewMode" :showViewToggle="true" />

    <main class="main-content">
      <div class="stats-bar">
        <div class="stat-item">
          <span class="stat-value">{{ statistics.total }}</span>
          <span class="stat-label">服务器总数</span>
        </div>
        <div class="stat-item">
          <span class="stat-value online">{{ statistics.online }}</span>
          <span class="stat-label">在线</span>
        </div>
        <div class="stat-item">
          <span class="stat-value offline">{{ statistics.offline }}</span>
          <span class="stat-label">离线</span>
        </div>
      </div>

      <n-spin :show="loading">
        <n-grid :cols="gridCols" :x-gap="16" :y-gap="16" responsive="screen" class="server-grid">
          <n-grid-item v-for="agent in agents" :key="agent.id">
            <n-card hoverable :class="['server-card', viewMode]" @click="goToDetail(agent.id)">
              <div class="card-header">
                <div class="server-info">
                  <span class="hostname">{{ agent.hostname || agent.id }}</span>
                  <span class="location" v-if="agent.location">{{ agent.location }}</span>
                </div>
                <n-tag :type="agent.status === 1 ? 'success' : 'error'" size="small">
                  {{ agent.status === 1 ? '在线' : '离线' }}
                </n-tag>
              </div>

              <div class="card-body">
                <div class="metrics-section">
                  <div class="metric-row">
                    <div class="metric-item">
                      <div class="metric-header">
                        <span class="metric-label">CPU</span>
                        <span class="metric-value">{{ (agent.cpu_usage || 0).toFixed(1) }}%</span>
                      </div>
                      <n-progress
                        type="line"
                        :percentage="agent.cpu_usage || 0"
                        :height="6"
                        :border-radius="3"
                        :fill-border-radius="3"
                        :color="getProgressColor(agent.cpu_usage || 0)"
                        :show-indicator="false"
                      />
                    </div>
                    <div class="metric-item">
                      <div class="metric-header">
                        <span class="metric-label">内存</span>
                        <span class="metric-value">{{ (agent.memory_usage || 0).toFixed(1) }}%</span>
                      </div>
                      <n-progress
                        type="line"
                        :percentage="agent.memory_usage || 0"
                        :height="6"
                        :border-radius="3"
                        :fill-border-radius="3"
                        :color="getProgressColor(agent.memory_usage || 0)"
                        :show-indicator="false"
                      />
                    </div>
                    <div class="metric-item">
                      <div class="metric-header">
                        <span class="metric-label">磁盘</span>
                        <span class="metric-value">{{ (agent.disk_usage || 0).toFixed(1) }}%</span>
                      </div>
                      <n-progress
                        type="line"
                        :percentage="agent.disk_usage || 0"
                        :height="6"
                        :border-radius="3"
                        :fill-border-radius="3"
                        :color="getProgressColor(agent.disk_usage || 0)"
                        :show-indicator="false"
                      />
                    </div>
                  </div>
                </div>

                <div class="network-section">
                  <div class="network-speed">
                    <span class="speed-item">
                      <span class="arrow up">↑</span>
                      {{ formatSpeed(agent.network_tx_rate) }}
                    </span>
                    <span class="speed-item">
                      <span class="arrow down">↓</span>
                      {{ formatSpeed(agent.network_rx_rate) }}
                    </span>
                  </div>
                  <div class="network-total">
                    <span class="total-item">
                      累计 ↑ {{ formatBytes(agent.network_tx_total) }}
                    </span>
                    <span class="total-item">
                      累计 ↓ {{ formatBytes(agent.network_rx_total) }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="card-footer">
                <span class="ip">{{ agent.ip_address || '-' }}</span>
                <span class="os">{{ agent.os || '-' }}</span>
              </div>
            </n-card>
          </n-grid-item>
        </n-grid>

        <div v-if="!loading && agents.length === 0" class="empty-state">
          <p>暂无服务器数据</p>
        </div>
      </n-spin>
    </main>

    <PublicFooter />
  </div>
</template>

<style scoped>
.server-list-page {
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

.stats-bar {
  display: flex;
  gap: 32px;
  margin-bottom: 24px;
  padding: 16px 24px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-value {
  font-size: 28px;
  font-weight: 600;
  color: #1e293b;
}

.stat-value.online {
  color: #18a058;
}

.stat-value.offline {
  color: #d03050;
}

.stat-label {
  font-size: 13px;
  color: #64748b;
  margin-top: 4px;
}

.server-grid {
  margin-bottom: 24px;
}

.server-card {
  cursor: pointer;
  transition: all 0.2s;
  border-radius: 12px;
}

.server-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.server-card.list {
  display: flex;
  flex-direction: row;
  align-items: center;
}

.server-card.list .card-body {
  flex: 1;
  display: flex;
  flex-direction: row;
  gap: 24px;
}

.server-card.list .metrics-section {
  flex: 1;
}

.server-card.list .network-section {
  flex: 1;
  margin-top: 0;
  padding-top: 0;
  border-top: none;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
}

.server-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.hostname {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
}

.location {
  font-size: 12px;
  color: #94a3b8;
}

.card-body {
  margin-bottom: 16px;
}

.metrics-section .metric-row {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.metric-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.metric-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.metric-label {
  font-size: 12px;
  color: #64748b;
}

.metric-value {
  font-size: 12px;
  font-weight: 500;
  color: #1e293b;
}

.network-section {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.network-speed {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.speed-item {
  font-size: 13px;
  color: #475569;
}

.arrow {
  font-weight: 600;
}

.arrow.up {
  color: #3b82f6;
}

.arrow.down {
  color: #10b981;
}

.network-total {
  display: flex;
  justify-content: space-between;
}

.total-item {
  font-size: 12px;
  color: #94a3b8;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #94a3b8;
  padding-top: 12px;
  border-top: 1px solid #f1f5f9;
}

.empty-state {
  text-align: center;
  padding: 48px;
  color: #94a3b8;
}

@media (max-width: 768px) {
  .stats-bar {
    gap: 16px;
    padding: 12px 16px;
  }

  .stat-value {
    font-size: 24px;
  }

  .main-content {
    padding: 16px;
  }
}
</style>
