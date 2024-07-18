.. include:: /Includes.rst.txt

.. _breaking-101266-1688654482:

====================================
Breaking: #101266 - Remove RequireJS
====================================

See :issue:`101266`

Description
===========

The RequireJS project has been `discontinued`_ and was therefore
deprecated in TYPO3 v12 with :issue:`96510` in favor of native ECMAScript
v6/v11 modules (added in :issue:`96510`).

The infrastructure for configuration and loading of RequireJS
modules is now removed.


Impact
======

Registering FormEngine JavaScript modules via :php:`'requireJsModules'` will
have no effect. The PageRenderer endpoints
:php:`\TYPO3\CMS\Core\Page\PageRenderer->loadRequireJs()` and
:php:`\TYPO3\CMS\Core\Page\PageRenderer->loadRequireJsModule()`
have been removed and must no longer be called.
The respective :html:`includeJavaScriptModules` property of the ViewHelper
:html:`<f:be.pageRenderer>` ViewHelper has also been removed.


Affected installations
======================

TYPO3 installations using RequireJS modules to provide JavaScript in the TYPO3
backend, or – less common – use PageRenderer RequireJS infrastructure for
frontend JavaScript module loading.


Migration
=========

Migrate your JavaScript from the AMD module format to native ES6 modules and
register your configuration in :php:`Configuration/JavaScriptModules.php`,
also see :issue:`96510` and :ref:`t3coreapi:backend-javascript-es6`
for more information:

.. code-block:: php

    # Configuration/JavaScriptModules.php
    <?php

    return [
        'dependencies' => ['core', 'backend'],
        'imports' => [
            '@vendor/my-extension/' => 'EXT:my_extension/Resources/Public/JavaScript/',
        ],
    ];

Then use :php:`\TYPO3\CMS\Core\Page\PageRenderer->loadJavaScriptModule()` instead
of :php:`\TYPO3\CMS\Core\Page\PageRenderer->loadRequireJsModule()` to load the ES6 module:

.. code-block:: php

    // via PageRenderer
    $this->pageRenderer->loadJavaScriptModule('@vendor/my-extension/example.js');


In Fluid templates `includeJavaScriptModules` is to be used instead of
`includeRequireJsModules`:

In Fluid template the `includeJavaScriptModules` property of the
:html:`<f:be.pageRenderer>` ViewHelper may be used:

.. code-block:: xml

   <f:be.pageRenderer
      includeJavaScriptModules="{
         0: '@vendor/my-extension/example.js'
      }"
   />

.. seealso::
    :ref:`t3coreapi:backend-javascript-es6` for more info about JavaScript in TYPO3 Backend.

.. _discontinued: https://github.com/requirejs/requirejs/issues/1816

.. index:: Backend, JavaScript, PHP-API, PartiallyScanned, ext:core
