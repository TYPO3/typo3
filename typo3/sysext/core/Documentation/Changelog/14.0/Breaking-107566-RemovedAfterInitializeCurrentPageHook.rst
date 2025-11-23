..  include:: /Includes.rst.txt

..  _breaking-107566-1759226580:

=============================================================
Breaking: #107566 - Removed "afterInitializeCurrentPage" hook
=============================================================

See :issue:`107566`

Description
===========

The hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterInitializeCurrentPage']`
has been removed in favor of the PSR-14 event
:php:`\TYPO3\CMS\Form\Event\AfterCurrentPageIsResolvedEvent`.

Impact
======

Hook implementations registered under `afterInitializeCurrentPage` are
no longer executed in TYPO3 v14.0 and later.

Affected installations
======================

TYPO3 installations with custom extensions using this hook are affected.

The extension scanner reports any usage as a weak match.

Migration
=========

The hook was removed without a deprecation phase to allow extensions to work
with both TYPO3 v13 (using the hook) and TYPO3 v14+ (using the new event)
simultaneously.

Use the :ref:`PSR-14 event <feature-107566-1759226649>` instead to allow
greater influence over the form rendering process. Since the event is
dispatched at a later point, it allows more extensive modifications than the
previous hook.

..  index:: Backend, ext:form, FullyScanned
