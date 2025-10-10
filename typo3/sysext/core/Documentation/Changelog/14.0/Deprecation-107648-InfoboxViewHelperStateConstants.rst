..  include:: /Includes.rst.txt

..  _deprecation-107648-1744465200:

==========================================================
Deprecation: #107648 - InfoboxViewHelper STATE_* constants
==========================================================
See :issue:`107648`

Description
===========

The public constants in :php:`\TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper`
for defining the state/severity of an infobox have been deprecated:

* :php:`\TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper::STATE_NOTICE`
* :php:`\TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper::STATE_INFO`
* :php:`\TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper::STATE_OK`
* :php:`\TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper::STATE_WARNING`
* :php:`\TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper::STATE_ERROR`

These constants have been superseded by the dedicated enum
:php:`\TYPO3\CMS\Core\Type\ContextualFeedbackSeverity`, which provides
a single source of truth for severity levels across the entire TYPO3
Core and improves type safety and maintainability.

Impact
======

Using these constants will trigger a PHP deprecation warning. The constants
will be removed in TYPO3 v15.0. The extension scanner will report usages
as **weak** match.

Affected installations
======================

Instances using any of the :php:`STATE_*` constants from
:php:`InfoboxViewHelper` in their code or Fluid templates.

Migration
=========

Replace the deprecated constants with the corresponding
:php:`ContextualFeedbackSeverity` enum.

.. important::
    Whenever possible, use the enum directly instead of extracting its integer
    value. This provides better type safety and makes the code more expressive.
    Only use :php:`->value` when you explicitly need the integer representation.

.. code-block:: php

    // Before
    use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;
    $state = InfoboxViewHelper::STATE_ERROR;

    // After - Recommended: Use the enum directly
    use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
    $severity = ContextualFeedbackSeverity::ERROR;

    // Alternative: Use the integer value when explicitly needed
    $stateValue = ContextualFeedbackSeverity::ERROR->value;

In Fluid templates, use the enum via :html:`f:constant()`:

.. code-block:: html

    <!-- Before -->
    <f:be.infobox title="Error!"
        state="{f:constant(name: 'TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper::STATE_ERROR')}">
        Error message
    </f:be.infobox>

    <!-- After -->
    <f:be.infobox title="Error!"
        state="{f:constant(name: 'TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::ERROR')}">
        Error message
    </f:be.infobox>

The :php:`InfoboxViewHelper` has been updated to accept both the enum directly
and integer values for backwards compatibility.

Mapping table:

========================================  ================================================  =======
Deprecated constant                       Replacement                                       Value
========================================  ================================================  =======
``InfoboxViewHelper::STATE_NOTICE``       ``ContextualFeedbackSeverity::NOTICE->value``     -2
``InfoboxViewHelper::STATE_INFO``         ``ContextualFeedbackSeverity::INFO->value``       -1
``InfoboxViewHelper::STATE_OK``           ``ContextualFeedbackSeverity::OK->value``         0
``InfoboxViewHelper::STATE_WARNING``      ``ContextualFeedbackSeverity::WARNING->value``    1
``InfoboxViewHelper::STATE_ERROR``        ``ContextualFeedbackSeverity::ERROR->value``      2
========================================  ================================================  =======

..  index:: Fluid, FullyScanned, ext:fluid
