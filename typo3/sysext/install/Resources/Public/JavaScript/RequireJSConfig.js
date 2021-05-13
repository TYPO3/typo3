var TYPO3 = TYPO3 || {};

var require = {
  baseUrl: '',
  urlArgs: 'bust=' + (typeof document.currentScript.dataset.bust !== undefined ? document.currentScript.dataset.bust : (new Date()).getTime()),
  paths: {
    'TYPO3/CMS/Core': 'sysext/core/Resources/Public/JavaScript',
    'TYPO3/CMS/Backend': 'sysext/backend/Resources/Public/JavaScript',
    'TYPO3/CMS/Install': 'sysext/install/Resources/Public/JavaScript',
    'jquery': 'sysext/core/Resources/Public/JavaScript/Contrib/jquery/jquery.min',
    'jquery/minicolors': 'sysext/core/Resources/Public/JavaScript/Contrib/jquery.minicolors',
    'bootstrap': 'sysext/core/Resources/Public/JavaScript/Contrib/bootstrap/bootstrap',
    'chosen': 'sysext/install/Resources/Public/JavaScript/chosen.jquery.min',
    'nprogress': 'sysext/core/Resources/Public/JavaScript/Contrib/nprogress'
  },
  packages: [
    {
      name: 'lit-html',
      location: 'sysext/core/Resources/Public/JavaScript/Contrib/lit-html',
      main: 'lit-html'
    },
    {
      name: '@lit/reactive-element',
      location: 'sysext/core/Resources/Public/JavaScript/Contrib/@lit/reactive-element',
      main: 'reactive-element'
    },
    {
      name: 'lit-element',
      location: 'sysext/core/Resources/Public/JavaScript/Contrib/lit-element',
      main: 'index'
    },
    {
      name: 'lit',
      location: 'sysext/core/Resources/Public/JavaScript/Contrib/lit',
      main: 'index'
    },
  ],
  shim: {
    jQuery: {
      exports: '$'
    },
    'bootstrap': {
      'deps': ['jquery']
    }
  }
};

if (typeof document.currentScript.dataset.init !== undefined) {
  require.deps = [document.currentScript.dataset.init];
}
