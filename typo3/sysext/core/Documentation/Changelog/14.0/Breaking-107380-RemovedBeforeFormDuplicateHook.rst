.. include:: /Includes.rst.txt

.. _breaking-107380-1756896552:

======================================================
Breaking: #107380 - Removed "beforeFormDuplicate" hook
======================================================

See :issue:`107380`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDuplicate']`
has been removed in favor of the more powerful PSR-14 :php:`TYPO3\CMS\Form\Event\BeforeFormIsDuplicatedEvent`.


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
Use the :ref:`PSR-14 Event <feature-107380-1756896691>` to allow greater
influence in the functionality.

.. index:: Backend, ext:form, FullyScanned
