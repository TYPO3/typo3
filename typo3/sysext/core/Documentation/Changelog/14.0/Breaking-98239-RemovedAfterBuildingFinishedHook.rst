..  include:: /Includes.rst.txt

..  _breaking-98239-1758890437:

=======================================================
Breaking: #98239 - Removed "afterBuildingFinished" hook
=======================================================

See :issue:`98239`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterBuildingFinished']`
has been removed in favor of the more powerful PSR-14 events
:php:`\TYPO3\CMS\Form\Event\BeforeRenderableIsAddedToFormEvent` and
:php:`\TYPO3\CMS\Form\Event\AfterFormIsBuiltEvent`.

Impact
======

Any hook implementation registered under this identifier will no longer be
executed in TYPO3 v14.0 and later.

Affected installations
======================

TYPO3 installations with custom extensions that implement this hook are
affected. The extension scanner reports such usages as a weak match.

Migration
=========

The hook has been removed without a deprecation phase to allow extensions to
remain compatible with both TYPO3 v13 (using the hook) and v14+ (using the new
events). Implementing the PSR-14 events provides the same or greater control
over form rendering.

Use the :ref:`BeforeRenderableIsAddedToFormEvent <feature-107518-1758539757>` or
:ref:`AfterFormIsBuiltEvent <feature-98239-1758890522>` to achieve the same
functionality with the new event-based system.

..  index:: Backend, ext:form, FullyScanned
