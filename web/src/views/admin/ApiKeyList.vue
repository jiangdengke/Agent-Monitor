<script setup>
import { ref, onMounted, h } from 'vue'
import { NCard, NDataTable, NTag, NButton, NSpace, NModal, NForm, NFormItem, NInput, useMessage, useDialog, NIcon } from 'naive-ui'
import { CopyOutline, ServerOutline } from '@vicons/ionicons5'
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

const copyToClipboard = (text) => {
  navigator.clipboard.writeText(text).then(() => {
    message.success('已复制到剪贴板')
  }).catch(() => {
    message.error('复制失败')
  })
}

const columns = [
  {
    title: '名称',
    key: 'name',
    width: 150
  },
  {
    title: 'API Key',
    key: 'key',
    render: (row) => {
      return h('div', { style: { display: 'flex', alignItems: 'center', gap: '8px' } }, [
        h('code', { style: { fontSize: '12px', background: '#f5f5f5', padding: '2px 6px', borderRadius: '4px' } }, row.key),
        h(NButton, {
          size: 'tiny',
          quaternary: true,
          onClick: () => copyToClipboard(row.key)
        }, {
          icon: () => h(NIcon, null, { default: () => h(CopyOutline) })
        })
      ])
    }
  },
  {
    title: '绑定服务器',
    key: 'agent',
    width: 180,
    render: (row) => {
      if (row.agent) {
        return h('div', { style: { display: 'flex', alignItems: 'center', gap: '6px' } }, [
          h(NIcon, { size: 14, color: '#22c55e' }, { default: () => h(ServerOutline) }),
          h('span', { style: { fontSize: '13px' } }, row.agent.hostname || row.agent.name)
        ])
      }
      return h(NTag, { size: 'small', type: 'default' }, { default: () => '未绑定' })
    }
  },
  {
    title: '状态',
    key: 'enabled',
    width: 80,
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
    width: 180,
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
    const res = await createApiKey({ name: formValue.value.name })
    message.success('创建成功')
    showModal.value = false
    formValue.value.name = ''
    fetchData()

    // 显示新创建的 API Key
    dialog.success({
      title: 'API Key 创建成功',
      content: () => h('div', [
        h('p', { style: { marginBottom: '12px' } }, '请复制并保存此 API Key，用于配置 Agent：'),
        h('code', {
          style: {
            display: 'block',
            padding: '12px',
            background: '#f5f5f5',
            borderRadius: '6px',
            wordBreak: 'break-all',
            fontSize: '13px'
          }
        }, res.key)
      ]),
      positiveText: '复制',
      onPositiveClick: () => {
        copyToClipboard(res.key)
      }
    })
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
    content: '重新生成后，原有的 API Key 将失效，使用该 Key 的 Agent 需要重新配置。确定继续吗？',
    positiveText: '确定',
    negativeText: '取消',
    onPositiveClick: async () => {
      try {
        const res = await regenerateApiKey(row.id)
        message.success('重新生成成功')
        fetchData()

        // 显示新的 API Key
        dialog.success({
          title: '新的 API Key',
          content: () => h('code', {
            style: {
              display: 'block',
              padding: '12px',
              background: '#f5f5f5',
              borderRadius: '6px',
              wordBreak: 'break-all',
              fontSize: '13px'
            }
          }, res.key),
          positiveText: '复制',
          onPositiveClick: () => {
            copyToClipboard(res.key)
          }
        })
      } catch (error) {
        message.error('操作失败')
      }
    }
  })
}

const handleDelete = (row) => {
  const hasAgent = row.agent ? `（已绑定服务器: ${row.agent.hostname || row.agent.name}）` : ''
  dialog.error({
    title: '确认删除',
    content: `确定要删除 "${row.name}" 吗？${hasAgent}此操作不可恢复。`,
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

      <div class="tips">
        <p>API Key 用于 Agent 向服务器上报数据的身份验证。每个 API Key 只能绑定一台服务器。</p>
        <p>Agent 首次注册时会自动绑定到使用的 API Key，之后该 Key 只能用于该服务器。</p>
      </div>

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
        <n-form-item label="名称" required>
          <n-input v-model:value="formValue.name" placeholder="请输入密钥名称，如：生产服务器-01" />
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

<style scoped>
.tips {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px 16px;
  margin-bottom: 16px;
  font-size: 13px;
  color: #6b7280;
}

.tips p {
  margin: 0;
  line-height: 1.6;
}

.tips p + p {
  margin-top: 4px;
}
</style>
