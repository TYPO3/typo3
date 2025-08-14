..  include:: /Includes.rst.txt

..  _feature-107281-1755252069:

====================================================
Feature: #107281 - Type-specific TCAdefaults support
====================================================

See :issue:`107281`

Description
===========

The :typoscript:`TCAdefaults` configuration has been extended to support
type-specific syntax similar to :typoscript:`TCEFORM`, enabling different
default values based on the record type.

This allows configuration like:

..  code-block:: typoscript
    :caption: Page TSconfig

    # Field-level default (applies to all content types)
    TCAdefaults.tt_content.header_layout = 1

    # Type-specific defaults (applies only to specific content types)
    TCAdefaults.tt_content.header_layout.types.textmedia = 3
    TCAdefaults.tt_content.frame_class.types.textmedia = ruler-before

The same syntax is supported in User TSconfig as well.

Type-specific defaults take precedence over field-level defaults, and Page
TSconfig overrides User TSconfig following the established inheritance pattern.

Implementation Details
======================

The feature is implemented in two main areas:

1. **Backend Forms**: The
   :php:`\TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew`
   class now processes type-specific defaults when creating new records in the
   backend.

2. **DataHandler**: The
   :php:`\TYPO3\CMS\Core\DataHandling\DataHandler` class supports type-specific
   defaults when creating records programmatically via the PHP API.

..  important::
    Type-specific defaults only work when the record type is defined in the new
    record values. This happens automatically when using the New Content Element
    Wizard (via :php:`defVals`) or when explicitly providing the type field in
    the DataHandler datamap (e.g., :php:`'CType' => 'textmedia'` for content
    elements).

    If no type information is available during record creation, only field-level
    :typoscript:`TCAdefaults` will be applied.

Fallback Behavior
=================

If no type-specific default is found for a given record type, the system falls
back to:

1. Field-level :typoscript:`TCAdefaults` configuration
2. TCA :php:`'default'` configuration
3. Database field default value

Automatic Field Discovery
=========================

This enhancement makes :typoscript:`TCAdefaults` consistent with
:typoscript:`TCEFORM` patterns and enables automatic field discovery.

Examples
========

..  code-block:: typoscript
    :caption: User TSconfig - Basic type-specific defaults

    TCAdefaults.tt_content.header_layout = 1
    TCAdefaults.tt_content.header_layout.types.textmedia = 3
    TCAdefaults.tt_content.header_layout.types.image = 2

..  code-block:: typoscript
    :caption: Page TSconfig - Multiple fields with type-specific overrides

    TCAdefaults.tt_content {
        header_layout = 1
        header_layout.types.textmedia = 3
        header_layout.types.image = 2

        frame_class = default
        frame_class.types.textmedia = ruler-before
        frame_class.types.image = none

        space_before_class = none
    }

..  code-block:: php
    :caption: PHP API usage - DataHandler with type-specific defaults

    $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
    $datamap = [
        'tt_content' => [
            'NEW123' => [
                'pid' => 42,
                'CType' => 'textmedia',
                'header' => 'My Content Element',
                // header_layout and frame_class will be set automatically
                // based on type-specific TCAdefaults configuration
            ],
        ],
    ];
    $dataHandler->start($datamap, [], $backendUser);
    $dataHandler->process_datamap();

Impact
======

This feature provides a more flexible and consistent way to configure default
values for different record types, reducing repetitive configuration and
improving the user experience when creating new records.

The type-specific syntax aligns :typoscript:`TCAdefaults` with the established
:typoscript:`TCEFORM` pattern, making the configuration more intuitive for
TYPO3 developers and integrators.

.. index:: PHP-API, ext:core
