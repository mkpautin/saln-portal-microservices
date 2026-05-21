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

export default authAPI
