export function useCurrency() {
  function formatPHP(amount: number | string | null | undefined): string {
    const num = parseFloat(String(amount ?? 0)) || 0
    return new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(num)
  }

  function formatNumber(amount: number | string | null | undefined): string {
    const num = parseFloat(String(amount ?? 0)) || 0
    return new Intl.NumberFormat('en-PH', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(num)
  }

  function formatShort(amount: number | string | null | undefined): string {
    const num = parseFloat(String(amount ?? 0)) || 0
    if (num >= 1_000_000) {
      return `₱${(num / 1_000_000).toFixed(1)}M`
    } else if (num >= 1_000) {
      return `₱${(num / 1_000).toFixed(1)}K`
    }
    return `₱${num.toFixed(2)}`
  }

  return { formatPHP, formatNumber, formatShort }
}
