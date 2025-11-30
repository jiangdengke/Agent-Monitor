<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { NIcon, NButton, NButtonGroup, NSpin } from 'naive-ui'
import {
  DesktopOutline,
  ServerOutline,
  SpeedometerOutline,
  HardwareChipOutline,
  CloudUploadOutline,
  SwapVerticalOutline,
  GridOutline,
  ListOutline,
  LogInOutline,
  LogoGithub,
  FileTrayFullOutline,
  LayersOutline,
  HeartOutline
} from '@vicons/ionicons5'
import { getPublicAgentList } from '../../api/public'
import { useMetricsChannel, useAgentsChannel } from '../../composables/useMetricsChannel'

const router = useRouter()

const loading = ref(false)
const agents = ref([])
const viewMode = ref('grid')
const lastUpdateTime = ref('')

const updateLastTime = () => {
  const now = new Date()
  lastUpdateTime.value = now.toLocaleTimeString('zh-CN', { hour12: false })
}

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
    updateLastTime()
  }
}

const handleStatusChange = (data) => {
  const index = agents.value.findIndex(a => a.id === data.agent_id)
  if (index !== -1) {
    agents.value[index].status = data.status
  }
}

useMetricsChannel(handleMetricsUpdate)
useAgentsChannel(handleStatusChange)

const fetchData = async () => {
  loading.value = true
  try {
    const listRes = await getPublicAgentList({ pageSize: 100 })
    agents.value = listRes.items || []
    updateLastTime()
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
  if (percent >= 90) return '#ef4444'
  if (percent >= 70) return '#f59e0b'
  return '#22c55e'
}

const formatExpireDate = (timestamp) => {
  if (!timestamp) return ''
  const date = new Date(timestamp)
  return `${date.getFullYear()}/${date.getMonth() + 1}/${date.getDate()}`
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <div class="server-list-page">
    <!-- 顶部导航 -->
    <header class="header">
      <div class="header-inner">
        <div class="logo">
          <span class="logo-icon">
            <n-icon size="28"><ServerOutline /></n-icon>
          </span>
          <div class="logo-text">
            <span class="logo-subtitle">SERVER MONITOR</span>
            <span class="logo-title">设备监控</span>
          </div>
        </div>

        <div class="header-right">
          <div class="last-update">
            <span class="update-dot"></span>
            最后更新: {{ lastUpdateTime }}
          </div>

          <n-button-group size="small">
            <n-button
              :type="viewMode === 'grid' ? 'primary' : 'default'"
              @click="viewMode = 'grid'"
            >
              <template #icon>
                <n-icon><GridOutline /></n-icon>
              </template>
            </n-button>
            <n-button
              :type="viewMode === 'list' ? 'primary' : 'default'"
              @click="viewMode = 'list'"
            >
              <template #icon>
                <n-icon><ListOutline /></n-icon>
              </template>
            </n-button>
          </n-button-group>

          <nav class="nav-links">
            <a href="/" class="nav-link active">
              <n-icon><DesktopOutline /></n-icon>
              设备监控
            </a>
            <a href="/monitors" class="nav-link">
              <n-icon><SpeedometerOutline /></n-icon>
              服务监控
            </a>
          </nav>

          <a href="https://github.com" target="_blank" class="github-link">
            <n-icon size="20"><LogoGithub /></n-icon>
          </a>

          <n-button type="primary" size="small" @click="router.push('/login')">
            <template #icon>
              <n-icon><LogInOutline /></n-icon>
            </template>
            登录
          </n-button>
        </div>
      </div>
    </header>

    <!-- 主内容区 -->
    <main class="main-content">
      <n-spin :show="loading">
        <div :class="['server-grid', viewMode]">
          <div
            v-for="agent in agents"
            :key="agent.id"
            class="server-card"
            @click="goToDetail(agent.id)"
          >
            <!-- 卡片头部 -->
            <div class="card-header">
              <div class="header-left">
                <span class="server-name">{{ agent.name || agent.hostname }}</span>
                <span :class="['status-tag', agent.status === 1 ? 'online' : 'offline']">
                  <span class="status-dot"></span>
                  {{ agent.status === 1 ? '在线' : '离线' }}
                </span>
              </div>
              <span class="server-os">{{ agent.os || 'linux' }} · {{ agent.arch || 'amd64' }}</span>
            </div>

            <!-- 服务器信息行 -->
            <div class="info-row">
              <span v-if="agent.platform" class="info-item">平台: {{ agent.platform }}</span>
              <span v-if="agent.location" class="info-item">位置: {{ agent.location }}</span>
              <span v-if="agent.expire_time" class="info-item expire">
                到期: {{ formatExpireDate(agent.expire_time) }}
              </span>
            </div>

            <!-- 资源指标 - 三列布局 -->
            <div class="metrics-row">
              <div class="metric-card">
                <div class="metric-header">
                  <span class="metric-icon cpu">
                    <n-icon><HardwareChipOutline /></n-icon>
                  </span>
                  <span class="metric-label">CPU</span>
                </div>
                <div class="metric-value">{{ (agent.cpu_usage || 0).toFixed(1) }}%</div>
                <div class="metric-bar">
                  <div
                    class="metric-bar-fill"
                    :style="{
                      width: Math.min(agent.cpu_usage || 0, 100) + '%',
                      backgroundColor: getProgressColor(agent.cpu_usage || 0)
                    }"
                  ></div>
                </div>
              </div>

              <div class="metric-card">
                <div class="metric-header">
                  <span class="metric-icon memory">
                    <n-icon><LayersOutline /></n-icon>
                  </span>
                  <span class="metric-label">内存</span>
                </div>
                <div class="metric-value">{{ (agent.memory_usage || 0).toFixed(1) }}%</div>
                <div class="metric-bar">
                  <div
                    class="metric-bar-fill"
                    :style="{
                      width: Math.min(agent.memory_usage || 0, 100) + '%',
                      backgroundColor: getProgressColor(agent.memory_usage || 0)
                    }"
                  ></div>
                </div>
              </div>

              <div class="metric-card">
                <div class="metric-header">
                  <span class="metric-icon disk">
                    <n-icon><FileTrayFullOutline /></n-icon>
                  </span>
                  <span class="metric-label">磁盘</span>
                </div>
                <div class="metric-value">{{ (agent.disk_usage || 0).toFixed(1) }}%</div>
                <div class="metric-bar">
                  <div
                    class="metric-bar-fill"
                    :style="{
                      width: Math.min(agent.disk_usage || 0, 100) + '%',
                      backgroundColor: getProgressColor(agent.disk_usage || 0)
                    }"
                  ></div>
                </div>
              </div>
            </div>

            <!-- 网络速率 -->
            <div class="network-card">
              <div class="network-left">
                <span class="network-icon">
                  <n-icon><SwapVerticalOutline /></n-icon>
                </span>
                <span class="network-label">实时速率</span>
              </div>
              <div class="network-right">
                <span class="speed up">↑ {{ formatSpeed(agent.network_tx_rate) }}</span>
                <span class="speed down">↓ {{ formatSpeed(agent.network_rx_rate) }}</span>
              </div>
            </div>

            <!-- 累计流量 -->
            <div class="network-card">
              <div class="network-left">
                <span class="network-icon">
                  <n-icon><CloudUploadOutline /></n-icon>
                </span>
                <span class="network-label">累计流量</span>
              </div>
              <div class="network-right">
                <span class="speed up">↑ {{ formatBytes(agent.network_tx_total) }}</span>
                <span class="speed down">↓ {{ formatBytes(agent.network_rx_total) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div v-if="!loading && agents.length === 0" class="empty-state">
          <n-icon size="48" color="#ccc"><ServerOutline /></n-icon>
          <p>暂无服务器数据</p>
        </div>
      </n-spin>
    </main>

    <!-- 底部 -->
    <footer class="footer">
      <span>© 2025 Server Monitor · 保持洞察，稳定运行</span>
      <span class="footer-right">
        用 <n-icon color="#f59e0b" :size="14"><HeartOutline /></n-icon> 构建
      </span>
    </footer>
  </div>
</template>

<style scoped>
.server-list-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f9fafb;
}

/* 顶部导航 */
.header {
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-inner {
  max-width: 1400px;
  margin: 0 auto;
  padding: 12px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.logo {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo-icon {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #3b82f6;
  color: #fff;
  border-radius: 8px;
}

.logo-text {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.logo-subtitle {
  font-size: 10px;
  color: #f59e0b;
  font-weight: 600;
  letter-spacing: 1.5px;
}

.logo-title {
  font-size: 16px;
  font-weight: 700;
  color: #1f2937;
  margin-top: -2px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.last-update {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #6b7280;
}

.update-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #22c55e;
}

.nav-links {
  display: flex;
  gap: 4px;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  font-size: 13px;
  color: #6b7280;
  text-decoration: none;
  border-radius: 6px;
  border: 1px solid transparent;
  transition: all 0.2s;
}

.nav-link:hover {
  color: #1f2937;
  background: #f3f4f6;
}

.nav-link.active {
  color: #1f2937;
  border-color: #e5e7eb;
  background: #fff;
}

.github-link {
  color: #6b7280;
  display: flex;
  align-items: center;
}

.github-link:hover {
  color: #1f2937;
}

/* 主内容 */
.main-content {
  flex: 1;
  max-width: 1400px;
  width: 100%;
  margin: 0 auto;
  padding: 24px;
}

/* 服务器卡片网格 */
.server-grid {
  display: grid;
  gap: 20px;
}

.server-grid.grid {
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
}

.server-grid.list {
  grid-template-columns: 1fr;
}

/* 服务器卡片 */
.server-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px 20px;
  cursor: pointer;
  transition: all 0.2s;
}

.server-card:hover {
  border-color: #d1d5db;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

/* 卡片头部 */
.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.server-name {
  font-size: 15px;
  font-weight: 600;
  color: #1f2937;
}

.status-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  font-weight: 500;
  padding: 2px 8px;
  border-radius: 4px;
}

.status-tag.online {
  color: #16a34a;
  background: #f0fdf4;
}

.status-tag.offline {
  color: #dc2626;
  background: #fef2f2;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.status-tag.online .status-dot {
  background: #22c55e;
}

.status-tag.offline .status-dot {
  background: #ef4444;
}

.server-os {
  font-size: 13px;
  color: #2563eb;
  font-weight: 500;
}

/* 信息行 */
.info-row {
  display: flex;
  gap: 16px;
  font-size: 12px;
  color: #9ca3af;
  margin-bottom: 14px;
}

.info-item.expire {
  color: #f59e0b;
}

/* 资源指标行 */
.metrics-row {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
}

.metric-card {
  flex: 1;
  background: #f9fafb;
  border: 1px solid #f3f4f6;
  border-radius: 8px;
  padding: 10px 12px;
}

.metric-header {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 6px;
}

.metric-icon {
  width: 22px;
  height: 22px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
}

.metric-icon.cpu {
  background: #eff6ff;
  color: #3b82f6;
}

.metric-icon.memory {
  background: #f0fdf4;
  color: #22c55e;
}

.metric-icon.disk {
  background: #fefce8;
  color: #eab308;
}

.metric-label {
  font-size: 12px;
  color: #6b7280;
}

.metric-value {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 6px;
}

.metric-bar {
  height: 4px;
  background: #e5e7eb;
  border-radius: 2px;
  overflow: hidden;
}

.metric-bar-fill {
  height: 100%;
  border-radius: 2px;
  transition: width 0.3s ease;
}

/* 网络卡片 */
.network-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f9fafb;
  border: 1px solid #f3f4f6;
  border-radius: 8px;
  padding: 10px 12px;
  margin-bottom: 8px;
}

.network-card:last-child {
  margin-bottom: 0;
}

.network-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.network-icon {
  width: 22px;
  height: 22px;
  border-radius: 4px;
  background: #fef3c7;
  color: #f59e0b;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
}

.network-label {
  font-size: 12px;
  color: #6b7280;
}

.network-right {
  display: flex;
  gap: 12px;
}

.speed {
  font-size: 12px;
  font-weight: 500;
}

.speed.up {
  color: #3b82f6;
}

.speed.down {
  color: #22c55e;
}

/* 空状态 */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #9ca3af;
}

.empty-state p {
  margin-top: 12px;
  font-size: 14px;
}

/* 底部 */
.footer {
  background: #fff;
  border-top: 1px solid #e5e7eb;
  padding: 16px 24px;
  display: flex;
  justify-content: space-between;
  font-size: 13px;
  color: #9ca3af;
}

.footer-right {
  color: #f59e0b;
}

/* 响应式 */
@media (max-width: 768px) {
  .header-inner {
    flex-direction: column;
    gap: 12px;
  }

  .header-right {
    flex-wrap: wrap;
    justify-content: center;
  }

  .nav-links {
    display: none;
  }

  .server-grid.grid {
    grid-template-columns: 1fr;
  }

  .metrics-row {
    flex-wrap: wrap;
  }

  .metric-card {
    min-width: calc(50% - 5px);
  }

  .main-content {
    padding: 16px;
  }
}
</style>
