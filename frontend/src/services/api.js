import axios from 'axios'

const authAPI = axios.create({
  baseURL: import.meta.env.VITE_AUTH_API_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  withCredentials: false,
  allowAbsoluteUrls: false,
})

authAPI.interceptors.request.use((config) => {
  const token = localStorage.getItem('access_token')
  if (token) {
    config.headers.set('Authorization', `Bearer ${token}`)
  }
  return config
})

authAPI.interceptors.response.use(
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

export default authAPI
