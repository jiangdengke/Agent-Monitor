<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { NCard, NGrid, NGridItem, NTag, NProgress, NDescriptions, NDescriptionsItem, NButton, NSpace } from 'naive-ui'
import { getPublicAgentDetail, getPublicAgentLatestMetrics } from '../../api/public'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const agent = ref(null)
const metrics = ref({})
let refreshTimer = null

const fetchData = async () => {
  loading.value = true
  try {
    const agentId = route.params.id
    const [agentRes, metricsRes] = await Promise.all([
      getPublicAgentDetail(agentId),
      getPublicAgentLatestMetrics(agentId)
    ])
    agent.value = agentRes
    metrics.value = metricsRes
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

const formatUptime = (seconds) => {
  if (!seconds) return '-'
  const days = Math.floor(seconds / 86400)
  const hours = Math.floor((seconds % 86400) / 3600)
  if (days > 0) return `${days}天 ${hours}小时`
  return `${hours}小时`
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
  <div class="server-detail">
    <header class="header">
      <div class="header-content">
        <n-space align="center">
          <n-button text style="color: #fff" @click="router.push('/')">← 返回</n-button>
          <h1>{{ agent?.hostname || '服务器详情' }}</h1>
          <n-tag :type="agent?.status === 1 ? 'success' : 'error'" size="small">
            {{ agent?.status === 1 ? '在线' : '离线' }}
          </n-tag>
        </n-space>
      </div>
    </header>

    <main class="main">
      <n-grid :cols="4" :x-gap="16" class="metrics-grid">
        <n-grid-item>
          <n-card>
            <div class="metric-card">
              <span class="metric-label">CPU 使用率</span>
              <n-progress
                type="circle"
                :percentage="metrics.cpu?.usage_percent || 0"
                :stroke-width="10"
              />
            </div>
          </n-card>
        </n-grid-item>
        <n-grid-item>
          <n-card>
            <div class="metric-card">
              <span class="metric-label">内存使用率</span>
              <n-progress
                type="circle"
                :percentage="metrics.memory?.usage_percent || 0"
                :stroke-width="10"
              />
            </div>
          </n-card>
        </n-grid-item>
        <n-grid-item>
          <n-card>
            <div class="metric-card">
              <span class="metric-label">磁盘使用率</span>
              <n-progress
                type="circle"
                :percentage="metrics.disk?.usage_percent || 0"
                :stroke-width="10"
              />
            </div>
          </n-card>
        </n-grid-item>
        <n-grid-item>
          <n-card>
            <div class="metric-card">
              <span class="metric-label">系统负载</span>
              <div class="metric-value">{{ metrics.load?.load1?.toFixed(2) || '-' }}</div>
            </div>
          </n-card>
        </n-grid-item>
      </n-grid>

      <n-card title="系统信息" class="info-card">
        <n-descriptions :column="2" bordered>
          <n-descriptions-item label="主机名">{{ agent?.hostname || '-' }}</n-descriptions-item>
          <n-descriptions-item label="IP 地址">{{ agent?.ip_address || '-' }}</n-descriptions-item>
          <n-descriptions-item label="操作系统">{{ agent?.os || '-' }}</n-descriptions-item>
          <n-descriptions-item label="系统版本">{{ agent?.os_version || '-' }}</n-descriptions-item>
          <n-descriptions-item label="CPU 核心">{{ metrics.cpu?.logical_cores || '-' }} 核</n-descriptions-item>
          <n-descriptions-item label="总内存">{{ formatBytes(metrics.memory?.total) }}</n-descriptions-item>
          <n-descriptions-item label="运行时间">{{ formatUptime(metrics.host?.uptime) }}</n-descriptions-item>
          <n-descriptions-item label="Agent 版本">{{ agent?.version || '-' }}</n-descriptions-item>
        </n-descriptions>
      </n-card>

      <n-grid :cols="2" :x-gap="16" class="detail-grid">
        <n-grid-item>
          <n-card title="网络流量">
            <n-descriptions :column="1" bordered>
              <n-descriptions-item label="发送速率">{{ formatBytes(metrics.network?.bytes_sent_rate) }}/s</n-descriptions-item>
              <n-descriptions-item label="接收速率">{{ formatBytes(metrics.network?.bytes_recv_rate) }}/s</n-descriptions-item>
            </n-descriptions>
          </n-card>
        </n-grid-item>
        <n-grid-item>
          <n-card title="磁盘 IO">
            <n-descriptions :column="1" bordered>
              <n-descriptions-item label="读取速率">{{ formatBytes(metrics.disk_io?.read_bytes_rate) }}/s</n-descriptions-item>
              <n-descriptions-item label="写入速率">{{ formatBytes(metrics.disk_io?.write_bytes_rate) }}/s</n-descriptions-item>
            </n-descriptions>
          </n-card>
        </n-grid-item>
      </n-grid>
    </main>
  </div>
</template>

<style scoped>
.server-detail {
  min-height: 100vh;
  background: #f5f7f9;
}

.header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 24px 0;
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

.header h1 {
  color: #fff;
  margin: 0;
  font-size: 24px;
}

.main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px;
}

.metrics-grid {
  margin-bottom: 16px;
}

.metric-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px 0;
}

.metric-label {
  font-size: 14px;
  color: #666;
  margin-bottom: 16px;
}

.metric-value {
  font-size: 32px;
  font-weight: 600;
  color: #333;
}

.info-card {
  margin-bottom: 16px;
}

.detail-grid {
  margin-top: 16px;
}
</style>
