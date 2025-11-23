..  include:: /Includes.rst.txt

..  _breaking-107569-1759906416:

==================================================
Breaking: #107569 - Removed "beforeRendering" hook
==================================================

See :issue:`107569`

Description
===========

The hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRendering']`
has been removed in favor of the PSR-14 event
:php:`\TYPO3\CMS\Form\Event\BeforeRenderableIsRenderedEvent`.

Impact
======

Hook implementations registered under :php:`beforeRendering` are no longer
executed in TYPO3 v14.0 and later.

Affected installations
======================

TYPO3 installations with custom extensions using this hook are affected.

The extension scanner reports any usage as a weak match.

Migration
=========

The hook was removed without a deprecation phase to allow extensions to work
with both TYPO3 v13 (using the hook) and TYPO3 v14+ (using the new event)
simultaneously.

Use the :ref:`PSR-14 event <feature-107569-1759906422>` instead to allow
greater influence over the rendering process. Since the event is dispatched
at a later point, it allows more extensive modifications than the previous
hook.

..  index:: Backend, ext:form, FullyScanned
