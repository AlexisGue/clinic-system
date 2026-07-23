/**
 * Money display for the SPA. Defaults to USD (en-US).
 * @param {number|string|null|undefined} value
 * @param {{ currency?: string, locale?: string }} [opts]
 */
export function formatMoney(value, opts = {}) {
  const currency = opts.currency || 'USD'
  const locale = opts.locale || 'en-US'
  const amount = Number(value || 0)

  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number.isFinite(amount) ? amount : 0)
}

/**
 * @param {string|Date|null|undefined} iso
 */
export function formatDateTime(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('en-US', {
    dateStyle: 'short',
    timeStyle: 'short',
  })
}

/**
 * @param {string|Date|null|undefined} iso
 */
export function formatDate(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('en-US', {
    dateStyle: 'short',
  })
}
