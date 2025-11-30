<script setup>
import { ref, onMounted } from 'vue'
import { NCard, NForm, NFormItem, NInput, NButton, NTabs, NTabPane, useMessage } from 'naive-ui'
import { getProperties, setPropertiesBatch } from '../../api/property'

const message = useMessage()

const loading = ref(false)
const saving = ref(false)
const settings = ref({
  site_name: '',
  heartbeat_timeout: '120',
  data_retention_days: '30'
})

const fetchSettings = async () => {
  loading.value = true
  try {
    const properties = await getProperties()
    if (Array.isArray(properties)) {
      properties.forEach(p => {
        if (settings.value.hasOwnProperty(p.id)) {
          settings.value[p.id] = p.value
        }
      })
    }
  } catch (error) {
    message.error('获取配置失败')
  } finally {
    loading.value = false
  }
}

const handleSave = async () => {
  saving.value = true
  try {
    const items = Object.keys(settings.value).map(key => ({
      id: key,
      name: key,
      value: settings.value[key]
    }))
    await setPropertiesBatch(items)
    message.success('保存成功')
  } catch (error) {
    message.error('保存失败')
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  fetchSettings()
})
</script>

<template>
  <div class="settings">
    <n-card title="系统设置">
      <n-tabs type="line">
        <n-tab-pane name="general" tab="基本设置">
          <n-form label-placement="left" label-width="120" class="settings-form">
            <n-form-item label="站点名称">
              <n-input v-model:value="settings.site_name" placeholder="请输入站点名称" />
            </n-form-item>
            <n-form-item label="心跳超时(秒)">
              <n-input v-model:value="settings.heartbeat_timeout" placeholder="默认 120 秒" />
            </n-form-item>
            <n-form-item label="数据保留天数">
              <n-input v-model:value="settings.data_retention_days" placeholder="默认 30 天" />
            </n-form-item>
            <n-form-item>
              <n-button type="primary" :loading="saving" @click="handleSave">保存设置</n-button>
            </n-form-item>
          </n-form>
        </n-tab-pane>
        <n-tab-pane name="alert" tab="告警设置">
          <p style="color: #999">告警通知渠道配置（开发中）</p>
        </n-tab-pane>
      </n-tabs>
    </n-card>
  </div>
</template>

<style scoped>
.settings-form {
  max-width: 500px;
  padding: 16px 0;
}
</style>
