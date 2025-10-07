
# Scheduler Client (frontend)

This is the React + Vite frontend for the Shift Scheduler application. It talks to the `scheduler-service` backend API and provides a small UI to manage staff and schedules.

## Requirements

- Node.js 16+ and npm (or yarn)
- A running instance of the `scheduler-service` API (by default at http://localhost:8000)

## Setup

1. **Install dependencies**

	npm install

2. **Environment**

	By default api connection is set as:

	REACT_APP_API_BASE=http://localhost:8000

	If your API runs on another host/port, update `REACT_APP_API_BASE` accordingly.

## Run (development)

Start the Vite dev server (with HMR):

```bash
npm run dev -- --host
```

Open the app at the URL Vite prints (commonly http://localhost:5173). The client will call the API at `REACT_APP_API_BASE`.


## Example local flow

1. Start backend from `scheduler-service`:

	php -S localhost:8000 -t public

2. Start frontend (from project root `scheduler-client`):

	npm run dev -- --host

3. Open the client in your browser and create staff/roles/shifts via the UI, or use curl examples in `scheduler-service/README.md`.