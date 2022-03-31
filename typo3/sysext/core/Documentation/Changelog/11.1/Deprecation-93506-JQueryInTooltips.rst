.. include:: /Includes.rst.txt

========================================
Deprecation: #93506 - jQuery in tooltips
========================================

See :issue:`93506`

Description
===========

Passing jQuery objects to the methods :js:`show()` and :js:`hide()` of the
module :file:`TYPO3/CMS/Backend/Tooltip` has been marked as deprecated.


Impact
======

Passing jQuery objects to the aforementioned methods will log a deprecation message in
the browser's console.


Affected Installations
======================

All 3rd party extensions passing jQuery objects to either :js:`show()` or
:js:`hide()` are affected.


Migration
=========

Either pass a single :js:`HTMLElement` (e.g. from
:js:`document.querySelector('#my-element')`) or a :js:`NodeList` (e.g. from
:js:`document.querySelectorAll('.my-element')`) to :js:`show()` or :js:`hide()`.

.. index:: Backend, JavaScript, NotScanned, ext:backend
