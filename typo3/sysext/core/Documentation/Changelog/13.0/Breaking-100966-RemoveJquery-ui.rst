.. include:: /Includes.rst.txt

.. _breaking-100966-1686062649:

====================================
Breaking: #100966 - Remove jquery-ui
====================================

See :issue:`100966`

Description
===========

The `NPM package jquery-ui <https://www.npmjs.com/package/jquery-ui>`__ has
been removed completely for TYPO3 v13 without any substitute.

According to the `TYPO3 Deprecation Policy <https://typo3.org/article/typo3-deprecation-policy>`__,
JavaScript code and packages used only in the TYPO3 backend are not
considered to be part of that policy:

    The deprecation policy does not cover the deprecations of backend components
    such as JavaScript code, CSS code, HTML code, and backend templates.


Impact
======

TYPO3 does not ship the NPM package `jquery-ui` any longer. Third-party
extensions that rely on this package will be broken and need to be adjusted.

Since TYPO3 exposed only parts of `jquery-ui`, only the components `core`,
`draggable`, `droppable`, `mouse`, `resizable`, `selectable`, `sortable` and
`widget` are affected - other components simply did not exist.


Affected installations
======================

Those having custom or third-party extensions using `jquery-ui` from
:file:`typo3/sysext/core/Resources/Public/JavaScript/Contrib/jquery-ui/`.


Migration
=========

TYPO3 does not provide any substitute. In TYPO3 the `draggable` and `resizable`
features of `jquery-ui` have been reimplemented in the new custom element
:html:`<typo3-backend-draggable-resizable>`.

.. index:: Backend, JavaScript, NotScanned, ext:core
