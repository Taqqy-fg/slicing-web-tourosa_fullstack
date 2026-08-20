export function useDashboardData() {
  const fmt = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(Number(n) || 0))
  const fmtNum = (n) => new Intl.NumberFormat('id-ID').format(Math.round(Number(n) || 0))
  const fmtDate = (iso) => {
    if (!iso) return '-'
    const p = String(iso).split('-'); if (p.length < 3) return iso;
    const b = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
    return Number(p[2]) + ' ' + b[Number(p[1]) - 1] + ' ' + p[0]
  }
  const fmtShort = (iso) => {
    if (!iso) return '-'
    const p = String(iso).split('-'); if (p.length < 3) return iso;
    const b = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
    return Number(p[2]) + ' ' + b[Number(p[1]) - 1] + ' ' + p[0]
  }

  const calc = (o) => {
    const items = (o.items || []).map(it => {
      const qty = Number(it.qty) || 0
      const price = Number(it.price) || 0
      const cost = Number(it.cost) || 0
      const markupCost = Number(it.markupCost) || 0
      const markupPrice = Number(it.markupPrice) || 0
      const unitCost = cost + markupCost
      const unitPrice = markupPrice
      return {
        ...it, qty, price, cost, markupCost, markupPrice,
        unitCost, unitPrice,
        line: qty * unitPrice,
        lineCost: qty * unitCost,
        lineProfit: qty * (unitPrice - unitCost)
      }
    })

    const subtotal = items.reduce((s, it) => s + it.line, 0)
    const totalCost = items.reduce((s, it) => s + it.lineCost, 0)
    const totalExpenses = (o.expenses || []).reduce((s, e) => s + (Number(e.amount) || 0), 0)

    const discount = Number(o.discount) || 0
    const discountType = o.discountType || 'Rp'
    const discountAmount = discountType === '%' ? Math.round(subtotal * discount / 100) : discount

    const afterDisc = Math.max(0, subtotal - discountAmount)

    const serviceFee = Number(o.serviceFee) || 0

    const taxPercent = Number(o.taxPercent) || 0
    const tax = Math.round(afterDisc * taxPercent / 100)

    const grandTotal = afterDisc + serviceFee + tax

    const pax = Number(o.pax) || 0
    const perPax = pax ? grandTotal / pax : 0

    const dpPercent = Number(o.dpPercent) || 0
    const dp = Math.round(grandTotal * dpPercent / 100)
    const sisa = Math.max(0, grandTotal - dp)

    const profit = afterDisc - totalCost - totalExpenses
    const marginPct = afterDisc ? (profit / afterDisc * 100) : 0

    return {
      items, subtotal, totalCost, totalExpenses,
      discount, discountType, discountAmount,
      afterDisc, serviceFee,
      taxPercent, tax, grandTotal,
      pax, perPax, dpPercent, dp, sisa,
      profit, marginPct
    }
  }

  const statusMeta = (st) => {
    if (st === 'Lunas') return { bg: '#e6f4ec', color: '#1f7a5c' };
    if (st === 'DP') return { bg: '#fbf1dc', color: '#9a7320' };
    return { bg: '#eef0f3', color: '#5f6b80' };
  }

  return {
    fmt,
    fmtNum,
    fmtDate,
    fmtShort,
    calc,
    statusMeta
  }
}
