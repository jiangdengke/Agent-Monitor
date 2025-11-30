<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { NCard, NDataTable, NTag, NButton, NSpace, NStatistic, NGrid, NGridItem, useMessage } from 'naive-ui'
import { getAgentList, getAgentStatistics } from '../../api/agent'

const router = useRouter()
const message = useMessage()

const loading = ref(false)
const agents = ref([])
const statistics = ref({
  total: 0,
  online: 0,
  offline: 0
})
const pagination = ref({
  page: 1,
  pageSize: 10,
  itemCount: 0
})

const columns = [
  {
    title: '主机名',
    key: 'hostname',
    render: (row) => {
      return h('a', {
        style: { color: '#18a058', cursor: 'pointer' },
        onClick: () => router.push(`/admin/agents/${row.id}`)
      }, row.hostname || row.id)
    }
  },
  {
    title: 'IP 地址',
    key: 'ip_address'
  },
  {
    title: '操作系统',
    key: 'os',
    render: (row) => row.os || '-'
  },
  {
    title: '状态',
    key: 'status',
    render: (row) => {
      const isOnline = row.status === 'online'
      return h(NTag, {
        type: isOnline ? 'success' : 'error',
        size: 'small'
      }, { default: () => isOnline ? '在线' : '离线' })
    }
  },
  {
    title: '最后心跳',
    key: 'last_heartbeat_at',
    render: (row) => {
      if (!row.last_heartbeat_at) return '-'
      return new Date(row.last_heartbeat_at).toLocaleString()
    }
  },
  {
    title: '操作',
    key: 'actions',
    render: (row) => {
      return h(NSpace, null, {
        default: () => [
          h(NButton, {
            size: 'small',
            onClick: () => router.push(`/admin/agents/${row.id}`)
          }, { default: () => '详情' })
        ]
      })
    }
  }
]

const fetchData = async () => {
  loading.value = true
  try {
    const [listRes, statsRes] = await Promise.all([
      getAgentList({ page: pagination.value.page, pageSize: pagination.value.pageSize }),
      getAgentStatistics()
    ])

    agents.value = listRes.items || []
    pagination.value.itemCount = listRes.total || 0

    statistics.value = statsRes
  } catch (error) {
    message.error('获取数据失败')
  } finally {
    loading.value = false
  }
}

const handlePageChange = (page) => {
  pagination.value.page = page
  fetchData()
}

onMounted(() => {
  fetchData()
})

import { h } from 'vue'
</script>

<template>
  <div class="agent-list">
    <n-grid :cols="3" :x-gap="16" class="stats-grid">
      <n-grid-item>
        <n-card>
          <n-statistic label="探针总数" :value="statistics.total" />
        </n-card>
      </n-grid-item>
      <n-grid-item>
        <n-card>
          <n-statistic label="在线" :value="statistics.online">
            <template #suffix>
              <span style="color: #18a058">台</span>
            </template>
          </n-statistic>
        </n-card>
      </n-grid-item>
      <n-grid-item>
        <n-card>
          <n-statistic label="离线" :value="statistics.offline">
            <template #suffix>
              <span style="color: #d03050">台</span>
            </template>
          </n-statistic>
        </n-card>
      </n-grid-item>
    </n-grid>

    <n-card title="探针列表" class="list-card">
      <n-data-table
        :columns="columns"
        :data="agents"
        :loading="loading"
        :pagination="pagination"
        @update:page="handlePageChange"
      />
    </n-card>
  </div>
</template>

<style scoped>
.stats-grid {
  margin-bottom: 16px;
}

.list-card {
  margin-top: 16px;
}
</style>
