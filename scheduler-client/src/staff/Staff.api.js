import { jsonFetch } from '../utils/apiUtils';

const staffApi = {
  getStaff: () => jsonFetch('/api/staff'),
  createStaff: (payload) => jsonFetch('/api/staff', { method: 'POST', body: JSON.stringify(payload) }),
  updateStaff: (payload, id) => jsonFetch(`/api/staff/${id}`, { method: 'PUT', body: JSON.stringify(payload) }),
  deleteStaff: (id) => jsonFetch(`/api/staff/${id}`, { method: 'DELETE' }),
  getRoles: () => jsonFetch('/api/roles')
}

export { staffApi };
