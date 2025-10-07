import { createPortal } from 'react-dom'

export default function StaffModal({ show, onClose, form, OnChange, OnSubmit, onDelete, saving, roles}) {
  if (!show) return null

  const handleClose = (e) => {
    if (e.target.classList.contains('modal-overlay'))
    {
        onClose();
    }
  }

  const handleChange = (e) => {
    OnChange(e);
  }

  const handleSubmit = (e) => {
      OnSubmit(e);
  }

  const handleDelete = (e) => {
      onDelete(e);
  }

  return (
    <div
      className="modal-overlay"
      onClick={(e) => handleClose(e)}>

      <div className="modal-dialog" role="dialog" aria-modal="true">
        <div style={{ 
            display: 'flex', 
            justifyContent: 'space-between', 
            alignItems: 'center' }}>

          <h3>Create staff</h3>
          <button aria-label="Close" className="modal-close" onClick={() => onClose()}>✕</button>

        </div>

        <form onSubmit={handleSubmit} className="form">

          <label>Name 
            <input name="name" value={form.name} onChange={handleChange} required/>
          </label>
          <label>Email 
            <input name="email" value={form.email} onChange={handleChange} type="email" required/>
          </label>
          <label>Phone 
            <input name="phone_number" value={form.phone_number} onChange={handleChange}/>
          </label>
          <label>Role
            <select name="role_id" value={form.role_id} onChange={handleChange} required>
              <option value="">Select role</option>
              {roles.map(r => <option key={r.id} value={r.id}>{r.role_name}</option>)}
            </select>
          </label>
          <label>Start Date: 
              <input name="start_date" className='form' type="date" value={form.start_date} onChange={handleChange} required/>
          </label>

          <div style={{ display: 'flex', justifyContent: 'space-between', gap: 8, marginTop: 12 }}>
            <button type="submit" disabled={saving}>{saving ? 'Saving...' : 'Save Staff'}</button>
            <button type="button" onClick={(e) => handleDelete(e)}>Delete</button>
            <button type="button" onClick={() => onClose()}>Cancel</button>
          </div>
        </form>
      </div>
    </div>
    
  )
}
