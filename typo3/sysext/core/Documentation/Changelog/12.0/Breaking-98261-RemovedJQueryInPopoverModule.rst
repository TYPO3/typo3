.. include:: /Includes.rst.txt

.. _breaking-98261-1662389392:

===================================================
Breaking: #98261 - Removed jQuery in Popover module
===================================================

See :issue:`98261`

Description
===========

The support for jQuery in the module :js:`@typo3/backend/popover` has been
dropped. Passing jQuery elements to the module's methods is not possible anymore.

This affects the following methods:

* :js:`popover()`
* :js:`setOptions()`
* :js:`show()`
* :js:`hide()`
* :js:`destroy()`
* :js:`toggle()`

Impact
======

Calling any of the aforementioned methods with passing a jQuery-based object is
undefined and will lead to JavaScript errors.

Affected installations
======================

All 3rd party extensions using the API of the :js:`@typo3/backend/popover` module
are affected.

Migration
=========

The method :js:`popover()` accepts either an object of type :js:`HTMLElement`
or a collection of type :js:`NodeList`, where all elements must be of type
:js:`HTMLElement`.

Any other method accepts objects of type :js:`HTMLElement` only.

Example:

..  code-block:: js

    // Before
    Popover.popover($('button.popover'));

    // After
    Popover.popover(document.querySelectorAll('button.popover'));

.. index:: Backend, JavaScript, NotScanned, ext:backend
