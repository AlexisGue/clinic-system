import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/modules/auth/views/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: '/',
    component: () => import('@/components/layout/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: { name: 'dashboard' } },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('@/modules/dashboard/views/DashboardView.vue'),
        meta: { permission: 'dashboard.view' },
      },
      {
        path: 'patients',
        name: 'patients.index',
        component: () => import('@/modules/patients/views/PatientsListView.vue'),
        meta: { permission: 'patients.view' },
      },
      {
        path: 'patients/:id',
        name: 'patients.show',
        component: () => import('@/modules/patients/views/PatientShowView.vue'),
        meta: { permission: 'patients.view' },
      },
      {
        path: 'doctors',
        name: 'doctors.index',
        component: () => import('@/modules/doctors/views/DoctorsListView.vue'),
        meta: { permission: 'doctors.view' },
      },
      {
        path: 'doctors/:id',
        name: 'doctors.show',
        component: () => import('@/modules/doctors/views/DoctorShowView.vue'),
        meta: { permission: 'doctors.view' },
      },
      {
        path: 'appointments',
        name: 'appointments.index',
        component: () => import('@/modules/appointments/views/AppointmentsView.vue'),
        meta: { permission: 'appointments.view' },
      },
      {
        path: 'consultations',
        name: 'consultations.index',
        component: () => import('@/modules/consultations/views/ConsultationsListView.vue'),
        meta: { permission: 'consultations.view' },
      },
      {
        path: 'consultations/:id',
        name: 'consultations.show',
        component: () => import('@/modules/consultations/views/ConsultationShowView.vue'),
        meta: { permission: 'consultations.view' },
      },
      {
        path: 'prescriptions',
        name: 'prescriptions.index',
        component: () => import('@/modules/prescriptions/views/PrescriptionsListView.vue'),
        meta: { permission: 'prescriptions.view' },
      },
      {
        path: 'prescriptions/:id',
        name: 'prescriptions.show',
        component: () => import('@/modules/prescriptions/views/PrescriptionShowView.vue'),
        meta: { permission: 'prescriptions.view' },
      },
      {
        path: 'specialties',
        name: 'specialties.index',
        component: () => import('@/modules/catalogs/views/SpecialtiesView.vue'),
        meta: { permission: 'specialties.view' },
      },
      {
        path: 'medicines',
        name: 'medicines.index',
        component: () => import('@/modules/catalogs/views/MedicinesView.vue'),
        meta: { permission: 'medicines.view' },
      },
      {
        path: 'reports',
        name: 'reports.index',
        component: () => import('@/modules/reports/views/ReportsView.vue'),
        meta: { permission: 'reports.view' },
      },
      {
        path: 'settings',
        name: 'settings.index',
        component: () => import('@/modules/settings/views/ClinicSettingsView.vue'),
        meta: { permission: 'settings.view' },
      },
      {
        path: 'users',
        name: 'users.index',
        component: () => import('@/modules/users/views/UsersListView.vue'),
        meta: { permission: 'users.view' },
      },
      {
        path: 'users/create',
        name: 'users.create',
        component: () => import('@/modules/users/views/UserFormView.vue'),
        meta: { permission: 'users.create' },
      },
      {
        path: 'users/:id/edit',
        name: 'users.edit',
        component: () => import('@/modules/users/views/UserFormView.vue'),
        meta: { permission: 'users.update' },
      },
      {
        path: 'roles',
        name: 'roles.index',
        component: () => import('@/modules/users/views/RolesListView.vue'),
        meta: { permission: 'roles.view' },
      },
      {
        path: 'roles/create',
        name: 'roles.create',
        component: () => import('@/modules/users/views/RoleFormView.vue'),
        meta: { permission: 'roles.create' },
      },
      {
        path: 'roles/:id/edit',
        name: 'roles.edit',
        component: () => import('@/modules/users/views/RoleFormView.vue'),
        meta: { permission: 'roles.update' },
      },
      {
        path: 'audits',
        name: 'audits.index',
        component: () => import('@/modules/audit/views/AuditsListView.vue'),
        meta: { permission: 'audits.view' },
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.initialized) {
    await auth.init()
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  if (to.meta.permission && !auth.can(to.meta.permission)) {
    return { name: 'dashboard' }
  }
})

export default router
