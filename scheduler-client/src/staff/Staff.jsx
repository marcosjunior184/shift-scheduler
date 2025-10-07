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
  const [rowSelectionModel, setRowSelectionModel] = useState([]);
  const [selectedRows, setSelectedRows] = useState([]);

  const columns = [
    { field: "name", headerName: "Name", minWidth: 200, flex:2},
    { field: "phone_number", headerName: "Phone Number", minWidth: 150, flex:2 },
    { field: "email", headerName: "Email", minWidth: 210, flex:2 }
  ];
  
  useEffect(()=>{
    setLoading(true)
    Promise.all([staffApi.getStaff(), staffApi.getRoles()])
      .then(([sRes, rRes]) => {
        setStaff(sRes?.data || [])
        setRoles(rRes?.data || [])
      })
      .catch(() => {})
      .finally(()=>setLoading(false))
  }, [])

  function handleChange(e){
    setForm({...form, [e.target.name]: e.target.value})
  }

  const handleRowClick = (params) => {
    setSelectedRow(params.row);
    setForm({
        name: params.row.name, 
        email: params.row.email, 
        phone_number: 
        params.row.phone_number, 
        role_id: params.row.role_id});

  };

  const handleClearForm = (_) => {

    setSelectedRow(null);
    setForm({
        name: "", 
        email: "", 
        phone_number: "" ,
        role_id: ""});

  };

  async function handleSubmit(e){
    e.preventDefault()
    setSaving(true)
    try{
  await staffApi.createStaff({
        name: form.name,
        email: form.email,
        phone_number: form.phone_number,
        role_id: form.role_id,
        start_date: new Date().toISOString().slice(0,10)
      })
      // refresh
  const res = await staffApi.getStaff()
  setStaff(res?.data || [])
      setForm({ name: '', email: '', phone_number: '', role_id: '' })
      // close modal if open
      if (showModal) setShowModal(false)
    }catch(err){
      console.error(err)
      alert('Failed to save staff (see console)')
    }finally{setSaving(false)}
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
                <button onClick={() => { handleClearForm();  setShowModal(true)}}>Create new staff</button>
                <button style={{ marginLeft: 8 }} disabled={!selectedRow}
                  onClick={() => { setShowModal(true)}}>Edit staff</button>
          </div>
      </div>
    }


    <StaffModal
      show={showModal}
      onClose={() => setShowModal(false)}
      form={form}
      handleChange={handleChange}
      handleSubmit={handleSubmit}
      saving={saving}
      roles={roles}
    />
    </section>
  )
}


