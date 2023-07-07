.. include:: /Includes.rst.txt

.. _feature-96510:

======================================================================
Feature: #96510 - Infrastructure for JavaScript modules and importmaps
======================================================================

See :issue:`96510`

Description
===========

JavaScript ES6 modules may now be used instead of AMD modules, both in backend
and frontend context. JavaScript node-js style path resolutions are managed by
`importmaps`_, which allow web pages to control the behavior of JavaScript imports.

By the time of writing importmaps are supported natively by Google Chrome,
a polyfill is available for Firefox and Safari and included by TYPO3 Core
and applied whenever an importmap is emitted.

RequireJS is shimmed to prefer ES6 modules if available, allowing any extension
to ship ES6 modules by providing an importmap configuration in
:file:`Configuration/JavaScriptModules.php` while providing full backwards
compatibility support for extensions that load modules via RequireJS.

For security reasons importmap configuration is only emitted when the modules
are actually used, that means when a module has been added to the current
page response via :php:`PageRenderer->loadJavaScriptModule()` or
:php:`JavaScriptRenderer->addJavaScriptModuleInstruction()`.
Exposing all module configurations is possible via
:php:`JavaScriptRenderer->includeAllImports()`, but that should only be
done in backend context for logged in users, to avoid disclosing installed
extensions to anonymous visitors.

Existing RequireJS modules can load new ES6 modules via a bridge that
prefers ES6 modules over traditional RequireJS AMD modules. This allows
extensions authors to migrate to ES6 without breaking dependencies that
used to load a module of that extension via RequireJS.

Configuration
-------------

A simple configuration example for an extension that maps
the `Public/JavaScript` folder to an import prefix `@vendor/my-extensions`:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/JavaScriptModules.php

    <?php

    return [
        // required import configurations of other extensions,
        // in case a module imports from another package
        'dependencies' => ['backend'],
        'imports' => [
            // recursive definition, all *.js files in this folder are import-mapped
            // trailing slash is required per importmap-specification
            '@vendor/my-extension/' => 'EXT:my_extension/Resources/Public/JavaScript/',
        ],
    ];

Complex configuration example containing recursive-lookup exclusions,
third-party library definitions and overwrites:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/JavaScriptModules.php

    <?php

    return [
        'dependencies' => ['core', 'backend'],
        'imports' => [
            '@vendor/my-extension/' => [
                'path' => 'EXT:my_extension/Resources/Public/JavaScript/',
                # Exclude files of the following folders from being import-mapped
                'exclude' => [
                    'EXT:my_extension/Resources/Public/JavaScript/Contrib/',
                    'EXT:my_extension/Resources/Public/JavaScript/Overrides/',
                ],
            ],
            # Adding a third-party package
            'thirdpartypkg' => 'EXT:my_extension/Resources/Public/JavaScript/Contrib/thidpartypkg/index.js',
            'thidpartypkg/' => 'EXT:my_extension/Resources/Public/JavaScript/Contrib/thirdpartypkg/',
            # Overriding a file from another package
            'TYPO3/CMS/Backend/Modal.js' => 'EXT:my_extension/Resources/Public/JavaScript/Overrides/BackendModal.js',
        ],
    ];

Usage
-----

A module can be added to the current page response either via
:php:`PageRenderer` or as :php:`JavaScriptModuleInstruction` via
:php:`JavaScriptRenderer`:

..  code-block:: php

    // via PageRenderer
    $this->pageRenderer->loadJavaScriptModule('@vendor/my-extension/example.js');

    // via JavaScriptRenderer
    $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
        JavaScriptModuleInstruction::create('@vendor/my-extension/example.js')
    );

In Fluid template the `includeJavaScriptModules` property of the
:html:`<f:be.pageRenderer>` ViewHelper may be used:

..  code-block:: xml

    <f:be.pageRenderer
       includeJavaScriptModules="{
          0: '@vendor/my-extension/example.js'
       }"
    />

.. _`importmaps`: https://wicg.github.io/import-maps/

Impact
======

The custom module loader RequireJS will become superfluous and can be removed
in favor of native browser modules. This will speed up module loading.
Also the RequireJS system is discontinued.

.. attention::

   This API is considered experimental and may change until v12.0.
   For example there are plans to take :file:`package.json` files into
   account.

.. index:: JavaScript, ext:core
