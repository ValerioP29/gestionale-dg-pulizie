// Imposta VITE_API_BASE_URL nell'ambiente (es. https://app.dgpulizie.it in produzione)
export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '';

export const ENDPOINTS = {
  login: '/api/login',
  logout: '/api/logout',
  registerEmployee: '/api/register/employee',
  me: '/api/me',
  current: '/api/mobile/work-sessions/current',
  punch: '/api/mobile/work-sessions/punch',
  payrollList: '/api/mobile/payroll',
  payrollDownload: (id) => `/api/mobile/payroll/${id}/download`,
};

