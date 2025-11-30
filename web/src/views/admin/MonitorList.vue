<script setup>
import { ref, onMounted, h } from 'vue'
import { NCard, NDataTable, NTag, NButton, NSpace, NModal, NForm, NFormItem, NInput, NSelect, NInputNumber, NStatistic, NGrid, NGridItem, useMessage, useDialog } from 'naive-ui'
import { getMonitorList, createMonitor, updateMonitor, deleteMonitor, getMonitorOverview } from '../../api/monitor'

const message = useMessage()
const dialog = useDialog()

const loading = ref(false)
const monitors = ref([])
const overview = ref({})
const pagination = ref({
  page: 1,
  pageSize: 10,
  itemCount: 0
})

const showModal = ref(false)
const modalLoading = ref(false)
const isEdit = ref(false)
const currentId = ref(null)
const formValue = ref({
  name: '',
  type: 'http',
  target: '',
  interval: 60,
  enabled: true
})

const typeOptions = [
  { label: 'HTTP', value: 'http' },
  { label: 'TCP', value: 'tcp' },
  { label: 'Ping', value: 'ping' }
]

const columns = [
  {
    title: '名称',
    key: 'name'
  },
  {
    title: '类型',
    key: 'type',
    render: (row) => {
      const typeMap = { http: 'HTTP', tcp: 'TCP', ping: 'Ping' }
      return h(NTag, { size: 'small' }, { default: () => typeMap[row.type] || row.type })
    }
  },
  {
    title: '目标',
    key: 'target',
    ellipsis: { tooltip: true }
  },
  {
    title: '检测间隔',
    key: 'interval',
    render: (row) => `${row.interval}秒`
  },
  {
    title: '状态',
    key: 'enabled',
    render: (row) => {
      return h(NTag, {
        type: row.enabled ? 'success' : 'warning',
        size: 'small'
      }, { default: () => row.enabled ? '启用' : '禁用' })
    }
  },
  {
    title: '操作',
    key: 'actions',
    width: 200,
    render: (row) => {
      return h(NSpace, null, {
        default: () => [
          h(NButton, {
            size: 'small',
            onClick: () => handleEdit(row)
          }, { default: () => '编辑' }),
          h(NButton, {
            size: 'small',
            type: 'error',
            onClick: () => handleDelete(row)
          }, { default: () => '删除' })
        ]
      })
    }
  }
]

const fetchData = async () => {
  loading.value = true
  try {
    const [listRes, overviewRes] = await Promise.all([
      getMonitorList({ page: pagination.value.page, pageSize: pagination.value.pageSize }),
      getMonitorOverview()
    ])
    monitors.value = listRes.items || []
    pagination.value.itemCount = listRes.total || 0
    overview.value = overviewRes
  } catch (error) {
    message.error('获取数据失败')
  } finally {
    loading.value = false
  }
}

const resetForm = () => {
  formValue.value = {
    name: '',
    type: 'http',
    target: '',
    interval: 60,
    enabled: true
  }
  isEdit.value = false
  currentId.value = null
}

const handleCreate = () => {
  resetForm()
  showModal.value = true
}

const handleEdit = (row) => {
  isEdit.value = true
  currentId.value = row.id
  formValue.value = {
    name: row.name,
    type: row.type,
    target: row.target,
    interval: row.interval,
    enabled: row.enabled
  }
  showModal.value = true
}

const handleSubmit = async () => {
  if (!formValue.value.name || !formValue.value.target) {
    message.warning('请填写完整信息')
    return
  }

  modalLoading.value = true
  try {
    if (isEdit.value) {
      await updateMonitor(currentId.value, formValue.value)
      message.success('更新成功')
    } else {
      await createMonitor(formValue.value)
      message.success('创建成功')
    }
    showModal.value = false
    fetchData()
  } catch (error) {
    message.error(isEdit.value ? '更新失败' : '创建失败')
  } finally {
    modalLoading.value = false
  }
}

const handleDelete = (row) => {
  dialog.error({
    title: '确认删除',
    content: `确定要删除监控任务 "${row.name}" 吗？`,
    positiveText: '删除',
    negativeText: '取消',
    onPositiveClick: async () => {
      try {
        await deleteMonitor(row.id)
        message.success('删除成功')
        fetchData()
      } catch (error) {
        message.error('删除失败')
      }
    }
  })
}

const handlePageChange = (page) => {
  pagination.value.page = page
  fetchData()
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <div class="monitor-list">
    <n-grid :cols="4" :x-gap="16" class="stats-grid">
      <n-grid-item>
        <n-card>
          <n-statistic label="监控任务" :value="overview.total_tasks || 0" />
        </n-card>
      </n-grid-item>
      <n-grid-item>
        <n-card>
          <n-statistic label="已启用" :value="overview.enabled_tasks || 0" />
        </n-card>
      </n-grid-item>
      <n-grid-item>
        <n-card>
          <n-statistic label="24h 可用率" :value="(overview.overall_uptime_24h || 0).toFixed(1)">
            <template #suffix>%</template>
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

    <n-card title="服务监控">
      <template #header-extra>
        <n-button type="primary" @click="handleCreate">新增监控</n-button>
      </template>

      <n-data-table
        :columns="columns"
        :data="monitors"
        :loading="loading"
        :pagination="pagination"
        @update:page="handlePageChange"
      />
    </n-card>

    <n-modal v-model:show="showModal" preset="dialog" :title="isEdit ? '编辑监控任务' : '新增监控任务'">
      <n-form label-placement="left" label-width="80">
        <n-form-item label="名称">
          <n-input v-model:value="formValue.name" placeholder="请输入监控名称" />
        </n-form-item>
        <n-form-item label="类型">
          <n-select v-model:value="formValue.type" :options="typeOptions" />
        </n-form-item>
        <n-form-item label="目标">
          <n-input v-model:value="formValue.target" placeholder="URL 或 IP:Port" />
        </n-form-item>
        <n-form-item label="间隔(秒)">
          <n-input-number v-model:value="formValue.interval" :min="10" :max="3600" />
        </n-form-item>
      </n-form>
      <template #action>
        <n-space>
          <n-button @click="showModal = false">取消</n-button>
          <n-button type="primary" :loading="modalLoading" @click="handleSubmit">
            {{ isEdit ? '更新' : '创建' }}
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
</style>
