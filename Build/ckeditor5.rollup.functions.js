/**
 * duplicated from https://github.com/egoist/style-inject/blob/04ca45c34f20f0aa63d3d68e668de037d24579ad/src/index.js
 * extended by nonce capabilities
 */
export default function styleInject(css, { insertAt } = {}) {
  if (!css || typeof document === 'undefined') return

  const head = document.head || document.getElementsByTagName('head')[0]
  const style = document.createElement('style')
  style.type = 'text/css'
  if (window['litNonce']) {
    style.setAttribute('nonce', window['litNonce']);
  }
  if (insertAt === 'top' && head.firstChild) {
    head.insertBefore(style, head.firstChild)
  } else {
    head.appendChild(style)
  }
  if (style.styleSheet) {
    style.styleSheet.cssText = css
  } else {
    style.appendChild(document.createTextNode(css))
  }
};
