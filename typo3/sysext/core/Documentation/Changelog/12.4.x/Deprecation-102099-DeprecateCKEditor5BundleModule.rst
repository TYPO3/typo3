.. include:: /Includes.rst.txt

.. _deprecation-102099:

========================================================
Deprecation: #102099 - Deprecate CKEditor5 bundle module
========================================================

See :issue:`102099`

Description
===========

With the CKEditor5 integration in TYPO3 v12 a custom CKEditor5 build in form of
a bundle has been introduced. Missing plugins had to be merged into that bundle
again and again which lead to an increased bundle size. Also plugin authors had
to reference the bundle module in order to fetch plugin exports from CKEditor.

With CKEditor5 suggestion to use named exports from the CKEditor5 package entry
point modules, it became feasible to create smaller bundles. One bundle per
scoped subpackage. For that reason :js:`@typo3/ckeditor5-bundle.js` is now
deprecated.


Impact
======

TYPO3 can ship all available CKEditor5 modules and only actually requested modules
are loaded. Developers can write plugins as suggested by upstream documentation.


Affected Installations
======================

Installations having custom extensions activated, that provide custom CKEditor5
plugins. Extensions that use:js:`@typo3/ckeditor5-bundle.js` will still work
as before (as the bundle module re-exports the exports of the split bundles)
but will trigger a deprecation log message to the browser console.


Migration
=========

Extension authors should import from scoped :js:`@ckeditor/ckeditor5-*` packages
directly.

.. code-block:: javascript

    // Before
    import {Core, UI} from '@typo3/ckeditor5-bundle.js';

    // After
    import * as Core from '@ckeditor/ckeditor5-core';
    import * as UI from '@ckeditor/ckeditor5-ui';

.. index:: PHP-API, NotScanned, ext:core
