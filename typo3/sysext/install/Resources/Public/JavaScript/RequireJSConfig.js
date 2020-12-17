var require = {
  baseUrl: '',
  urlArgs: 'bust=' + (typeof __bust !== 'undefined' ? __bust : (new Date()).getTime()),
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
      name: 'lit-element',
      location: 'sysext/core/Resources/Public/JavaScript/Contrib/lit-element',
      main: 'lit-element'
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
