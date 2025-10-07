const BASE = 'http://localhost:8000';

const staffApi = {
  async getStaff() {
    const res = await fetch(`${BASE}/api/staff`);
    return res.json();
  },

  async getRoles() {
    const res = await fetch(`${BASE}/api/roles`);
    return res.json();
  },

  async createStaff(payload) {
    const res = await fetch(`${BASE}/api/staff`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    return res.json();
  }
}

export { staffApi };
