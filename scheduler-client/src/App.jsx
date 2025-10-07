import React, { useEffect, useState } from 'react'
import TodayOverview from './components/TodayOverviewTab'
import StaffTab from './staff/Staff'
import ScheduleTab from './components/ScheduleTab'

export default function App() {
  const [tab, setTab] = useState(() => {
    try {
      return localStorage.getItem('activeTab') || 'today'
    } catch (e) {
      return 'today'
    }
  })

  useEffect(() => {
    try {
      localStorage.setItem('activeTab', tab)
    } catch (e) {
      // ignore storage errors
    }
  }, [tab])

  return (
    <div className="app">
      <header className="header">
        <h1>Restaurant Scheduler</h1>
        <nav>
          <button className={tab === 'today' ? 'active' : ''} onClick={() => setTab('today')}>Today</button>
          <button className={tab === 'staff' ? 'active' : ''} onClick={() => setTab('staff')}>Staff</button>
          <button className={tab === 'schedules' ? 'active' : ''} onClick={() => setTab('schedules')}>Schedules</button>
        </nav>
      </header>

      <main className="main">
        {tab === 'today' && <TodayOverview />}
        {tab === 'staff' && <StaffTab />}
        {tab === 'schedules' && <ScheduleTab />}
      </main>

      <footer className="footer">Backend: http://localhost:8000</footer>
    </div>
  )
}
