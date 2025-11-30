<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { NCard, NGrid, NGridItem, NStatistic, NTag, NDescriptions, NDescriptionsItem, NProgress, NSpace, NButton, useMessage } from 'naive-ui'
import { getAgentDetail, getAgentLatestMetrics } from '../../api/agent'

const route = useRoute()
const router = useRouter()
const message = useMessage()

const loading = ref(false)
const agent = ref(null)
const metrics = ref({})
let refreshTimer = null

const fetchData = async () => {
  loading.value = true
  try {
    const agentId = route.params.id
    const [agentRes, metricsRes] = await Promise.all([
      getAgentDetail(agentId),
      getAgentLatestMetrics(agentId)
    ])

    agent.value = agentRes
    metrics.value = metricsRes
  } catch (error) {
    message.error('获取数据失败')
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
  const mins = Math.floor((seconds % 3600) / 60)
  if (days > 0) return `${days}天 ${hours}小时`
  if (hours > 0) return `${hours}小时 ${mins}分钟`
  return `${mins}分钟`
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
  <div class="agent-detail">
    <n-space justify="space-between" align="center" class="header">
      <n-space align="center">
        <n-button text @click="router.back()">← 返回</n-button>
        <h2>{{ agent?.hostname || '探针详情' }}</h2>
        <n-tag :type="agent?.status === 'online' ? 'success' : 'error'" size="small">
          {{ agent?.status === 'online' ? '在线' : '离线' }}
        </n-tag>
      </n-space>
    </n-space>

    <n-grid :cols="4" :x-gap="16" class="metrics-grid">
      <n-grid-item>
        <n-card>
          <n-statistic label="CPU 使用率">
            <template #default>
              <n-progress
                type="circle"
                :percentage="metrics.cpu?.usage_percent || 0"
                :color="(metrics.cpu?.usage_percent || 0) > 80 ? '#d03050' : '#18a058'"
              />
            </template>
          </n-statistic>
        </n-card>
      </n-grid-item>
      <n-grid-item>
        <n-card>
          <n-statistic label="内存使用率">
            <template #default>
              <n-progress
                type="circle"
                :percentage="metrics.memory?.usage_percent || 0"
                :color="(metrics.memory?.usage_percent || 0) > 80 ? '#d03050' : '#18a058'"
              />
            </template>
          </n-statistic>
        </n-card>
      </n-grid-item>
      <n-grid-item>
        <n-card>
          <n-statistic label="磁盘使用率">
            <template #default>
              <n-progress
                type="circle"
                :percentage="metrics.disk?.usage_percent || 0"
                :color="(metrics.disk?.usage_percent || 0) > 80 ? '#d03050' : '#18a058'"
              />
            </template>
          </n-statistic>
        </n-card>
      </n-grid-item>
      <n-grid-item>
        <n-card>
          <n-statistic label="系统负载" :value="metrics.load?.load1?.toFixed(2) || '-'" />
        </n-card>
      </n-grid-item>
    </n-grid>

    <n-card title="系统信息" class="info-card">
      <n-descriptions :column="2" bordered>
        <n-descriptions-item label="主机名">{{ agent?.hostname || '-' }}</n-descriptions-item>
        <n-descriptions-item label="IP 地址">{{ agent?.ip_address || '-' }}</n-descriptions-item>
        <n-descriptions-item label="操作系统">{{ agent?.os || '-' }}</n-descriptions-item>
        <n-descriptions-item label="系统版本">{{ agent?.os_version || '-' }}</n-descriptions-item>
        <n-descriptions-item label="内核版本">{{ agent?.kernel_version || '-' }}</n-descriptions-item>
        <n-descriptions-item label="CPU 型号">{{ metrics.cpu?.model_name || '-' }}</n-descriptions-item>
        <n-descriptions-item label="CPU 核心">{{ metrics.cpu?.logical_cores || '-' }} 核</n-descriptions-item>
        <n-descriptions-item label="总内存">{{ formatBytes(metrics.memory?.total) }}</n-descriptions-item>
        <n-descriptions-item label="系统运行时间">{{ formatUptime(metrics.host?.uptime) }}</n-descriptions-item>
        <n-descriptions-item label="Agent 版本">{{ agent?.version || '-' }}</n-descriptions-item>
      </n-descriptions>
    </n-card>

    <n-grid :cols="2" :x-gap="16" class="detail-grid">
      <n-grid-item>
        <n-card title="网络流量">
          <n-descriptions :column="1" bordered>
            <n-descriptions-item label="发送速率">{{ formatBytes(metrics.network?.bytes_sent_rate) }}/s</n-descriptions-item>
            <n-descriptions-item label="接收速率">{{ formatBytes(metrics.network?.bytes_recv_rate) }}/s</n-descriptions-item>
            <n-descriptions-item label="总发送">{{ formatBytes(metrics.network?.bytes_sent) }}</n-descriptions-item>
            <n-descriptions-item label="总接收">{{ formatBytes(metrics.network?.bytes_recv) }}</n-descriptions-item>
          </n-descriptions>
        </n-card>
      </n-grid-item>
      <n-grid-item>
        <n-card title="磁盘 IO">
          <n-descriptions :column="1" bordered>
            <n-descriptions-item label="读取速率">{{ formatBytes(metrics.disk_io?.read_bytes_rate) }}/s</n-descriptions-item>
            <n-descriptions-item label="写入速率">{{ formatBytes(metrics.disk_io?.write_bytes_rate) }}/s</n-descriptions-item>
            <n-descriptions-item label="总读取">{{ formatBytes(metrics.disk_io?.read_bytes) }}</n-descriptions-item>
            <n-descriptions-item label="总写入">{{ formatBytes(metrics.disk_io?.write_bytes) }}</n-descriptions-item>
          </n-descriptions>
        </n-card>
      </n-grid-item>
    </n-grid>
  </div>
</template>

<style scoped>
.header {
  margin-bottom: 16px;
}

.header h2 {
  margin: 0;
}

.metrics-grid {
  margin-bottom: 16px;
}

.info-card {
  margin-bottom: 16px;
}

.detail-grid {
  margin-top: 16px;
}
</style>
