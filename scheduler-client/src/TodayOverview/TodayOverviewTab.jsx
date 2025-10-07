import React, { useEffect, useState } from 'react'
import { scheduleApi } from '../Schedule/Schedule.api'
import './TodayOverview.css'

/**
 * TodayOverview component
 * 
 */
export default function TodayOverview(){
  const [schedules, setSchedules] = useState([])
  const [loading, setLoading] = useState(true)

  const date = new Date().toISOString().slice(0,10)
  const format = { day: 'numeric', month: 'long', year: 'numeric' }
  const formattedDate = new Date(date).toLocaleDateString(undefined, format)

  /**
   * Fetch today's schedules for overview
   */
  useEffect(() => {
    scheduleApi.getTodaySchedules(date)
      .then(res => { if(res && res.data) setSchedules(res.data) })
      .catch(() => {})
      .finally(()=>setLoading(false))
  }, [])

  return (
    <section className="card">
  <h2>Today's Overview ({formattedDate})</h2>
      {loading && <p>Loading...</p>}
      {!loading && schedules.length === 0 && <p>No shifts scheduled for today.</p>}
      {!loading && schedules.length > 0 && (
        <ul className="list">
          {schedules.map(s => (
            <li key={s.id} className="list-item">
              <div>
                <strong>{s.employee?.name ?? 'Unassigned'}</strong>
                <div className="muted">{s.role?.role_name ?? ''}</div>
              </div>
              <div>{s.start_time} — {s.end_time}</div>
            </li>
          ))}
        </ul>
      )}
    </section>
  )
}
