import React, { useEffect, useState } from 'react'
import { scheduleApi } from './Schedule.api'
import { staffApi } from '../staff/Staff.api'
import ShiftCard from './ShiftCard'
import './Schedule.css'
import MessageModal from '../components/MessageModal'
import { groupShiftsByRole } from '../utils/scheduleUtils'
import { formatError } from '../utils/madalMessageUtils'

/**
 * Schedule tab component for managing staff schedules.
 * @returns 
 */
export default function ScheduleTab(){
  const [staff, setStaff] = useState([])
  const [roles, setRoles] = useState([])
  const [schedules, setSchedules] = useState([])
  const [groupedSchedules, setGroupedSchedules] = useState({})
  const [originalSchedules, setOriginalSchedules] = useState([])

  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [selectedDate, setSelectedDate] = useState(() => new Date().toISOString().split('T')[0]);
  const [messageModal, setMessageModal] = useState({ open: false, message: '', type: 'error', title: '' })


  /**
   * Use effect to load data when selectedDate changes
   */
  useEffect(()=>{
    setLoading(true)
    load()
  }, [selectedDate])

  /**
   * Use effect to group schedules by role when schedules or roles change.
   * 
   * Api call will return shifts with role info, but we need to regroup them here.
   * so the data that comes as:
   * [ { id: 1, employee: { id: 1, name: 'John Doe', ...}, role: { id: 1, role_name: 'Manager' }, ...}, ... ]
   * 
   * becomes:
   * { role_name: [ { id: 1, employee: { id: 1, name: 'John Doe', ...}, role: { id: 1, role_name: 'Manager' }, ...}, ... ], ... }
   *
   * where each key is a role name and the value is an array of shifts for that role.
   * Each shift still contains the full role object:
   * 
   * That is to facilitate rendering in sections by role.
   */
  useEffect(() => {
    setGroupedSchedules(groupShiftsByRole(schedules, roles));
  }, [schedules, roles])

  /**
   * Deep clones a value. Helper to keep track of changes.
   * @param {*} v - The value to clone.
   * @returns {*} - The cloned value.
   */
  const deepClone = (v) => {
    try { return JSON.parse(JSON.stringify(v)) } catch (e) { return v }
  }

  /**
   * Shows a message modal.
   * @param {*} msg - The message to display.
   * @param {*} opts - Options for the message modal.
   * @returns 
   */
  const showMessage = (msg, opts = {}) => setMessageModal({
     open: true, 
     message: msg || 'An error occurred', type: opts.type || 'error', 
     title: opts.title || '', 
     onClose: opts.onClose
  })

  /**
   * Handles changes to the date input.
   * @param {*} event - The change event from the date input.
   */
  const handleDateChange = (event) => {
    setSelectedDate(event.target.value);
  };

  /**
   * Load staff, roles, and schedules for the selected date.
   */
  const load = () => {
    Promise.all([
      staffApi.getStaff(), 
      staffApi.getRoles(), 
      scheduleApi.getTodaySchedules(selectedDate)
    ])
    .then(([staffRes, rolesRes, schedulesRes]) => {

      const staffData = staffRes?.data || [];
      const rolesData = rolesRes?.data || [];
      let schedulesData = schedulesRes?.data || [];

      // Initialize tracking flags
      schedulesData = schedulesData.map(s => ({ ...s, isChanged: false, isNew: false, isDeleted: false }))

      // Update state
      setStaff(staffData);
      setRoles(rolesData);
      setSchedules(schedulesData);
      setOriginalSchedules(deepClone(schedulesData));
      setGroupedSchedules(groupShiftsByRole(schedulesData, rolesData));
    })
    .catch((err)=>{ 
      console.error(err); 
      showMessage(formatError(err), { type: 'error' }) 
    })
    .finally(()=>setLoading(false)) 
  }
  
  /**
   * Adds an empty shift for the specified role.
   * @param {*} role_name - The name of the role to add a shift for.
   */
  const addEmptyShift = (role_name) => {
    const role = roles.find(r => r.role_name === role_name)

    const newShift = {
      id: `new-${Date.now()}`,
      employee: null,
      role: role,
      shift: "09:00 - 17:00",
      duration: "8 hours",
      date: new Date().toISOString().split('T')[0],
      start_time: "17:00",
      end_time: "22:00",
      assigned_role: role?.id,
      isNew: true,
      isEditing: true
    };

    setSchedules(prev => [...prev, newShift]);
  };

  /**
   * Updates an existing shift.
   * @param {*} role - The role associated with the shift.
   * @param {*} shiftId - The ID of the shift to update.
   * @param {*} changes - The changes to apply to the shift.
   */
  const updateExistingShift = (role, shiftId, changes) => {
    setSchedules(prev => {
      // Find the index of the shift to update
      const changeIdx = prev.findIndex(s => s.id === shiftId)
      const originalIdx = originalSchedules.findIndex(s => s.id === shiftId)
      if (changeIdx === -1) return prev

      // Merge changes
      const oldShift = prev[changeIdx]
      let updatedShift = { ...oldShift, ...changes }

      // If role_id changed, update the role object as well
      if (changes.role_id) {
        const roleObj = roles.find(rr => String(rr.id) === String(changes.role_id))
        updatedShift.role = roleObj
        updatedShift.role_id = roleObj?.role_id
      }

      // If employee changed, update the employee object as well
      if (changes.employee){
        const emp = staff.find(s => s.id === (changes.employee.id))
        updatedShift.employee = emp
        updatedShift.employee_id = emp?.id
      }

      // Reset flags for comparison with original data state
      const wasDeleted = updatedShift.isDeleted
      updatedShift.isChanged = false
      updatedShift.isDeleted = false

      // Compare with original to determine if changed
      const isTheSame = JSON.stringify(originalSchedules[originalIdx]) === JSON.stringify(updatedShift);

      // Update change flags
      updatedShift.isChanged = !isTheSame

      // If changing a shift that was flagged as deleted, unflag it.
      if (updatedShift.isChanged){
        updatedShift.isDeleted = false
      } else {
        updatedShift.isDeleted = wasDeleted
      }

      // Update the schedules array
      const updatedSchedules = prev.map((s, idx) => idx === changeIdx ? updatedShift : s)
      return updatedSchedules
    })
  }

  /**
   * Removes (or flags for removal) a shift by ID.
   * @param {*} shiftId - Shift id to be removed
   */
  const removeShift = (shiftId) => {
    setSchedules(prev => {
      // Find the index of the shift to remove
      const removeIdx = prev.findIndex(s => s.id === shiftId)
      if (removeIdx === -1) return prev

      // Update or remove the shift
      const updatedShift = prev.map((s, idx) => {
        if (idx === removeIdx) {
          // If new, remove it from the list
          if (s.isNew) {
            return null;
          } else {
            return { ...s, isDeleted: !s.isDeleted };
          }
        } else return s;
      }).filter(Boolean)
      return updatedShift
    })
  };

  /**
   * Saves the current shifts to the server.
   * The function identifies shifts to add, update, or delete based on their flags.
   * It constructs payloads for each operation and sends them to the server using
   * the appropriate API calls. The function handles success and error responses,
   * 
   * @returns {Promise<void>}
   */
  const saveShift = async () => {
    // Identify shifts to add, update, or delete
    const shiftToAdd = schedules.filter(s => s.isNew && !s.isDeleted)
    const shiftToUpdate = schedules.filter(s => s.isChanged && !s.isNew && !s.isDeleted)
    const shiftToDelete = schedules.filter(s => s.isDeleted && !s.isNew)

    // If no changes, inform the user and return
    if (shiftToAdd.length === 0 && shiftToUpdate.length === 0 && shiftToDelete.length === 0){
      showMessage('No changes to save', { type: 'info', title: 'Info' })
      return;
    }

    // Construct payloads
    const addPayload = { shifts: shiftToAdd.map(s => ({
      date: s.date,
      start_time: s.start_time,
      end_time: s.end_time,
      employee_id: s.employee?.id,
      assigned_role: s.assigned_role 
    }))};

    const updatePayload = { shifts: shiftToUpdate.map(s => ({
      id: s.id,
      date: s.date,
      start_time: s.start_time,
      end_time: s.end_time,
      employee_id: s.employee?.id,
      assigned_role: s.assigned_role
    }))};

    const deletePayload = { shifts: shiftToDelete.map(s => ({ id: s.id })) };

    try {
      setSaving(true)
      // Perform all operations concurrently
      const results = await Promise.allSettled([
        ...(addPayload.shifts.length > 0 ? [scheduleApi.createSchedule(addPayload)] : []),
        ...(updatePayload.shifts.length > 0 ? [scheduleApi.updateSchedule(updatePayload)] : []),
        ...(deletePayload.shifts.length > 0 ? [scheduleApi.deleteSchedule(deletePayload)] : [])
      ]);

      // Check for failure
      const failedOperations = results.filter(result => result.status === 'rejected');
      setSaving(false)
      // If any operation failed, show error and return
      if (failedOperations.length > 0) {
        showMessage(formatError(failedOperations.map(op => op.reason)), { type: 'error' })
        return;
      }

      // All operations succeeded inform user and reload data
      showMessage('All changes saved successfully', { type: 'success', title: 'Success', onClose: load()})
      return 

    } catch (error) {
      showMessage(formatError(error), { type: 'error' })
      console.error('Error saving shifts:', error);
      return 
    }
  }

  return (
    <section className="card">
      <div style={{ display: 'flex', gap: '2rem'}}>

        <div>
          <h2>Schedules</h2>
          <form className="form">

              <label>Select Date: 
                <input name="date" className='form' type="date" value={selectedDate} onChange={handleDateChange} required/>
              </label>

              <div style={{ marginTop: '0.5rem' }}>
                <button type="button" className="btn btn-primary" onClick={(e) => saveShift()} disabled={saving}>
                  {saving ? 'Saving...' : 'Save shifts'}
                </button>
              </div>

          </form>
        </div>

        {loading && <div className="loading">Loading...</div>}
      </div>

      {loading && <p>Loading...</p>}
      {!loading && (
            <div className="schedules-container">

              {Object.entries(groupedSchedules).map(([roleName, shifts]) => (

                <div key={roleName} className="role-section">

                  <div className="section-header" >
                    <div className="header-content">
                      <h2>{roleName.toUpperCase()}</h2>
                      <span className="shift-count">{shifts.length} shifts</span>
                    </div>

                    <button className="btn btn-primary" onClick={() => addEmptyShift(roleName)}>
                      <span className="icon">+</span>
                      Add {roleName} Shift
                    </button>
                  </div>

                  <div className="shifts-grid">
                    {shifts.map(shift => (
                      <div key={shift.id} className={`shift-card ${shift.isNew ? 'new' : ''}`}>
                        <ShiftCard
                          shift={shift}
                          staff={staff}
                          roles={roles}
                          onRemove={(id) => removeShift(id)}
                          onChange={(changes) => updateExistingShift(roleName, shift.id, changes)}
                        />
                      </div>
                    ))}
                  </div>

                </div>
              ))}
            </div>
      )}
      <MessageModal 
        open={messageModal.open} 
        message={messageModal.message} 
        type={messageModal.type} 
        title={messageModal.title} 
        onClose={() => setMessageModal({ 
          open: false, 
          message: '', 
          type: 'error', 
          title: '' })}
      />
    </section>
  )
}
