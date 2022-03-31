.. include:: /Includes.rst.txt

=======================================================================
Deprecation: #95011 - Various global JavaScript functions and variables
=======================================================================

See :issue:`95011`

Description
===========

The following globally available variables in TYPO3 Backend's JavaScript code have been marked as deprecated:

* :js:`top.currentSubScript`
* :js:`top.currentModuleLoaded`
* :js:`top.nextLoadModuleUrl`

In addition the global JavaScript function :js:`jump()` has
been marked as deprecated as well.

This functionality has been around for a very long time, and
is superseded by TYPO3's Module Menu Component (since 4.5) and the newly introduced Backend Routing Component in JavaScript
since TYPO3 v11.


Impact
======

The variables will work and be filled as expected in TYPO3 v11, but will not be available anymore in TYPO3 v12.

Calling :js:`jump()` will trigger a JavaScript warning in ones'
browser console.


Affected Installations
======================

TYPO3 installations with custom extensions which utilize Backend
JavaScript and using the legacy functionality, which is highly
unlikely.


Migration
=========

Use the ModuleMenu JavaScript API or the Router API to find out the current module or go to a specific route:

.. code-block:: js

    const router = document.querySelector('typo3-backend-module-router');
    router.setAttribute('endpoint', url);
    router.setAttribute('module', moduleName);


.. index:: Backend, JavaScript, NotScanned, ext:backend
