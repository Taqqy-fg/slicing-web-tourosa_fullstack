/**
 * @typedef {Object} OrderItem
 * @property {string} cat
 * @property {string|null} vendor
 * @property {string} desc
 * @property {number|string} qty
 * @property {number|string} cost
 * @property {number|string} price
 */

/**
 * @typedef {Object} OrderExpense
 * @property {string} label
 * @property {number|string} amount
 */

/**
 * @typedef {Object} OrderTerm
 * @property {string} label
 * @property {number|string} percent
 * @property {string|null} due
 */

/**
 * @typedef {Object} Order
 * @property {string} no
 * @property {string} date
 * @property {string} group
 * @property {string} pic
 * @property {string} contact
 * @property {string} dest
 * @property {string|null} depart
 * @property {string|null} ret
 * @property {number|string} pax
 * @property {string} status 'Lunas' | 'DP' | 'Pending'
 * @property {number|string} discount
 * @property {number|string} taxPercent
 * @property {number|string} dpPercent
 * @property {string} notes
 * @property {OrderItem[]} items
 * @property {OrderExpense[]} expenses
 * @property {OrderTerm[]} terms
 */

/**
 * @typedef {Object} Catalog
 * @property {string} cat
 * @property {string[]} items
 */

/**
 * @typedef {Object} SiteSettings
 * @property {string} waNumber
 * @property {string} email
 * @property {string} address
 * @property {string} tagline
 * @property {Array<{n: string, l: string}>} stats
 * @property {Array<{name: string, img: string|null}>} clients
 */

/**
 * @typedef {Object} DashboardResponse
 * @property {Order[]} orders
 * @property {Catalog[]} catalog
 * @property {SiteSettings} site
 */

export default {}; // Export empty object to make this a module
