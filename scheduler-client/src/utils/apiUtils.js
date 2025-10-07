const BASE = 'http://localhost:8000'

/**
 * Fetches JSON data from a REST API endpoint.
 * @param {*} path - The API endpoint path.
 * @param {*} opts - Fetch options.
 * @returns {Promise<*>} - The JSON response from the API.
 */
export async function jsonFetch(path, opts = {}) {
  const res = await fetch(BASE + path, {
    headers: { 'Content-Type': 'application/json' },
    ...opts,
  })
  const text = await res.text()
  try {
    const data = text ? JSON.parse(text) : null
    if (!res.ok) throw { status: res.status, data }
    return data
  } catch (err) {
    if (err instanceof SyntaxError) {
      // non-json response
      if (!res.ok) throw { status: res.status, data: text }
      return text
    }
    throw err
  }
}