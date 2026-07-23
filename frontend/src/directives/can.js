/**
 * v-can="'users.create'" — hides the element when the user lacks the
 * permission. This is UX only; every mutating API call is still gated
 * by Policies on the backend.
 */
export function canDirective(authStore) {
  const apply = (el, binding) => {
    const allowed = authStore.can(binding.value)
    el.style.display = allowed ? '' : 'none'
    el.toggleAttribute('disabled', !allowed)
  }

  return {
    mounted: apply,
    updated: apply,
  }
}
