from pathlib import Path

BASE = Path(r"C:\xampp\htdocs\inventory-system\frontend\src\modules")


def w(rel: str, content: str) -> None:
    path = BASE / rel
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content.strip() + "\n", encoding="utf-8")
    print(rel)


w(
    "patients/api.js",
    """
import api from '@/api/axios'

export const patientsApi = {
  list: (params) => api.get('/patients', { params }),
  options: () => api.get('/patients/options'),
  get: (id) => api.get(`/patients/${id}`),
  create: (payload) => api.post('/patients', payload),
  update: (id, payload) => api.put(`/patients/${id}`, payload),
  remove: (id) => api.delete(`/patients/${id}`),
  history: (id) => api.get(`/patients/${id}/history`),
  contacts: (id) => api.get(`/patients/${id}/contacts`),
  storeContact: (id, payload) => api.post(`/patients/${id}/contacts`, payload),
  updateContact: (id, contactId, payload) => api.put(`/patients/${id}/contacts/${contactId}`, payload),
  destroyContact: (id, contactId) => api.delete(`/patients/${id}/contacts/${contactId}`),
}
""",
)

w(
    "doctors/api.js",
    """
import api from '@/api/axios'

export const doctorsApi = {
  list: (params) => api.get('/doctors', { params }),
  options: () => api.get('/doctors/options'),
  get: (id) => api.get(`/doctors/${id}`),
  create: (payload) => api.post('/doctors', payload),
  update: (id, payload) => api.put(`/doctors/${id}`, payload),
  remove: (id) => api.delete(`/doctors/${id}`),
  syncSpecialties: (id, specialty_ids) => api.put(`/doctors/${id}/specialties`, { specialty_ids }),
  getSchedules: (id) => api.get(`/doctors/${id}/schedules`),
  putSchedules: (id, schedules) => api.put(`/doctors/${id}/schedules`, { schedules }),
}
""",
)

w(
    "appointments/api.js",
    """
import api from '@/api/axios'

export const appointmentsApi = {
  list: (params) => api.get('/appointments', { params }),
  calendar: (params) => api.get('/appointments/calendar', { params }),
  get: (id) => api.get(`/appointments/${id}`),
  create: (payload) => api.post('/appointments', payload),
  reschedule: (id, payload) => api.post(`/appointments/${id}/reschedule`, payload),
  cancel: (id, payload) => api.post(`/appointments/${id}/cancel`, payload),
  status: (id, payload) => api.post(`/appointments/${id}/status`, payload),
}
""",
)

w(
    "consultations/api.js",
    """
import api from '@/api/axios'

export const consultationsApi = {
  list: (params) => api.get('/consultations', { params }),
  get: (id) => api.get(`/consultations/${id}`),
  create: (payload) => api.post('/consultations', payload),
  update: (id, payload) => api.put(`/consultations/${id}`, payload),
  finalize: (id) => api.post(`/consultations/${id}/finalize`),
  vitals: (id, payload) => api.post(`/consultations/${id}/vitals`, payload),
}
""",
)

w(
    "prescriptions/api.js",
    """
import api from '@/api/axios'

export const prescriptionsApi = {
  list: (params) => api.get('/prescriptions', { params }),
  get: (id) => api.get(`/prescriptions/${id}`),
  create: (payload) => api.post('/prescriptions', payload),
  update: (id, payload) => api.put(`/prescriptions/${id}`, payload),
  cancel: (id) => api.post(`/prescriptions/${id}/cancel`),
  pdfUrl: (id) => `${import.meta.env.VITE_API_URL}/api/v1/prescriptions/${id}/pdf`,
}
""",
)

w(
    "payments/api.js",
    """
import api from '@/api/axios'

export const paymentsApi = {
  list: (params) => api.get('/payments', { params }),
  create: (payload) => api.post('/payments', payload),
}
""",
)

w(
    "catalogs/api.js",
    """
import api from '@/api/axios'

export const catalogsApi = {
  specialtyOptions: () => api.get('/specialties/options'),
  medicineOptions: () => api.get('/medicines/options'),
  paymentMethods: () => api.get('/payment-methods'),
}
""",
)

print("apis ok")
