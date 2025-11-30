import { ref, onMounted, onUnmounted } from 'vue'
import echo from '../utils/echo'

/**
 * 订阅指标更新频道
 * @param {Function} onUpdate - 收到更新时的回调函数
 * @returns {Object} - 连接状态和方法
 */
export function useMetricsChannel(onUpdate) {
  const connected = ref(false)
  const error = ref(null)
  let channel = null

  const connect = () => {
    try {
      channel = echo.channel('metrics')
        .listen('.metrics.updated', (data) => {
          if (onUpdate && typeof onUpdate === 'function') {
            onUpdate(data)
          }
        })

      connected.value = true
      console.log('[WebSocket] 已连接到 metrics 频道')
    } catch (err) {
      error.value = err.message
      console.error('[WebSocket] 连接失败:', err)
    }
  }

  const disconnect = () => {
    if (channel) {
      echo.leaveChannel('metrics')
      channel = null
      connected.value = false
      console.log('[WebSocket] 已断开 metrics 频道')
    }
  }

  onMounted(() => {
    connect()
  })

  onUnmounted(() => {
    disconnect()
  })

  return {
    connected,
    error,
    connect,
    disconnect
  }
}

/**
 * 订阅单个探针的指标频道
 * @param {String} agentId - 探针 ID
 * @param {Function} onUpdate - 收到更新时的回调函数
 * @returns {Object} - 连接状态和方法
 */
export function useAgentChannel(agentId, onUpdate) {
  const connected = ref(false)
  const error = ref(null)
  let channel = null

  const connect = () => {
    if (!agentId) return

    try {
      channel = echo.channel(`agent.${agentId}`)
        .listen('.metrics.updated', (data) => {
          if (onUpdate && typeof onUpdate === 'function') {
            onUpdate(data)
          }
        })

      connected.value = true
      console.log(`[WebSocket] 已连接到 agent.${agentId} 频道`)
    } catch (err) {
      error.value = err.message
      console.error('[WebSocket] 连接失败:', err)
    }
  }

  const disconnect = () => {
    if (channel && agentId) {
      echo.leaveChannel(`agent.${agentId}`)
      channel = null
      connected.value = false
      console.log(`[WebSocket] 已断开 agent.${agentId} 频道`)
    }
  }

  onMounted(() => {
    connect()
  })

  onUnmounted(() => {
    disconnect()
  })

  return {
    connected,
    error,
    connect,
    disconnect
  }
}

/**
 * 订阅探针状态变更频道
 * @param {Function} onStatusChange - 状态变更时的回调函数
 * @returns {Object} - 连接状态和方法
 */
export function useAgentsChannel(onStatusChange) {
  const connected = ref(false)
  const error = ref(null)
  let channel = null

  const connect = () => {
    try {
      channel = echo.channel('agents')
        .listen('.agent.status', (data) => {
          if (onStatusChange && typeof onStatusChange === 'function') {
            onStatusChange(data)
          }
        })

      connected.value = true
      console.log('[WebSocket] 已连接到 agents 频道')
    } catch (err) {
      error.value = err.message
      console.error('[WebSocket] 连接失败:', err)
    }
  }

  const disconnect = () => {
    if (channel) {
      echo.leaveChannel('agents')
      channel = null
      connected.value = false
      console.log('[WebSocket] 已断开 agents 频道')
    }
  }

  onMounted(() => {
    connect()
  })

  onUnmounted(() => {
    disconnect()
  })

  return {
    connected,
    error,
    connect,
    disconnect
  }
}
