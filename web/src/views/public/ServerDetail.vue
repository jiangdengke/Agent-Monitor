<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { NIcon, NButton, NButtonGroup, NSpin } from 'naive-ui'
import {
  ArrowBackOutline,
  HardwareChipOutline,
  LayersOutline,
  FileTrayFullOutline,
  SpeedometerOutline,
  SwapVerticalOutline,
  CloudUploadOutline,
  TimeOutline,
  ServerOutline,
  DesktopOutline,
  GlobeOutline,
  InformationCircleOutline,
  GridOutline,
  ListOutline,
  LogInOutline,
  LogoGithub,
  HeartOutline
} from '@vicons/ionicons5'
import { getPublicAgentDetail, getPublicAgentLatestMetrics } from '../../api/public'
import { useAgentChannel } from '../../composables/useMetricsChannel'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const agent = ref(null)
const metrics = ref({})
const lastUpdateTime = ref('')
let refreshTimer = null

const agentId = computed(() => route.params.id)

const updateLastTime = () => {
  const now = new Date()
  lastUpdateTime.value = now.toLocaleTimeString('zh-CN', { hour12: false })
}

// WebSocket 实时更新
const handleMetricsUpdate = (data) => {
  if (data.agent_id === agentId.value) {
    const m = data.metrics
    metrics.value = {
      ...metrics.value,
      cpu: { ...metrics.value.cpu, usage_percent: m.cpu_usage },
      memory: { ...metrics.value.memory, usage_percent: m.memory_usage },
      disk: { ...metrics.value.disk, usage_percent: m.disk_usage },
      network: {
        ...metrics.value.network,
        bytes_sent_rate: m.network_tx_rate,
        bytes_recv_rate: m.network_rx_rate,
        bytes_sent_total: m.network_tx_total,
        bytes_recv_total: m.network_rx_total,
      },
      load: { ...metrics.value.load, load1: m.load1 },
    }
    updateLastTime()
  }
}

useAgentChannel(agentId.value, handleMetricsUpdate)

const fetchData = async () => {
  loading.value = true
  try {
    const [agentRes, metricsRes] = await Promise.all([
      getPublicAgentDetail(agentId.value),
      getPublicAgentLatestMetrics(agentId.value)
    ])
    agent.value = agentRes
    metrics.value = metricsRes
    updateLastTime()
  } catch (error) {
    console.error('获取数据失败', error)
  } finally {
    loading.value = false
  }
}

const formatBytes = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatSpeed = (bytesPerSec) => {
  if (!bytesPerSec || bytesPerSec === 0) return '0 B/s'
  return formatBytes(bytesPerSec) + '/s'
}

const formatUptime = (seconds) => {
  if (!seconds) return '-'
  const days = Math.floor(seconds / 86400)
  const hours = Math.floor((seconds % 86400) / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  if (days > 0) return `${days}天 ${hours}小时`
  if (hours > 0) return `${hours}小时 ${minutes}分钟`
  return `${minutes}分钟`
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
  refreshTimer = setInterval(fetchData, 30000)
})

onUnmounted(() => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
  }
})
</script>

<template>
  <div class="server-detail-page">
    <!-- 顶部导航 - 与列表页保持一致 -->
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

    <main class="main-content">
      <n-spin :show="loading">
        <!-- 服务器信息卡片 - 与列表页卡片样式一致 -->
        <div class="server-card">
          <!-- 卡片头部 -->
          <div class="card-header">
            <div class="header-left">
              <n-button text class="back-btn" @click="router.push('/')">
                <template #icon>
                  <n-icon><ArrowBackOutline /></n-icon>
                </template>
              </n-button>
              <span class="server-name">{{ agent?.name || agent?.hostname || '服务器详情' }}</span>
              <span :class="['status-tag', agent?.status === 1 ? 'online' : 'offline']">
                <span class="status-dot"></span>
                {{ agent?.status === 1 ? '在线' : '离线' }}
              </span>
            </div>
            <span class="server-os">{{ agent?.os || 'linux' }} · {{ agent?.arch || 'amd64' }}</span>
          </div>

          <!-- 服务器信息行 -->
          <div class="info-row">
            <span v-if="agent?.platform" class="info-item">平台: {{ agent.platform }}</span>
            <span v-if="agent?.location" class="info-item">位置: {{ agent.location }}</span>
            <span v-if="agent?.expire_time" class="info-item expire">
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
              <div class="metric-value">{{ (metrics.cpu?.usage_percent || 0).toFixed(1) }}%</div>
              <div class="metric-bar">
                <div
                  class="metric-bar-fill"
                  :style="{
                    width: Math.min(metrics.cpu?.usage_percent || 0, 100) + '%',
                    backgroundColor: getProgressColor(metrics.cpu?.usage_percent || 0)
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
              <div class="metric-value">{{ (metrics.memory?.usage_percent || 0).toFixed(1) }}%</div>
              <div class="metric-bar">
                <div
                  class="metric-bar-fill"
                  :style="{
                    width: Math.min(metrics.memory?.usage_percent || 0, 100) + '%',
                    backgroundColor: getProgressColor(metrics.memory?.usage_percent || 0)
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
              <div class="metric-value">{{ (metrics.disk?.usage_percent || 0).toFixed(1) }}%</div>
              <div class="metric-bar">
                <div
                  class="metric-bar-fill"
                  :style="{
                    width: Math.min(metrics.disk?.usage_percent || 0, 100) + '%',
                    backgroundColor: getProgressColor(metrics.disk?.usage_percent || 0)
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
              <span class="speed up">↑ {{ formatSpeed(metrics.network?.bytes_sent_rate) }}</span>
              <span class="speed down">↓ {{ formatSpeed(metrics.network?.bytes_recv_rate) }}</span>
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
              <span class="speed up">↑ {{ formatBytes(metrics.network?.bytes_sent_total) }}</span>
              <span class="speed down">↓ {{ formatBytes(metrics.network?.bytes_recv_total) }}</span>
            </div>
          </div>
        </div>

        <!-- 详细信息区域 -->
        <div class="detail-grid">
          <!-- 系统负载 -->
          <div class="detail-card">
            <div class="detail-title">
              <span class="detail-icon load">
                <n-icon><SpeedometerOutline /></n-icon>
              </span>
              系统负载
            </div>
            <div class="load-grid">
              <div class="load-item">
                <span class="load-label">1 分钟</span>
                <span class="load-value">{{ metrics.load?.load1?.toFixed(2) || '0.00' }}</span>
              </div>
              <div class="load-item">
                <span class="load-label">5 分钟</span>
                <span class="load-value">{{ metrics.load?.load5?.toFixed(2) || '0.00' }}</span>
              </div>
              <div class="load-item">
                <span class="load-label">15 分钟</span>
                <span class="load-value">{{ metrics.load?.load15?.toFixed(2) || '0.00' }}</span>
              </div>
            </div>
          </div>

          <!-- 磁盘 IO -->
          <div class="detail-card">
            <div class="detail-title">
              <span class="detail-icon disk">
                <n-icon><FileTrayFullOutline /></n-icon>
              </span>
              磁盘 IO
            </div>
            <div class="io-grid">
              <div class="io-item">
                <span class="io-label">读取速率</span>
                <span class="io-value">{{ formatSpeed(metrics.disk_io?.read_bytes_rate) }}</span>
              </div>
              <div class="io-item">
                <span class="io-label">写入速率</span>
                <span class="io-value">{{ formatSpeed(metrics.disk_io?.write_bytes_rate) }}</span>
              </div>
            </div>
          </div>

          <!-- 内存详情 -->
          <div class="detail-card">
            <div class="detail-title">
              <span class="detail-icon memory">
                <n-icon><LayersOutline /></n-icon>
              </span>
              内存详情
            </div>
            <div class="memory-grid">
              <div class="memory-item">
                <span class="memory-label">已使用</span>
                <span class="memory-value">{{ formatBytes(metrics.memory?.used) }}</span>
              </div>
              <div class="memory-item">
                <span class="memory-label">总容量</span>
                <span class="memory-value">{{ formatBytes(metrics.memory?.total) }}</span>
              </div>
            </div>
          </div>

          <!-- 磁盘详情 -->
          <div class="detail-card">
            <div class="detail-title">
              <span class="detail-icon disk">
                <n-icon><FileTrayFullOutline /></n-icon>
              </span>
              磁盘详情
            </div>
            <div class="disk-grid">
              <div class="disk-item">
                <span class="disk-label">已使用</span>
                <span class="disk-value">{{ formatBytes(metrics.disk?.used) }}</span>
              </div>
              <div class="disk-item">
                <span class="disk-label">总容量</span>
                <span class="disk-value">{{ formatBytes(metrics.disk?.total) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 系统信息 -->
        <div class="server-card system-info">
          <div class="detail-title">
            <span class="detail-icon info">
              <n-icon><InformationCircleOutline /></n-icon>
            </span>
            系统信息
          </div>
          <div class="system-grid">
            <div class="system-item">
              <span class="system-label">
                <n-icon><ServerOutline /></n-icon>
                主机名
              </span>
              <span class="system-value">{{ agent?.hostname || '-' }}</span>
            </div>
            <div class="system-item">
              <span class="system-label">
                <n-icon><GlobeOutline /></n-icon>
                IP 地址
              </span>
              <span class="system-value">{{ agent?.ip_address || '-' }}</span>
            </div>
            <div class="system-item">
              <span class="system-label">
                <n-icon><DesktopOutline /></n-icon>
                操作系统
              </span>
              <span class="system-value">{{ agent?.os || '-' }} {{ agent?.arch || '' }}</span>
            </div>
            <div class="system-item">
              <span class="system-label">
                <n-icon><TimeOutline /></n-icon>
                运行时间
              </span>
              <span class="system-value">{{ formatUptime(metrics.host?.uptime) }}</span>
            </div>
            <div class="system-item">
              <span class="system-label">
                <n-icon><HardwareChipOutline /></n-icon>
                CPU 核心
              </span>
              <span class="system-value">{{ metrics.cpu?.logical_cores || '-' }} 核</span>
            </div>
            <div class="system-item">
              <span class="system-label">
                <n-icon><InformationCircleOutline /></n-icon>
                Agent 版本
              </span>
              <span class="system-value">{{ agent?.version || '-' }}</span>
            </div>
          </div>
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
.server-detail-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f9fafb;
}

/* 顶部导航 - 与列表页一致 */
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

/* 服务器卡片 - 与列表页一致 */
.server-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px 20px;
  margin-bottom: 20px;
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

.back-btn {
  color: #6b7280;
  margin-right: 4px;
}

.back-btn:hover {
  color: #1f2937;
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

/* 详情网格 */
.detail-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.detail-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 16px;
}

.detail-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 12px;
}

.detail-icon {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
}

.detail-icon.load {
  background: #fdf4ff;
  color: #a855f7;
}

.detail-icon.disk {
  background: #fefce8;
  color: #eab308;
}

.detail-icon.memory {
  background: #f0fdf4;
  color: #22c55e;
}

.detail-icon.info {
  background: #eff6ff;
  color: #3b82f6;
}

/* 负载网格 */
.load-grid {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.load-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.load-label {
  font-size: 12px;
  color: #6b7280;
}

.load-value {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

/* IO网格 */
.io-grid {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.io-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.io-label {
  font-size: 12px;
  color: #6b7280;
}

.io-value {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

/* 内存网格 */
.memory-grid {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.memory-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.memory-label {
  font-size: 12px;
  color: #6b7280;
}

.memory-value {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

/* 磁盘网格 */
.disk-grid {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.disk-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.disk-label {
  font-size: 12px;
  color: #6b7280;
}

.disk-value {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

/* 系统信息卡片 */
.system-info {
  margin-bottom: 0;
}

.system-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.system-item {
  background: #f9fafb;
  border: 1px solid #f3f4f6;
  border-radius: 8px;
  padding: 12px;
}

.system-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 6px;
}

.system-value {
  font-size: 14px;
  font-weight: 500;
  color: #1f2937;
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
@media (max-width: 1024px) {
  .detail-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .system-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

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

  .detail-grid {
    grid-template-columns: 1fr;
  }

  .system-grid {
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
