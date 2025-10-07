import React, { useEffect, useState } from 'react'
import { DataGrid } from "@mui/x-data-grid";
import { staffApi } from './Staff.api'
import './Staff.css'
import StaffModal from './StaffModal'

export default function StaffTab(){
  const [staff, setStaff] = useState([])
  const [roles, setRoles] = useState([])
  const [selectedRow, setSelectedRow] = useState(null);
  const [form, setForm] = useState({ name: '', email: '', phone_number: '', role_id: '' })
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [showModal, setShowModal] = useState(false)
  const [isNew, setIsNew] = useState(true)

  const columns = [
    { field: "name", headerName: "Name", minWidth: 200, flex:2},
    { field: "phone_number", headerName: "Phone Number", minWidth: 150, flex:2 },
    { field: "email", headerName: "Email", minWidth: 210, flex:2 }
  ];
  
  /**
   * Fetch staff and roles on mount
   */
  useEffect(()=>{
    load();
  }, [])


  const load = () => {
    setLoading(true)
    Promise.all([staffApi.getStaff(), staffApi.getRoles()])
      .then(([sRes, rRes]) => {

    setStaff(sRes?.data || [])
    setRoles(rRes?.data || [])
  })
  .catch(() => {})
  .finally(()=>setLoading(false))
}

  /**
   * Handle form field changes
   * @param {*} e 
   */
  function handleChange(e){
    setForm({...form, [e.target.name]: e.target.value})
  }

  /**
   * Handle row click - should update form.
   * @param {*} params 
   */
  const handleRowClick = (params) => {
    setSelectedRow(params.row);
    
    const start_date = params.row.start_date ? 
      params.row.start_date.slice(0,10) : 
      new Date().toISOString().slice(0,10);

    setForm({
        name: params.row.name, 
        email: params.row.email, 
        phone_number: params.row.phone_number, 
        role_id: params.row.role_id,
        start_date: start_date
    });
    setIsNew(false);
  };

  /**
   * Clear the form and selection
   * @param {*} _ 
   */
  const handleClearForm = (_) => {
    setSelectedRow(null);
    setForm({
        name: "", 
        email: "", 
        phone_number: "" ,
        role_id: ""});

    setIsNew(true);
    setShowModal(true);

  };

  /**
   * Handle form submission
   * @param {*} e 
   */
  async function handleSubmit(e){
    e.preventDefault()
    setSaving(true)
    try{

      if (isNew){
        await staffApi.createStaff({
          name: form.name,
          email: form.email,
          phone_number: form.phone_number,
          role_id: form.role_id,
          start_date: form.start_date
        })
      }else{
        await staffApi.updateStaff({
          id: selectedRow.id,
          name: form.name,
          email: form.email,
          phone_number: form.phone_number,
          role_id: form.role_id,
          start_date: form.start_date
        }, selectedRow.id)
      }
      load()
      setForm({ name: '', email: '', phone_number: '', role_id: '' })
      setSelectedRow(null)
      setIsNew(true)
      if (showModal) setShowModal(false)
    }catch(err){
      console.error(err)
      alert('Failed to save staff (see console)')
    }finally{setSaving(false)}
  }

  async function handleDelete(e){
    e.preventDefault()
    if (isNew || !selectedRow) return;
    if (!window.confirm('Are you sure you want to delete this staff member?')) return;

    setSaving(true)
    try{
      await staffApi.deleteStaff(selectedRow.id)
      load()
      setSelectedRow(null)
      setIsNew(true)
      if (showModal) setShowModal(false)
    }catch(err){
      console.error(err)
      alert('Failed to delete staff (see console)')
    }finally{
      setSaving(false)
    }
  }


  return (
    
    <section className="card">
      <h2>Staff</h2>
      {loading && <p>Loading...</p>}
      {!loading && staff.length === 0 && <p>No staff found</p>}
      {!loading && staff.length > 0 &&
      <div>
          <DataGrid
            rows={staff}
            columns={columns}
            pageSize={25}
            rowsPerPageOptions={[25, 50, 75]}
            className='staff-grid'
            pagination
            onRowClick={handleRowClick}
            onRowDoubleClick={() => setShowModal(true)}/>

          <div style={{ width: '100%', marginBottom: 12 }}>

                <button onClick={() => handleClearForm()}>Create new staff</button>
                <button style={{ marginLeft: 8 }} disabled={!selectedRow} onClick={() => { setShowModal(true)}}>Edit staff</button>

          </div>
      </div>
    }


    <StaffModal
      show={showModal}
      onClose={() => setShowModal(false)}
      form={form}
      OnChange={handleChange}
      OnSubmit={handleSubmit}
      onDelete={handleDelete}
      saving={saving}
      roles={roles}
      isNew={isNew}
    />
    </section>
  )
}


