import './Schedule.css'

/**
 * ShiftCard component for displaying and editing a single shift.
 * @param {*} shift - shift object
 * @param {*} staff - array of staff members
 * @param {*} roles - array of roles
 * @param {*} onChange - callback function for handling changes
 * @param {*} onRemove - callback function for handling removal
 * @returns 
 */
export default function ShiftCard({ shift, staff = [], roles = [], onChange, onRemove }) {

  const handleEmployeeChange = (e) => {
    const val = e.target.value
    const employee = staff.find(s => String(s.id) === String(val)) || null
    onChange && onChange({ employee })
  }


  const handleStartChange = (e) => {
    onChange && onChange({ start_time: e.target.value })
  }

  const handleEndChange = (e) => {
    onChange && onChange({ end_time: e.target.value })
  }

  const handleRoleChange = (e) => {
    onChange && onChange({ role_id: e.target.value })
  }

  const handleRemove = (e) => {
    onRemove && onRemove(shift.id)
  }

  const backgroundColor = shift.isNew ? '#9df671ff' : (shift.isChanged && !shift.isDeleted) ? '#fdf489ff' : shift.isDeleted ? '#ff7a6eff' : '#fff'

  return (
    <div className="existing-shift" style={{ 
        border: '1px solid #ccc', 
        padding: '8px', 
        borderRadius: '4px', 
        display: 'flex', 
        alignItems: 'center', 
        gap: '12px',  
        backgroundColor: backgroundColor }}>

      <div style={{ flex: 2 }} className='shift-item'>
        <label className="muted">Employee:
        <select value={shift.employee?.id ?? ''} onChange={(e) => handleEmployeeChange(e)} className="form-select">
          <option value="">Unassigned</option>
          {staff.map(s => <option key={s.id} value={s.id}>{s.name}</option>)}
        </select>
        </label>
      </div>

      <div style={{ flex: 1}} className='shift-item'>
        <label className="muted">Role:
          <select name="role" value={shift.role.id} onChange={(e) => handleRoleChange(e)} required>
            <option value="">Select role</option>
            {roles.map(r => <option key={r.id} value={r.id}>{r.role_name}</option>)}
          </select>
        </label>
      </div>

      <div style={{ flex: 1}} className='shift-item'>
        <label className="muted">Start:
          <input type="time" value={shift.start_time ?? shift.start ?? ''} onChange={(e) => handleStartChange(e)} />
        </label>
      </div>

      <div style={{ flex: 1}} className='shift-item'>
        <label className="muted">End:
          <input type="time" value={shift.end_time ?? shift.end ?? ''} onChange={(e) => handleEndChange(e)} />
        </label>
      </div>

      <div style={{ flex: 1, textAlign: 'right' , alignSelf: 'flex-start'}} className='shift-item'>
        {!shift.isDeleted && <button aria-label="Close" className="modal-close" onClick={(e) => handleRemove(e)}>✕</button>}
        {shift.isDeleted && <button aria-label="Close" className="modal-close" onClick={(e) => handleRemove(e)}>Undo</button>}
      </div>
    </div>
  )
}
