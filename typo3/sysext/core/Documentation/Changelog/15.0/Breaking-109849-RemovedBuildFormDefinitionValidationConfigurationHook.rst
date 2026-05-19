..  include:: /Includes.rst.txt

..  _breaking-109849-1716115200:

=============================================================================
Breaking: #109849 - Removed "buildFormDefinitionValidationConfiguration" hook
=============================================================================

See :issue:`109849`

Description
===========

The hook :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['buildFormDefinitionValidationConfiguration']`
has been removed in favor of the new PSR-14 event
:php:`\TYPO3\CMS\Form\Event\AfterFormDefinitionValidationConfigurationIsBuiltEvent`.

Impact
======

Any hook implementation registered under this identifier will no longer be
executed.

Affected installations
======================

TYPO3 installations with custom extensions that register a hook class under
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['buildFormDefinitionValidationConfiguration']`
are affected.

Migration
=========

The hook has been removed without a deprecation phase to allow extensions to
remain compatible with both TYPO3 v14 (using the hook) and v15+ (using the
new event). Implementing the PSR-14 event provides the same or greater control.

Use the :ref:`AfterFormDefinitionValidationConfigurationIsBuiltEvent <feature-109849-1716115200>`
to achieve the same functionality with the new event-based system.

..  index:: Backend, ext:form, FullyScanned
