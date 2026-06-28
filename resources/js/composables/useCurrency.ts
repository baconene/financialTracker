export function useCurrency() {
  function formatPHP(amount: number): string {
    return new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(amount)
  }

  function formatNumber(amount: number): string {
    return new Intl.NumberFormat('en-PH', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(amount)
  }

  function formatShort(amount: number): string {
    if (amount >= 1_000_000) {
      return `₱${(amount / 1_000_000).toFixed(1)}M`
    } else if (amount >= 1_000) {
      return `₱${(amount / 1_000).toFixed(1)}K`
    }
    return `₱${amount.toFixed(2)}`
  }

  return { formatPHP, formatNumber, formatShort }
}
