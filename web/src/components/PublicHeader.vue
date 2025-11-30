<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { NSpace, NButton, NIcon } from 'naive-ui'
import { ServerOutline, PulseOutline, GridOutline, ListOutline, LogInOutline, SettingsOutline } from '@vicons/ionicons5'

const props = defineProps({
  viewMode: {
    type: String,
    default: 'grid'
  },
  showViewToggle: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:viewMode'])

const route = useRoute()
const router = useRouter()

const isLoggedIn = ref(false)

const isDeviceActive = computed(() => route.path === '/')
const isMonitorActive = computed(() => route.path === '/monitors')

onMounted(() => {
  const token = localStorage.getItem('token')
  isLoggedIn.value = !!token
})

const setViewMode = (mode) => {
  emit('update:viewMode', mode)
}
</script>

<template>
  <header class="public-header">
    <div class="header-content">
      <div class="header-left">
        <div class="brand" @click="router.push('/')">
          <div class="logo-icon">
            <n-icon :component="ServerOutline" :size="24" />
          </div>
          <div class="brand-text">
            <span class="brand-title">系统监控平台</span>
          </div>
        </div>

        <nav class="nav">
          <router-link to="/" :class="['nav-item', { active: isDeviceActive }]">
            <n-icon :component="ServerOutline" />
            <span>设备监控</span>
          </router-link>
          <router-link to="/monitors" :class="['nav-item', { active: isMonitorActive }]">
            <n-icon :component="PulseOutline" />
            <span>服务监控</span>
          </router-link>
        </nav>
      </div>

      <div class="header-right">
        <div v-if="showViewToggle" class="view-toggle">
          <button
            :class="['toggle-btn', { active: viewMode === 'grid' }]"
            @click="setViewMode('grid')"
            title="网格视图"
          >
            <n-icon :component="GridOutline" />
          </button>
          <button
            :class="['toggle-btn', { active: viewMode === 'list' }]"
            @click="setViewMode('list')"
            title="列表视图"
          >
            <n-icon :component="ListOutline" />
          </button>
        </div>

        <n-button
          v-if="isLoggedIn"
          type="primary"
          size="small"
          @click="router.push('/admin')"
        >
          <template #icon>
            <n-icon :component="SettingsOutline" />
          </template>
          管理后台
        </n-button>
        <n-button
          v-else
          type="primary"
          size="small"
          @click="router.push('/login')"
        >
          <template #icon>
            <n-icon :component="LogInOutline" />
          </template>
          登录
        </n-button>
      </div>
    </div>
  </header>
</template>

<style scoped>
.public-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: #fff;
  border-bottom: 1px solid #e5e7eb;
}

.header-content {
  max-width: 1280px;
  margin: 0 auto;
  padding: 12px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 24px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}

.logo-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  border-radius: 8px;
  color: #fff;
}

.brand-title {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
}

.nav {
  display: flex;
  align-items: center;
  gap: 4px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  color: #64748b;
  text-decoration: none;
  transition: all 0.2s;
}

.nav-item:hover {
  background: #f1f5f9;
  color: #1e293b;
}

.nav-item.active {
  background: #eff6ff;
  color: #3b82f6;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.view-toggle {
  display: none;
  align-items: center;
  gap: 2px;
  padding: 2px;
  background: #f1f5f9;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

@media (min-width: 640px) {
  .view-toggle {
    display: flex;
  }
}

.toggle-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 6px;
  border: none;
  background: transparent;
  border-radius: 6px;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}

.toggle-btn:hover {
  color: #1e293b;
}

.toggle-btn.active {
  background: #fff;
  color: #3b82f6;
}
</style>
