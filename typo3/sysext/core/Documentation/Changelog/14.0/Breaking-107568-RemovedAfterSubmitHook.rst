..  include:: /Includes.rst.txt

..  _breaking-107568-1759325068:

==============================================
Breaking: #107568 - Removed "afterSubmit" hook
==============================================

See :issue:`107568`

Description
===========

The hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterSubmit']`
has been removed in favor of the PSR-14 event
:php:`\TYPO3\CMS\Form\Event\BeforeRenderableIsValidatedEvent`.

Impact
======

Hook implementations registered under :php:`afterSubmit` are no longer executed
in TYPO3 v14.0 and later.

Affected installations
======================

TYPO3 installations with custom extensions using this hook are affected.

The extension scanner reports any usage as a weak match.

Migration
=========

The hook was removed without a deprecation phase to allow extensions to work
with both TYPO3 v13 (using the hook) and TYPO3 v14+ (using the new event)
simultaneously.

Use the :ref:`PSR-14 event <feature-107568-1759326362>` instead to allow
greater influence over the form submission process. Since the event is
dispatched at a later point, it allows more extensive modifications than the
previous hook.

..  index:: Backend, ext:form, FullyScanned
