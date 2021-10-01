var TYPO3 = TYPO3 || {};

var packageResourcePaths = JSON.parse(document.body.dataset.packageResourcePaths);

var require = {
  baseUrl: '',
  urlArgs: 'bust=' + (typeof document.currentScript.dataset.bust !== 'undefined' ? document.currentScript.dataset.bust : (new Date()).getTime()),
  paths: {
    'TYPO3/CMS/Core': packageResourcePaths["core"] + 'JavaScript',
    'TYPO3/CMS/Backend': packageResourcePaths["backend"] + 'JavaScript',
    'TYPO3/CMS/Install': packageResourcePaths["install"] + 'JavaScript',
    'jquery': packageResourcePaths["core"] + 'JavaScript/Contrib/jquery/jquery.min',
    'jquery/minicolors': packageResourcePaths["core"] + 'JavaScript/Contrib/jquery.minicolors',
    'bootstrap': packageResourcePaths["core"] + 'JavaScript/Contrib/bootstrap/bootstrap',
    'chosen': packageResourcePaths["install"] + 'JavaScript/chosen.jquery.min',
    'nprogress': packageResourcePaths["core"] + 'JavaScript/Contrib/nprogress'
  },
  packages: [
    {
      name: 'lit-html',
      location: packageResourcePaths["core"] + 'JavaScript/Contrib/lit-html',
      main: 'lit-html'
    },
    {
      name: '@lit/reactive-element',
      location: packageResourcePaths["core"] + 'JavaScript/Contrib/@lit/reactive-element',
      main: 'reactive-element'
    },
    {
      name: 'lit-element',
      location: packageResourcePaths["core"] + 'JavaScript/Contrib/lit-element',
      main: 'index'
    },
    {
      name: 'lit',
      location: packageResourcePaths["core"] + 'JavaScript/Contrib/lit',
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

if (typeof document.currentScript.dataset.init !== 'undefined') {
  require.deps = [document.currentScript.dataset.init];
}
