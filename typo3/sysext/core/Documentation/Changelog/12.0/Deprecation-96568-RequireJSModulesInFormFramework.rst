.. include:: /Includes.rst.txt

.. _deprecation-96568:

=========================================================
Deprecation: #96568 - RequireJS modules in Form Framework
=========================================================

See :issue:`96568`

Description
===========

Extending the Form Framework manager and editor via RequireJS modules has been
deprecated in favor of native ES6 JavaScript modules.

The :yaml:`dynamicRequireJsModules` option is deprecated.

Impact
======

RequireJS is no longer loaded and native module loading is approached,
if :yaml:`dynamicRequireJsModules` is not defined. Extensions that
use :yaml:`dynamicRequireJsModules` will work as before but trigger a PHP :php:`E_USER_DEPRECATED` error.

Affected Installations
======================

Installations that register custom form types or extend the backend JavaScript
of the form framework.

Migration
=========

Use :yaml:`dynamicJavaScriptModules` option instead of
:yaml:`dynamicRequireJsModules` to load ES6 instead of RequireJS modules:

..  code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              formEditor:
                dynamicJavaScriptModules:
                  additionalViewModelModules:
                    10: '@my-vendor/my-site-package/backend/form-editor/view-model.js'

And configure a corresponding importmap in
:file:`Configuration/JavaScriptModules.php`:

..  code-block:: php

    # Configuration/JavaScriptModules.php
    <?php

    return [
        'dependencies' => ['form'],
        'imports' => [
            '@myvendor/my-site-package/' => 'EXT:my_site_package/Resources/Public/JavaScript/',
        ],
    ];

.. index:: Backend, JavaScript, NotScanned, ext:form
