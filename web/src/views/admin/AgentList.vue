<script setup>
import { ref, onMounted, h } from 'vue'
import { useRouter } from 'vue-router'
import {
  NCard,
  NDataTable,
  NTag,
  NButton,
  NSpace,
  NStatistic,
  NGrid,
  NGridItem,
  NModal,
  NForm,
  NFormItem,
  NInput,
  useMessage
} from 'naive-ui'
import { getAgentList, getAgentStatistics, updateAgent } from '../../api/agent'

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

const showEditModal = ref(false)
const editLoading = ref(false)
const editForm = ref({
  id: '',
  hostname: '',
  location: '',
  description: ''
})
const editFormRules = {
  hostname: { required: true, message: '请输入主机名', trigger: 'blur' }
}
const editFormRef = ref(null)

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
    title: '位置',
    key: 'location',
    render: (row) => row.location || '-'
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
      const isOnline = row.status === 1
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
    width: 150,
    render: (row) => {
      return h(NSpace, { size: 'small' }, {
        default: () => [
          h(NButton, {
            size: 'small',
            onClick: () => router.push(`/admin/agents/${row.id}`)
          }, { default: () => '详情' }),
          h(NButton, {
            size: 'small',
            type: 'primary',
            secondary: true,
            onClick: () => handleEdit(row)
          }, { default: () => '编辑' })
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

const handleEdit = (row) => {
  editForm.value = {
    id: row.id,
    hostname: row.hostname || '',
    location: row.location || '',
    description: row.description || ''
  }
  showEditModal.value = true
}

const handleEditSubmit = async () => {
  try {
    await editFormRef.value?.validate()
  } catch (errors) {
    return
  }

  editLoading.value = true
  try {
    await updateAgent(editForm.value.id, {
      hostname: editForm.value.hostname,
      location: editForm.value.location,
      description: editForm.value.description
    })
    message.success('更新成功')
    showEditModal.value = false
    fetchData()
  } catch (error) {
    message.error('更新失败')
  } finally {
    editLoading.value = false
  }
}

onMounted(() => {
  fetchData()
})
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

    <n-modal
      v-model:show="showEditModal"
      preset="card"
      title="编辑探针"
      style="width: 500px"
      :mask-closable="false"
    >
      <n-form
        ref="editFormRef"
        :model="editForm"
        :rules="editFormRules"
        label-placement="left"
        label-width="80"
      >
        <n-form-item label="主机名" path="hostname">
          <n-input v-model:value="editForm.hostname" placeholder="请输入主机名" />
        </n-form-item>
        <n-form-item label="位置" path="location">
          <n-input v-model:value="editForm.location" placeholder="请输入服务器位置，如：香港、美国等" />
        </n-form-item>
        <n-form-item label="描述" path="description">
          <n-input
            v-model:value="editForm.description"
            type="textarea"
            placeholder="请输入描述信息"
            :rows="3"
          />
        </n-form-item>
      </n-form>
      <template #footer>
        <n-space justify="end">
          <n-button @click="showEditModal = false">取消</n-button>
          <n-button type="primary" :loading="editLoading" @click="handleEditSubmit">
            保存
          </n-button>
        </n-space>
      </template>
    </n-modal>
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
