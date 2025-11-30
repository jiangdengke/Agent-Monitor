import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/Login.vue')
  },
  {
    path: '/',
    name: 'PublicServerList',
    component: () => import('../views/public/ServerList.vue')
  },
  {
    path: '/servers/:id',
    name: 'PublicServerDetail',
    component: () => import('../views/public/ServerDetail.vue')
  },
  {
    path: '/monitors',
    name: 'PublicMonitorList',
    component: () => import('../views/public/MonitorList.vue')
  },
  {
    path: '/admin',
    component: () => import('../layouts/AdminLayout.vue'),
    redirect: '/admin/agents',
    children: [
      {
        path: 'agents',
        name: 'AgentList',
        component: () => import('../views/admin/AgentList.vue')
      },
      {
        path: 'agents/:id',
        name: 'AgentDetail',
        component: () => import('../views/admin/AgentDetail.vue')
      },
      {
        path: 'api-keys',
        name: 'ApiKeyList',
        component: () => import('../views/admin/ApiKeyList.vue')
      },
      {
        path: 'monitors',
        name: 'MonitorList',
        component: () => import('../views/admin/MonitorList.vue')
      },
      {
        path: 'settings',
        name: 'Settings',
        component: () => import('../views/admin/Settings.vue')
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
