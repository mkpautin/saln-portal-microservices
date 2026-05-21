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

export default salnApi
