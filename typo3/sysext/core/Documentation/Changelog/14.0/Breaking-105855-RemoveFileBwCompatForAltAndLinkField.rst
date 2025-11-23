..  include:: /Includes.rst.txt

..  _breaking-105855-1734686200:

==============================================================================
Breaking: #105855 - Remove file backwards compatibility for alt and link field
==============================================================================

See :issue:`105855`

Description
===========

When the File Abstraction Layer (FAL) was introduced, the TYPO3 Core file fields
`media` for table `pages`, and `image` and `assets` for table
`tt_content`, had their so-called "overlay palettes" overridden to
`imageOverlayPalette`, so that additional fields like `alternative`, `link`, and
`crop` were displayed. However, this was done for all file types, including
`text`, `application`, and the fallback type `unknown`. For these types, the
additional fields served no meaningful purpose. For this reason, they have now
been removed.

Impact
======

The TYPO3 Core file fields `media` for table `pages`, `image` and
`assets` for table `tt_content`, will no longer display the fields
`alternative` and `link` for file types other than `image`.

Affected installations
======================

This affects installations that use one of the Core fields for file types
other than `image` (for example `text` or `application`) and make use of the
fields `alternative` and/or `link`.

This should not affect many installations, as these fields are used primarily
for images.

Migration
=========

These fields can be restored using TCA overrides if necessary. First,
register a new palette for the `sys_file_reference` table containing the desired
set of fields.

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/sys_file_reference.php

    $GLOBALS['TCA']['sys_file_reference']['palettes']['myCustomPalette'] = [
        'label' => 'My custom palette',
        'showitem' => 'alternative,description,--linebreak--,link,title',
    ];

Then, use this palette for your specific Core field and file type. The following
example restores the fields `alternative` and `link` for the `media` field of
the `pages` table when the file type is `text`.

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/Overrides/pages.php

    use TYPO3\CMS\Core\Resource\FileType;

    $GLOBALS['TCA']['pages']['columns']['media']['config']['overrideChildTca']
        ['types'][FileType::TEXT->value]['showitem'] =
        '--palette--;;myCustomPalette,--palette--;;filePalette';

..  index:: FAL, TCA, NotScanned, ext:core
