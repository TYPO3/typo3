..  include:: /Includes.rst.txt

..  _feature-97898-1737639600:

======================================================
Feature: #97898 - TCA option isViewable for Page Types
======================================================

See :issue:`97898`

Description
===========

A new TCA option :php:`isViewable` is introduced for page types ("doktype")
to configure whether a specific page type ("doktype") can be linked to in
the page browser and in frontend TypoLink generation.

.. code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/pages.php

    // Disable linking for custom page type
    $GLOBALS['TCA']['pages']['types']['116']['isViewable'] = false;

By default, all page types are viewable unless explicitly set to :php:`false`.

TYPO3 core now marks the following page types as non-viewable in TCA:

* Spacer (doktype 199)
* SysFolder (doktype 254)

The existing TSconfig option :typoscript:`TCEMAIN.preview.disableButtonForDokType`
is also respected when determining viewability in the backend page browser. If a
page type is disabled for preview via TSconfig, it will also be non-viewable.

Impact
======

The viewability of pages can now be configured in TCA, following the same
pattern as the :php:`allowedRecordTypes` option introduced in TYPO3 v14.1.
This provides a centralized way to control which page types can be linked.

Extensions with custom page types that should not be viewable can now
configure this directly in TCA:

.. code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/pages.php

    $GLOBALS['TCA']['pages']['types'][(string)\MyVendor\MyExtension\Domain\PageType::MY_NON_VIEWABLE_TYPE] = [
        'isViewable' => false,
        'showitem' => '...',
    ];

..  index:: TCA, ext:core
