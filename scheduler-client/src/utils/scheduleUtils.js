/**
 * Groups shifts by their role.
 * @param {*} shifts - The shifts to group.
 * @param {*} roles - The roles to consider.
 * @returns {*} - The grouped shifts.
 */
export const groupShiftsByRole = (shifts, roles = []) => {
  // Normalize shifts into an array (handle undefined, objects, etc.)
  const arr = Array.isArray(shifts) ? shifts : (shifts ? Object.values(shifts).flat() : [])

  // Initialize grouped with all roles so caller always gets a bucket for each role
  const grouped = (roles || []).reduce((acc, r) => {
    acc[r.role_name] = []
    return acc
  }, {})

  // Place shifts into buckets
  arr.forEach(shift => {
    const roleName = shift?.role?.role_name || 'Unassigned'
    if (!grouped[roleName]) grouped[roleName] = []
    grouped[roleName].push(shift)
  })

  // Return sorted by role name for stable UI ordering
  return Object.fromEntries(
    Object.entries(grouped).sort(([a], [b]) => String(a).localeCompare(String(b)))
  )
}