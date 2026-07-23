import api from '@/api/axios'

export const catalogsApi = {
  specialtyOptions: () => api.get('/specialties/options'),
  medicineOptions: () => api.get('/medicines/options'),
  paymentMethods: () => api.get('/payment-methods'),
}
