..  include:: /Includes.rst.txt

..  _breaking-105855-1734686200:

==============================================================================
Breaking: #105855 - Remove file backwards compatibility for alt and link field
==============================================================================

See :issue:`105855`

Description
===========

Back then, when FAL was introduced, the Core file fields `media` for table
`pages` as well as `image` and `assets` for table `tt_content` had their
so-called "overlay palettes" overridden to `imageOverlayPalette`, so that
additional fields like `alternative`, `link` and `crop` were displayed. However,
this was done for all file types, including `text`, `application` and the
fallback type `unknown`. For these types those additional fields serve no
meaningful purpose. For this reason they are now removed.


Impact
======

The Core file fields `media` for table `pages` as well as `image` and `assets`
for table `tt_content` will no longer display the fields `alternative` and
`link` for file types other than `image`.


Affected installations
======================

This affects installations, which use one of the named Core fields for file
types other than `image` (for example `text` or `application`) and make use of
the fields `alternative` and/or `link`.

This should not affect that many installations, as these fields are used most
often for images.


Migration
=========

In case you need those fields back, they can be brought back with TCA overrides.
First, register a new palette for the `sys_file_reference` table with the needed
set of fields.

..  php::
    :caption: EXT:my_extension/Configuration/TCA/Overrides/sys_file_reference.php

    $GLOBALS['TCA']['sys_file_reference']['palettes']['myCustomPalette'] = [
        'label' => 'My custom palette',
        'showitem' => 'alternative,description,--linebreak--,link,title',
    ];

Then, use this palette for your specific Core field and file type. This will
bring back the fields `alternative` and `link` for the `media` field of table
`pages`, when the file type is `text`.

..  php::
    :caption: EXT:my_extension/Configuration/TCA/Overrides/pages.php

    $GLOBALS['TCA']['pages']['columns']['media']['config']['overrideChildTca']
        ['types'][\TYPO3\CMS\Core\Resource\FileType::TEXT->value]['showitem'] =
         '--palette--;;myCustomPalette,--palette--;;filePalette';

..  index:: FAL, TCA, NotScanned, ext:core
