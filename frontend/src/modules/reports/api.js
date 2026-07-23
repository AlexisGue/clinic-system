import api from '@/api/axios'

export const reportsApi = {
  patients: (params) => api.get('/reports/patients', { params }),
  appointments: (params) => api.get('/reports/appointments', { params }),
  consultations: (params) => api.get('/reports/consultations', { params }),
  doctors: (params) => api.get('/reports/doctors', { params }),
  payments: (params) => api.get('/reports/payments', { params }),
  download: async (path, params) => {
    const response = await api.get(path, {
      params,
      responseType: 'blob',
    })

    const contentType = response.headers['content-type'] || ''
    if (contentType.includes('application/json')) {
      const text = await response.data.text()
      const json = JSON.parse(text)
      throw Object.assign(new Error(json.message || 'Error al exportar'), {
        response: { data: json, status: response.status },
      })
    }

    return response.data
  },
}

export function saveBlob(blob, filename) {
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  a.click()
  URL.revokeObjectURL(url)
}
