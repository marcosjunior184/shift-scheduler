import { jsonFetch } from '../utils/apiUtils';

const scheduleApi = {
  getTodaySchedules: (date) => jsonFetch(`/api/schedules?date=${encodeURIComponent(date)}`),
  createSchedule: (payload) => jsonFetch('/api/schedules', { method: 'POST', body: JSON.stringify(payload) }),
  updateSchedule: (payload) => jsonFetch('/api/schedules', { method: 'PUT', body: JSON.stringify(payload) }),
  deleteSchedule: (payload) => jsonFetch('/api/schedules', { method: 'DELETE', body: JSON.stringify(payload) }),
}

export { scheduleApi };
