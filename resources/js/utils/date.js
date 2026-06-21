/**
 * Date formatting utilities.
 *
 * Locale is controlled via the VITE_DATE_LOCALE environment variable.
 * Defaults to 'it-IT' (Italian) if not set.
 *
 * Example in .env:
 *   VITE_DATE_LOCALE=it-IT
 */

const DATE_LOCALE = import.meta.env.VITE_DATE_LOCALE ?? 'it-IT'

/**
 * Format a date value as a localized date string (day, month, year).
 *
 * @param {string|Date|number} value - Date input (ISO string, Date object, or timestamp)
 * @returns {string} Formatted date, e.g. "20 marzo 2026"
 */
export function formatDate(value) {
    if (!value) return '—'
    const date = value instanceof Date ? value : new Date(value)
    if (isNaN(date.getTime())) return '—'
    return new Intl.DateTimeFormat(DATE_LOCALE, {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date)
}

/**
 * Format a date value as a localized date+time string.
 *
 * @param {string|Date|number} value - Date input (ISO string, Date object, or timestamp)
 * @returns {string} Formatted datetime, e.g. "20 marzo 2026, 14:35"
 */
export function formatDateTime(value) {
    if (!value) return '—'
    const date = value instanceof Date ? value : new Date(value)
    if (isNaN(date.getTime())) return '—'
    return new Intl.DateTimeFormat(DATE_LOCALE, {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date)
}
