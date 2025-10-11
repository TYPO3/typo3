.. include:: /Includes.rst.txt

.. _breaking-107568-1759325068:

==============================================
Breaking: #107568 - Removed "afterSubmit" hook
==============================================

See :issue:`107568`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterSubmit']`
has been removed in favor of the more powerful PSR-14 :php:`TYPO3\CMS\Form\Event\BeforeRenderableIsValidatedEvent`.

Impact
======

Any hook implementation registered is not executed anymore in TYPO3 v14.0+.

Affected installations
======================

TYPO3 installations with custom extensions using this hook. The extension
scanner reports any usage as weak match.

Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v13 (using the hook) and v14+ (using the new event)
when implementing the event as well without any further deprecations.
Use the :ref:`PSR-14 Event <feature-107568-1759326362>` to allow greater
influence in the functionality. Especially because the event is dispatched
later, it allows more modifications than the previous hook.

.. index:: Backend, ext:form, FullyScanned
