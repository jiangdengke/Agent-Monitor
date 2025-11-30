<script setup>
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { NLayout, NLayoutSider, NLayoutHeader, NLayoutContent, NMenu, NIcon, NButton, NDropdown, NAvatar, NSpace } from 'naive-ui'
import { ServerOutline, KeyOutline, PulseOutline, SettingsOutline, LogOutOutline, EyeOutline } from '@vicons/ionicons5'

const router = useRouter()
const route = useRoute()

const collapsed = ref(false)

const menuOptions = [
  {
    label: '探针管理',
    key: 'agents',
    icon: () => h(NIcon, null, { default: () => h(ServerOutline) })
  },
  {
    label: 'API密钥',
    key: 'api-keys',
    icon: () => h(NIcon, null, { default: () => h(KeyOutline) })
  },
  {
    label: '服务监控',
    key: 'monitors',
    icon: () => h(NIcon, null, { default: () => h(PulseOutline) })
  },
  {
    label: '系统设置',
    key: 'settings',
    icon: () => h(NIcon, null, { default: () => h(SettingsOutline) })
  }
]

const activeKey = computed(() => {
  const path = route.path
  if (path.includes('/admin/api-keys')) return 'api-keys'
  if (path.includes('/admin/monitors')) return 'monitors'
  if (path.includes('/admin/settings')) return 'settings'
  return 'agents'
})

const handleMenuUpdate = (key) => {
  router.push(`/admin/${key}`)
}

const userOptions = [
  {
    label: '退出登录',
    key: 'logout',
    icon: () => h(NIcon, null, { default: () => h(LogOutOutline) })
  }
]

const handleUserSelect = (key) => {
  if (key === 'logout') {
    localStorage.removeItem('token')
    localStorage.removeItem('userInfo')
    router.push('/login')
  }
}

const goToPublic = () => {
  window.open('/', '_blank')
}

import { h } from 'vue'
</script>

<template>
  <n-layout has-sider class="layout-container">
    <n-layout-sider
      bordered
      collapse-mode="width"
      :collapsed-width="64"
      :width="240"
      :collapsed="collapsed"
      show-trigger
      @collapse="collapsed = true"
      @expand="collapsed = false"
      class="layout-sider"
    >
      <div class="logo">
        <img src="/logo.png" alt="Logo" class="logo-img" />
        <span v-if="!collapsed" class="logo-text">监控平台</span>
      </div>
      <n-menu
        :collapsed="collapsed"
        :collapsed-width="64"
        :collapsed-icon-size="22"
        :options="menuOptions"
        :value="activeKey"
        @update:value="handleMenuUpdate"
      />
    </n-layout-sider>

    <n-layout>
      <n-layout-header bordered class="layout-header">
        <div class="header-left">
          <span class="header-title">控制台</span>
        </div>
        <n-space align="center" :size="12">
          <n-button text @click="goToPublic">
            <template #icon>
              <n-icon><EyeOutline /></n-icon>
            </template>
            公共页面
          </n-button>
          <n-dropdown :options="userOptions" @select="handleUserSelect">
            <n-button text>
              <n-space align="center" :size="8">
                <n-avatar :size="28" round>U</n-avatar>
                <span>管理员</span>
              </n-space>
            </n-button>
          </n-dropdown>
        </n-space>
      </n-layout-header>

      <n-layout-content class="layout-content">
        <router-view />
      </n-layout-content>
    </n-layout>
  </n-layout>
</template>

<style scoped>
.layout-container {
  height: 100vh;
}

.layout-sider {
  background: #fff;
}

.logo {
  height: 56px;
  display: flex;
  align-items: center;
  padding: 0 16px;
  border-bottom: 1px solid #efeff5;
}

.logo-img {
  width: 32px;
  height: 32px;
}

.logo-text {
  margin-left: 12px;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.layout-header {
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  background: #fff;
}

.header-left {
  display: flex;
  align-items: center;
}

.header-title {
  font-size: 16px;
  font-weight: 500;
  color: #333;
}

.layout-content {
  padding: 24px;
  background: #f5f7f9;
  min-height: calc(100vh - 56px);
}
</style>
