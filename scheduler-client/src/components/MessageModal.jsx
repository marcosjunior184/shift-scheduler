import React from 'react'

const typeConfig = {
  error: { color: '#b91c1c', title: 'ERROR',  },
  success: { color: '#15803d', title: 'SUCCESS',  },
  info: { color: '#0ea5e9', title: 'INFO', }
}

export default function MessageModal({ open = false, message = '', onClose = () => {}, type = 'error', title }) {
  if (!open) return null

  const cfg = typeConfig[type] || typeConfig.error
  const heading = title || cfg.title

  const handleClose = (e) => {
    if (e.target === e.currentTarget){
        if (!onClose){
            open = false
        }else{
            onClose()
        }
    }
  }

  return (
    <div className="modal-overlay" onClick={(e) => handleClose(e)}>
      <div className="modal-dialog" role="dialog" aria-modal="true">
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>

            <h3 style={{ margin: 0, color: cfg.color }}>{heading}</h3>
          </div>
          <button aria-label="Close" className="modal-close" onClick={onClose}>✕</button>
        </div>

        <div style={{ marginTop: 12 }}>
          <p style={{ margin: 0, color: '#111' }}>{message}</p>
        </div>
      </div>
    </div>
  )
}
