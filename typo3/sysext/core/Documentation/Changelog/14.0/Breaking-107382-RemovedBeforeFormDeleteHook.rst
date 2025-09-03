.. include:: /Includes.rst.txt

.. _breaking-107382-1756901681:

===================================================
Breaking: #107382 - Removed "beforeFormDelete" hook
===================================================

See :issue:`107382`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDelete']`
has been removed in favor of the more powerful PSR-14 :php:`TYPO3\CMS\Form\Event\BeforeFormIsDeletedEvent`.


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
Use the :ref:`PSR-14 Event <feature-107382-1756901428>` to allow greater
influence in the functionality.

.. index:: Backend, ext:form, FullyScanned
