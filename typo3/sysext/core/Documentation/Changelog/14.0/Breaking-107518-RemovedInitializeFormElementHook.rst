.. include:: /Includes.rst.txt

.. _breaking-107518-1758539663:

========================================================
Breaking: #107518 - Removed "initializeFormElement" hook
========================================================

See :issue:`107518`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement']`
has been removed in favor of the more powerful PSR-14 :php:`TYPO3\CMS\Form\Event\BeforeRenderableIsAddedToFormEvent`.


Impact
======

Any hook implementation registered is not executed anymore in TYPO3 v14.0+.


Affected installations
======================

TYPO3 installations with custom extensions using this hook. The extensions
scanner reports any usage as weak match.


Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v13 (using the hook) and v14+ (using the new event)
when implementing the event as well without any further deprecations.
Use the :ref:`PSR-14 Event <feature-107518-1758539757>` to allow greater
influence in the functionality. Especially because the event is dispatched
later, it allows more modifications than the previous hook.

.. index:: Backend, ext:form, FullyScanned
