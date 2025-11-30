import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// 将 Pusher 挂载到 window 上，Laravel Echo 需要
window.Pusher = Pusher

// 开启 Pusher 日志（调试用）
Pusher.logToConsole = true

const wsHost = import.meta.env.VITE_REVERB_HOST || 'localhost'
const wsPort = import.meta.env.VITE_REVERB_PORT || 8080
const wsScheme = import.meta.env.VITE_REVERB_SCHEME || 'http'
const appKey = import.meta.env.VITE_REVERB_APP_KEY

console.log('[WebSocket] 配置:', { wsHost, wsPort, wsScheme, appKey })

/**
 * 创建 Laravel Echo 实例
 * 用于 WebSocket 实时通信
 */
const echo = new Echo({
  broadcaster: 'reverb',
  key: appKey,
  wsHost: wsHost,
  wsPort: wsPort,
  wssPort: wsPort,
  forceTLS: wsScheme === 'https',
  enabledTransports: ['ws', 'wss'],
  disableStats: true,
})

export default echo
