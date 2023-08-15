export default {
  baseUrl: process.env.ACCESSIBILITY_BASE_URL || 'http://web:80/typo3',
  login: {
    admin: {
      username: process.env.ACCESSIBILITY_BACKEND_ADMIN_USERNAME || 'admin',
      password: process.env.ACCESSIBILITY_BACKEND_ADMIN_PASSWORD || 'password'
    }
  }
};
