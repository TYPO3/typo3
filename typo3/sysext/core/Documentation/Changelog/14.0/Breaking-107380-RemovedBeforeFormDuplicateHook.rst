..  include:: /Includes.rst.txt

..  _breaking-107380-1756896552:

======================================================
Breaking: #107380 - Removed "beforeFormDuplicate" hook
======================================================

See :issue:`107380`

Description
===========

The hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDuplicate']`
has been removed in favor of the more powerful PSR-14 event
:php-short:`\TYPO3\CMS\Form\Event\BeforeFormIsDuplicatedEvent`.

Impact
======

Implementations of the removed hook are no longer executed in TYPO3 v14.0
and later.

Affected installations
======================

TYPO3 installations with custom extensions using this hook. The extension
scanner reports any usage as a weak match.

Migration
=========

The hook has been removed without deprecation to allow extensions to remain
compatible with both TYPO3 v13 (using the hook) and TYPO3 v14+ (using the new
event). When implementing the event as well, no further deprecations will
occur.

Use the :ref:`PSR-14 Event <feature-107380-1756896691>` to achieve the same
or greater functionality.

..  index:: Backend, ext:form, FullyScanned
