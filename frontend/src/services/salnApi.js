import axios from 'axios'

const salnApi = axios.create({
  baseURL: import.meta.env.VITE_SALN_API_URL || 'http://127.0.0.1:8001/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  withCredentials: false,
  allowAbsoluteUrls: false,
})

salnApi.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token')
  if (token) {
    config.headers.set('Authorization', `Bearer ${token}`)
  }
  return config
})

salnApi.interceptors.response.use(
  (response) => response,
  async (error) => {
    const status = error?.response?.status
    const message = error?.response?.data?.message

    if (status === 401 && message === 'Unauthenticated.') {
      localStorage.removeItem('access_token')
      localStorage.setItem('login_status', 'Session has expired.')

      try {
        const module = await import('../router')
        module.default.replace({ path: '/login' })
      } catch (_routerError) {
        window.location.replace('/login')
      }
    }

    return Promise.reject(error)
  },
)

export default salnApi
