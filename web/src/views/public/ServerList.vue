<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { NCard, NGrid, NGridItem, NTag, NProgress, NSpace, NButton } from 'naive-ui'
import { getAgentList, getAgentStatistics } from '../../api/agent'

const router = useRouter()

const loading = ref(false)
const agents = ref([])
const statistics = ref({ total: 0, online: 0, offline: 0 })

const fetchData = async () => {
  loading.value = true
  try {
    const [listRes, statsRes] = await Promise.all([
      getAgentList({ pageSize: 100 }),
      getAgentStatistics()
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

const goToAdmin = () => {
  router.push('/admin')
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <div class="server-list">
    <header class="header">
      <div class="header-content">
        <h1>系统监控平台</h1>
        <n-space>
          <n-button text @click="() => router.push('/monitors')">服务监控</n-button>
          <n-button type="primary" size="small" @click="goToAdmin">管理后台</n-button>
        </n-space>
      </div>
    </header>

    <main class="main">
      <n-space class="stats" :size="24">
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
      </n-space>

      <n-grid :cols="3" :x-gap="16" :y-gap="16" class="server-grid">
        <n-grid-item v-for="agent in agents" :key="agent.id">
          <n-card hoverable class="server-card" @click="goToDetail(agent.id)">
            <div class="card-header">
              <span class="hostname">{{ agent.hostname || agent.id }}</span>
              <n-tag :type="agent.status === 'online' ? 'success' : 'error'" size="small">
                {{ agent.status === 'online' ? '在线' : '离线' }}
              </n-tag>
            </div>
            <div class="card-content">
              <div class="metric">
                <span class="metric-label">CPU</span>
                <n-progress
                  type="line"
                  :percentage="agent.cpu_usage || 0"
                  :height="8"
                  :border-radius="4"
                  :fill-border-radius="4"
                />
              </div>
              <div class="metric">
                <span class="metric-label">内存</span>
                <n-progress
                  type="line"
                  :percentage="agent.memory_usage || 0"
                  :height="8"
                  :border-radius="4"
                  :fill-border-radius="4"
                />
              </div>
              <div class="metric">
                <span class="metric-label">磁盘</span>
                <n-progress
                  type="line"
                  :percentage="agent.disk_usage || 0"
                  :height="8"
                  :border-radius="4"
                  :fill-border-radius="4"
                />
              </div>
            </div>
            <div class="card-footer">
              <span class="ip">{{ agent.ip_address || '-' }}</span>
              <span class="os">{{ agent.os || '-' }}</span>
            </div>
          </n-card>
        </n-grid-item>
      </n-grid>
    </main>
  </div>
</template>

<style scoped>
.server-list {
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

.stats {
  margin-bottom: 24px;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-value {
  font-size: 32px;
  font-weight: 600;
  color: #333;
}

.stat-value.online {
  color: #18a058;
}

.stat-value.offline {
  color: #d03050;
}

.stat-label {
  font-size: 14px;
  color: #666;
}

.server-card {
  cursor: pointer;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.hostname {
  font-size: 16px;
  font-weight: 500;
}

.card-content {
  margin-bottom: 16px;
}

.metric {
  margin-bottom: 12px;
}

.metric-label {
  display: block;
  font-size: 12px;
  color: #666;
  margin-bottom: 4px;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #999;
}
</style>
