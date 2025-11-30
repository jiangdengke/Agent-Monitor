<script setup>
import { ref, onMounted, h } from 'vue'
import { NCard, NDataTable, NTag, NButton, NSpace, NModal, NForm, NFormItem, NInput, useMessage, useDialog } from 'naive-ui'
import { getApiKeyList, createApiKey, deleteApiKey, enableApiKey, disableApiKey, regenerateApiKey } from '../../api/apiKey'

const message = useMessage()
const dialog = useDialog()

const loading = ref(false)
const apiKeys = ref([])
const pagination = ref({
  page: 1,
  pageSize: 10,
  itemCount: 0
})

const showModal = ref(false)
const modalLoading = ref(false)
const formValue = ref({ name: '' })

const columns = [
  {
    title: '名称',
    key: 'name'
  },
  {
    title: 'API Key',
    key: 'key',
    render: (row) => {
      return h('code', { style: { fontSize: '12px' } }, row.key)
    }
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
    title: '创建时间',
    key: 'created_at',
    render: (row) => {
      if (!row.created_at) return '-'
      return new Date(row.created_at).toLocaleString()
    }
  },
  {
    title: '操作',
    key: 'actions',
    width: 280,
    render: (row) => {
      return h(NSpace, null, {
        default: () => [
          h(NButton, {
            size: 'small',
            type: row.enabled ? 'warning' : 'success',
            onClick: () => handleToggle(row)
          }, { default: () => row.enabled ? '禁用' : '启用' }),
          h(NButton, {
            size: 'small',
            onClick: () => handleRegenerate(row)
          }, { default: () => '重新生成' }),
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
    const res = await getApiKeyList({
      page: pagination.value.page,
      pageSize: pagination.value.pageSize
    })
    apiKeys.value = res.items || []
    pagination.value.itemCount = res.total || 0
  } catch (error) {
    message.error('获取数据失败')
  } finally {
    loading.value = false
  }
}

const handleCreate = async () => {
  if (!formValue.value.name) {
    message.warning('请输入名称')
    return
  }

  modalLoading.value = true
  try {
    await createApiKey({ name: formValue.value.name })
    message.success('创建成功')
    showModal.value = false
    formValue.value.name = ''
    fetchData()
  } catch (error) {
    message.error('创建失败')
  } finally {
    modalLoading.value = false
  }
}

const handleToggle = async (row) => {
  try {
    if (row.enabled) {
      await disableApiKey(row.id)
      message.success('已禁用')
    } else {
      await enableApiKey(row.id)
      message.success('已启用')
    }
    fetchData()
  } catch (error) {
    message.error('操作失败')
  }
}

const handleRegenerate = (row) => {
  dialog.warning({
    title: '确认重新生成',
    content: '重新生成后，原有的 API Key 将失效，确定继续吗？',
    positiveText: '确定',
    negativeText: '取消',
    onPositiveClick: async () => {
      try {
        await regenerateApiKey(row.id)
        message.success('重新生成成功')
        fetchData()
      } catch (error) {
        message.error('操作失败')
      }
    }
  })
}

const handleDelete = (row) => {
  dialog.error({
    title: '确认删除',
    content: `确定要删除 "${row.name}" 吗？此操作不可恢复。`,
    positiveText: '删除',
    negativeText: '取消',
    onPositiveClick: async () => {
      try {
        await deleteApiKey(row.id)
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
  <div class="api-key-list">
    <n-card title="API 密钥管理">
      <template #header-extra>
        <n-button type="primary" @click="showModal = true">新增密钥</n-button>
      </template>

      <n-data-table
        :columns="columns"
        :data="apiKeys"
        :loading="loading"
        :pagination="pagination"
        @update:page="handlePageChange"
      />
    </n-card>

    <n-modal v-model:show="showModal" preset="dialog" title="新增 API 密钥">
      <n-form>
        <n-form-item label="名称">
          <n-input v-model:value="formValue.name" placeholder="请输入密钥名称" />
        </n-form-item>
      </n-form>
      <template #action>
        <n-space>
          <n-button @click="showModal = false">取消</n-button>
          <n-button type="primary" :loading="modalLoading" @click="handleCreate">创建</n-button>
        </n-space>
      </template>
    </n-modal>
  </div>
</template>
