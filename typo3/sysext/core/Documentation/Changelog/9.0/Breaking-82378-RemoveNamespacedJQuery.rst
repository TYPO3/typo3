.. include:: /Includes.rst.txt

===========================================
Breaking: #82378 - Remove namespaced jQuery
===========================================

See :issue:`82378`

Description
===========

The possibility to jail jQuery into a namespace has been removed. This affects custom namespaces and
:js:`TYPO3.jQuery` as well.

The class constants :php:`TYPO3\CMS\Core\Page\PageRenderer::JQUERY_NAMESPACE_DEFAULT` and
:php:`TYPO3\CMS\Core\Page\PageRenderer::JQUERY_NAMESPACE_DEFAULT_NOCONFLICT` have been removed without
substitution, any usage will be detected by the Extension Scanner.


Impact
======

Namespaces will be ignored and automatically fall back to noConflict behavior.

Calling :js:`TYPO3.jQuery.*` will result in a TypeError.


Affected Installations
======================

All installations using a custom namespace, :php:`PageRenderer::JQUERY_NAMESPACE_DEFAULT`
:php:`PageRenderer::JQUERY_NAMESPACE_DEFAULT` or relying on :js:`TYPO3.jQuery` are affected.


Migration
=========

Remove :php:`$namespace` argument in :php:`PageRenderer->loadJquery()` and either use :js:`window.$` or migrate
to RequireJS.

.. index:: Backend, Frontend, JavaScript, PartiallyScanned
