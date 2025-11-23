..  include:: /Includes.rst.txt

..  _breaking-107343-1756559538:

===================================================
Breaking: #107343 - Removed "beforeFormCreate" hook
===================================================

See :issue:`107343`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormCreate']`
has been removed in favor of the PSR-14 event
:php-short:`\TYPO3\CMS\Form\Event\BeforeFormIsCreatedEvent`, which provides a
more powerful and flexible way to influence form creation.

Impact
======

Any hook implementation registered under
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormCreate']`
is no longer executed in TYPO3 v14.0 and later.

Affected installations
======================

TYPO3 installations with custom extensions using this hook are affected.

The Extension Scanner will report such usages as a *weak match*.

Migration
=========

The hook has been removed without prior deprecation.
This allows extensions to remain compatible with both TYPO3 v13 (using the
hook) and v14+ (using the new event) simultaneously.

Use the PSR-14 event :ref:`BeforeFormIsCreatedEvent <feature-107343-1756389242>`
to extend or modify form creation behavior.

..  index:: Backend, ext:form, FullyScanned
