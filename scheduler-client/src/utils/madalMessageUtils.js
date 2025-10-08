  /**
   * Formats an error object for display.
   * @param {*} err - The error object to format.
   * @returns {string} - The formatted error message.
   */
  export const formatError = (err) => {

    if (!err) return 'Unknown error'
    if (typeof err === 'string') return err
    if (Array.isArray(err)) return ( err[0].data.message || JSON.stringify(err))
    if (err.data) return typeof err.data === 'string' ? err.data : err.data.message + (err.data.errors ? `: ${JSON.stringify(err.data.errors)}` : '')
    if (err.message) return err.message
    try { return JSON.stringify(err) } catch (e) { return String(err) }
  }