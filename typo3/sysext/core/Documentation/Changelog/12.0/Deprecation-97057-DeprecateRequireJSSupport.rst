.. include:: /Includes.rst.txt

.. _deprecation-97057-1664653704:

=================================================
Deprecation: #97057 - Deprecate RequireJS support
=================================================

See :issue:`97057`

Description
===========

The RequireJS project has been discontinued_ and was therefore
replaced by native ECMAScript v6/v11 modules in TYPO3 in :issue:`96510`.

The infrastructure for configuration and loading of RequireJS
modules is now deprecated and will be removed in TYPO3 v13.


Impact
======

Registering modules via :php:`'requireJsModules'` will still work.
These modules will be loaded after modules registered via :php:`'javaScriptModules'`. Extensions that
use :php:`'requireJsModules` will work as before but trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected installations
======================

Installations that register custom JavaScript modules for the TYPO3 backend.


Migration
=========

Migrate your JavaScript from the AMD module format to native ES6 modules and register your configuration in :php:`Configuration/JavaScriptModules.php`, also see :issue:`96510` for more information:

.. code-block:: php

    # Configuration/JavaScriptModules.php
    <?php

    return [
        'dependencies' => ['core', 'backend'],
        'imports' => [
            '@vendor/my-extension/' => 'EXT:my_extension/Resources/Public/JavaScript/',
        ],
    ];

Then use :php:`TYPO3\CMS\Core\Page\PageRenderer::loadJavaScriptModules()` instead of :php:`TYPO3\CMS\Core\Page\PageRenderer::loadRequireJsModule()` to load the ES6 module:

.. code-block:: php

    // via PageRenderer
    $this->packageRenderer->loadJavaScriptModule('@vendor/my-extension/example.js');


In Fluid templates `includeJavaScriptModules` is to be used instead of `includeRequireJsModules`:

In Fluid template the `includeJavaScriptModules` property of the
:html:`<f:be.pageRenderer>` ViewHelper may be used:

.. code-block:: xml

   <f:be.pageRenderer
      includeJavaScriptModules="{
         0: '@vendor/my-extension/example.js'
      }"
   />

.. _discontinued: https://github.com/requirejs/requirejs/issues/1816

.. index:: Backend, JavaScript, NotScanned, ext:backend
