<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { NCard, NGrid, NGridItem, NTag, NStatistic, NSpace, NButton } from 'naive-ui'
import { getMonitorList, getMonitorOverview } from '../../api/monitor'

const router = useRouter()

const loading = ref(false)
const monitors = ref([])
const overview = ref({})

const fetchData = async () => {
  loading.value = true
  try {
    const [listRes, overviewRes] = await Promise.all([
      getMonitorList({ pageSize: 100 }),
      getMonitorOverview()
    ])
    monitors.value = listRes.items || []
    overview.value = overviewRes
  } catch (error) {
    console.error('获取数据失败', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <div class="monitor-list">
    <header class="header">
      <div class="header-content">
        <h1>服务监控</h1>
        <n-space>
          <n-button text style="color: #fff" @click="router.push('/')">服务器状态</n-button>
          <n-button type="primary" size="small" @click="router.push('/admin')">管理后台</n-button>
        </n-space>
      </div>
    </header>

    <main class="main">
      <n-grid :cols="4" :x-gap="16" class="stats-grid">
        <n-grid-item>
          <n-card>
            <n-statistic label="监控总数" :value="overview.total_tasks || 0" />
          </n-card>
        </n-grid-item>
        <n-grid-item>
          <n-card>
            <n-statistic label="已启用" :value="overview.enabled_tasks || 0" />
          </n-card>
        </n-grid-item>
        <n-grid-item>
          <n-card>
            <n-statistic label="24h 可用率">
              <template #default>
                <span :style="{ color: (overview.overall_uptime_24h || 0) >= 99 ? '#18a058' : '#d03050' }">
                  {{ (overview.overall_uptime_24h || 0).toFixed(1) }}%
                </span>
              </template>
            </n-statistic>
          </n-card>
        </n-grid-item>
        <n-grid-item>
          <n-card>
            <n-statistic label="平均响应" :value="overview.avg_response_time_24h || 0">
              <template #suffix>ms</template>
            </n-statistic>
          </n-card>
        </n-grid-item>
      </n-grid>

      <n-grid :cols="3" :x-gap="16" :y-gap="16" class="monitor-grid">
        <n-grid-item v-for="monitor in monitors" :key="monitor.id">
          <n-card class="monitor-card">
            <div class="card-header">
              <span class="name">{{ monitor.name }}</span>
              <n-tag :type="monitor.enabled ? 'success' : 'warning'" size="small">
                {{ monitor.enabled ? '运行中' : '已暂停' }}
              </n-tag>
            </div>
            <div class="card-content">
              <div class="target">{{ monitor.target }}</div>
              <n-space class="meta">
                <n-tag size="small">{{ monitor.type.toUpperCase() }}</n-tag>
                <span>间隔 {{ monitor.interval }}s</span>
              </n-space>
            </div>
          </n-card>
        </n-grid-item>
      </n-grid>
    </main>
  </div>
</template>

<style scoped>
.monitor-list {
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
  display: flex;
  justify-content: space-between;
  align-items: center;
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

.stats-grid {
  margin-bottom: 24px;
}

.monitor-card {
  cursor: pointer;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.name {
  font-size: 16px;
  font-weight: 500;
}

.card-content .target {
  font-size: 13px;
  color: #666;
  margin-bottom: 8px;
  word-break: break-all;
}

.meta {
  font-size: 12px;
  color: #999;
}
</style>
