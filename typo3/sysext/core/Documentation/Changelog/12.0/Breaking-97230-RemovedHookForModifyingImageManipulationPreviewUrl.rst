.. include:: ../../Includes.txt

============================================================================
Breaking: #97230 - Removed hook for modifying image manipulation preview url
============================================================================

See :issue:`97230`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Backend/Form/Element/ImageManipulationElement']['previewUrl']`
has been removed in favor of a new PSR-14 Event :php:`\TYPO3\CMS\Backend\Form\Event\ModifyImageManipulationPreviewUrlEvent`.

Impact
======

Any hook implementation registered is not executed anymore in
TYPO3 v12.0+. The extension scanner will report possible usages.

Affected Installations
======================

All TYPO3 installations using this hook in custom extension code.

Migration
=========

The hook is removed without deprecation in order to allow extensions
to work with TYPO3 v11 (using the hook) and v12+ (using the new Event)
when implementing the Event as well without any further deprecations.
Use the :doc:`PSR-14 Event <../12.0/Feature-97230-PSR-14EventForModifyingImageManipulationPreviewUrl>`
as an improved replacement.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
