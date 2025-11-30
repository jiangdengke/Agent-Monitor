<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { NIcon, NInput, NButton, useMessage } from 'naive-ui'
import { ServerOutline, PersonOutline, LockClosedOutline, MailOutline } from '@vicons/ionicons5'
import { login, register } from '@/api/auth'

const router = useRouter()
const message = useMessage()

const loading = ref(false)
const isRegister = ref(false)
const formValue = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const handleSubmit = async () => {
  if (isRegister.value) {
    await handleRegister()
  } else {
    await handleLogin()
  }
}

const handleLogin = async () => {
  if (!formValue.value.email) {
    message.warning('请输入邮箱')
    return
  }
  if (!formValue.value.password) {
    message.warning('请输入密码')
    return
  }

  try {
    loading.value = true
    const res = await login({
      email: formValue.value.email,
      password: formValue.value.password
    })

    localStorage.setItem('token', res.token)
    localStorage.setItem('userInfo', JSON.stringify(res.user))

    message.success('登录成功')
    router.push('/admin/agents')
  } catch (error) {
    message.error(error.response?.data?.message || error.message || '登录失败')
  } finally {
    loading.value = false
  }
}

const handleRegister = async () => {
  if (!formValue.value.name) {
    message.warning('请输入用户名')
    return
  }
  if (!formValue.value.email) {
    message.warning('请输入邮箱')
    return
  }
  if (!formValue.value.password) {
    message.warning('请输入密码')
    return
  }
  if (formValue.value.password !== formValue.value.password_confirmation) {
    message.warning('两次密码不一致')
    return
  }

  try {
    loading.value = true
    const res = await register({
      name: formValue.value.name,
      email: formValue.value.email,
      password: formValue.value.password,
      password_confirmation: formValue.value.password_confirmation
    })

    localStorage.setItem('token', res.token)
    localStorage.setItem('userInfo', JSON.stringify(res.user))

    message.success('注册成功')
    router.push('/admin/agents')
  } catch (error) {
    message.error(error.response?.data?.message || error.message || '注册失败')
  } finally {
    loading.value = false
  }
}

const toggleMode = () => {
  isRegister.value = !isRegister.value
  formValue.value = {
    name: '',
    email: '',
    password: '',
    password_confirmation: ''
  }
}
</script>

<template>
  <div class="login-page">
    <div class="login-container">
      <!-- Logo 和标题 -->
      <div class="login-header">
        <div class="logo">
          <span class="logo-icon">
            <n-icon size="24"><ServerOutline /></n-icon>
          </span>
        </div>
        <h1 class="title">{{ isRegister ? '注册' : '登录到' }} Server Monitor</h1>
        <p class="subtitle">{{ isRegister ? '创建您的管理员账号' : '管理您的服务器监控' }}</p>
      </div>

      <!-- 表单 -->
      <div class="login-form">
        <!-- 用户名（仅注册） -->
        <div v-if="isRegister" class="form-item">
          <label class="form-label">用户名</label>
          <div class="input-wrapper">
            <span class="input-icon">
              <n-icon><PersonOutline /></n-icon>
            </span>
            <n-input
              v-model:value="formValue.name"
              placeholder="请输入用户名"
              size="large"
              :bordered="false"
            />
          </div>
        </div>

        <!-- 邮箱 -->
        <div class="form-item">
          <label class="form-label">邮箱</label>
          <div class="input-wrapper">
            <span class="input-icon">
              <n-icon><MailOutline /></n-icon>
            </span>
            <n-input
              v-model:value="formValue.email"
              placeholder="请输入邮箱"
              size="large"
              :bordered="false"
            />
          </div>
        </div>

        <!-- 密码 -->
        <div class="form-item">
          <label class="form-label">密码</label>
          <div class="input-wrapper">
            <span class="input-icon">
              <n-icon><LockClosedOutline /></n-icon>
            </span>
            <n-input
              v-model:value="formValue.password"
              type="password"
              placeholder="请输入密码"
              size="large"
              :bordered="false"
              show-password-on="click"
              @keyup.enter="!isRegister && handleSubmit()"
            />
          </div>
        </div>

        <!-- 确认密码（仅注册） -->
        <div v-if="isRegister" class="form-item">
          <label class="form-label">确认密码</label>
          <div class="input-wrapper">
            <span class="input-icon">
              <n-icon><LockClosedOutline /></n-icon>
            </span>
            <n-input
              v-model:value="formValue.password_confirmation"
              type="password"
              placeholder="请再次输入密码"
              size="large"
              :bordered="false"
              show-password-on="click"
              @keyup.enter="handleSubmit"
            />
          </div>
        </div>

        <n-button
          type="primary"
          size="large"
          block
          :loading="loading"
          @click="handleSubmit"
          class="login-btn"
        >
          {{ isRegister ? '注册' : '继续' }}
        </n-button>
      </div>

      <!-- 底部链接 -->
      <div class="login-footer">
        <span class="toggle-link" @click="toggleMode">
          {{ isRegister ? '已有账号？去登录' : '没有账号？去注册' }}
        </span>
        <span class="separator">·</span>
        <a href="/" class="back-link">返回首页</a>
      </div>
    </div>

    <!-- 底部版权 -->
    <footer class="footer">
      <span>© 2025 Server Monitor</span>
    </footer>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #fff;
  padding: 24px;
}

.login-container {
  width: 100%;
  max-width: 400px;
}

/* Logo 和标题 */
.login-header {
  text-align: center;
  margin-bottom: 40px;
}

.logo {
  display: flex;
  justify-content: center;
  margin-bottom: 24px;
}

.logo-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #1f2937;
  color: #fff;
  border-radius: 12px;
}

.title {
  font-size: 24px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 8px 0;
}

.subtitle {
  font-size: 15px;
  color: #6b7280;
  margin: 0;
}

/* 表单 */
.login-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  font-size: 14px;
  font-weight: 500;
  color: #374151;
}

.input-wrapper {
  display: flex;
  align-items: center;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 0 12px;
  transition: all 0.2s;
}

.input-wrapper:focus-within {
  border-color: #1f2937;
  background: #fff;
}

.input-icon {
  color: #9ca3af;
  display: flex;
  align-items: center;
  margin-right: 8px;
}

.input-wrapper :deep(.n-input) {
  --n-border: none;
  --n-border-hover: none;
  --n-border-focus: none;
  --n-box-shadow-focus: none;
  --n-color: transparent;
  --n-color-focus: transparent;
}

.input-wrapper :deep(.n-input__input-el) {
  background: transparent;
}

.login-btn {
  margin-top: 8px;
  height: 44px;
  font-size: 15px;
  font-weight: 500;
  background: #1f2937;
  border: none;
  border-radius: 8px;
}

.login-btn:hover {
  background: #374151;
}

/* 底部链接 */
.login-footer {
  margin-top: 24px;
  text-align: center;
}

.toggle-link {
  font-size: 14px;
  color: #1f2937;
  cursor: pointer;
  transition: color 0.2s;
}

.toggle-link:hover {
  color: #374151;
  text-decoration: underline;
}

.separator {
  margin: 0 8px;
  color: #d1d5db;
}

.back-link {
  font-size: 14px;
  color: #6b7280;
  text-decoration: none;
  transition: color 0.2s;
}

.back-link:hover {
  color: #1f2937;
}

/* 版权 */
.footer {
  position: absolute;
  bottom: 24px;
  font-size: 13px;
  color: #9ca3af;
}

/* 响应式 */
@media (max-width: 480px) {
  .login-container {
    padding: 0 16px;
  }

  .title {
    font-size: 20px;
  }
}
</style>
