import { reactive, ref } from 'vue'
import api from '@/api/axios'

/**
 * Shared CRUD state machine for catalog pages.
 * Keeps list/search/pagination/modal form logic out of each view (DRY).
 */
export function useCatalog(resource) {
  const items = ref([])
  const meta = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 })
  const loading = ref(false)
  const saving = ref(false)
  const error = ref(null)
  const errors = ref({})
  const filters = reactive({ search: '', page: 1 })

  const modalOpen = ref(false)
  const editing = ref(null)

  async function load() {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get(`/${resource}`, {
        params: {
          search: filters.search || undefined,
          page: filters.page,
        },
      })
      items.value = data.data.items
      meta.value = data.data.meta
    } catch (e) {
      error.value = e.response?.data?.message || 'No se pudo cargar la lista.'
    } finally {
      loading.value = false
    }
  }

  function search() {
    filters.page = 1
    return load()
  }

  function setPage(page) {
    filters.page = page
    return load()
  }

  function openCreate(defaults = {}) {
    editing.value = null
    errors.value = {}
    modalOpen.value = true
    return { ...defaults }
  }

  function openEdit(row) {
    editing.value = row
    errors.value = {}
    modalOpen.value = true
    return { ...row }
  }

  function closeModal() {
    modalOpen.value = false
    editing.value = null
    errors.value = {}
  }

  async function save(payload) {
    saving.value = true
    errors.value = {}
    error.value = null
    try {
      if (editing.value?.id) {
        await api.put(`/${resource}/${editing.value.id}`, payload)
      } else {
        await api.post(`/${resource}`, payload)
      }
      closeModal()
      await load()
      return true
    } catch (e) {
      if (e.response?.status === 422) {
        errors.value = e.response.data.errors || {}
      } else {
        error.value = e.response?.data?.message || 'No se pudo guardar.'
      }
      return false
    } finally {
      saving.value = false
    }
  }

  async function remove(row, label = 'este registro') {
    if (!confirm(`¿Eliminar ${label}?`)) return
    try {
      await api.delete(`/${resource}/${row.id}`)
      await load()
    } catch (e) {
      alert(e.response?.data?.message || e.response?.data?.errors?.resource?.[0] || 'No se pudo eliminar.')
    }
  }

  // reactive() so nested refs unwrap in templates (catalog.modalOpen, catalog.saving, …).
  // A plain object keeps Ref instances nested → BaseModal :open stays truthy and Cancelar looks broken.
  return reactive({
    items,
    meta,
    loading,
    saving,
    error,
    errors,
    filters,
    modalOpen,
    editing,
    load,
    search,
    setPage,
    openCreate,
    openEdit,
    closeModal,
    save,
    remove,
  })
}
