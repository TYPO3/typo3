export default {
  // For the sake of using relative urls in page.goto('module/web/layout') the trailing slash is needed
  baseUrl: process.env.PLAYWRIGHT_BASE_URL || 'http://web:80/typo3/',
  login: {
    admin: {
      username: process.env.ACCESSIBILITY_BACKEND_ADMIN_USERNAME || 'admin',
      password: process.env.ACCESSIBILITY_BACKEND_ADMIN_PASSWORD || 'password'
    }
  }
};
