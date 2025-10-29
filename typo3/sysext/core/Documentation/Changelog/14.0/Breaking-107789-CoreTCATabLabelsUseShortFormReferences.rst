..  include:: /Includes.rst.txt

..  _breaking-107789-1729603200:

=======================================================================
Breaking: #107789 - Core TCA showitem strings use short form references
=======================================================================

See :issue:`107789`

Description
===========

TYPO3 Core TCA configurations have been updated to use short form
translation reference formats (e.g., :php:`core.form.tabs:*`) instead of
the full :php:`LLL:EXT:` path format in :php:`showitem` strings.

This change affects all core TCA :php:`showitem` definitions that previously
used full :php:`LLL:EXT:` paths for labels. The most prominent changes are
tab labels using the :php:`--div--` syntax, but this pattern may be applied
to other TCA elements in the future.

Examples of changed references in tab labels:

.. code-block:: php

    // Before (TYPO3 v13)
    '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general'
    '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access'
    '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language'

    // After (TYPO3 v14)
    '--div--;core.form.tabs:general'
    '--div--;core.form.tabs:access'
    '--div--;core.form.tabs:language'

Impact
======

Custom extensions that programmatically manipulate TCA :php:`showitem` strings
from core tables and expect the full :php:`LLL:EXT:` path format will break.

This particularly affects code that:

-   Uses string search/replace operations on :php:`showitem` strings to find or
    modify specific labels (tabs, palettes, or other elements)
-   Parses :php:`showitem` strings using regular expressions expecting the
    :php:`LLL:EXT:` pattern
-   Extracts translation keys from TCA configurations for analysis or
    documentation purposes
-   Builds custom TCA configurations by copying and modifying core
    :php:`showitem` strings

Currently, the following label categories have been migrated to short form:

**Tab labels (--div--):**

-   :php:`LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:*`
    → :php:`core.form.tabs:*`
-   :php:`LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:tabs.*`
    → :php:`core.form.tabs:*`
-   :php:`LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.tabs.*`
    → :php:`core.form.tabs:*`

**Palette labels (palette definitions):**

-   :php:`LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.palettes.*`
    → :php:`core.form.palettes:*`
-   :php:`LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.palettes.*`
    → :php:`core.form.palettes:*`
-   :php:`LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.*`
    → :php:`core.form.palettes:*`
-   :php:`LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.*`
    → :php:`core.form.palettes:*`
-   :php:`LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:palette.*`
    → :php:`core.form.palettes:*`
-   :php:`LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.palettes.*`
    → :php:`core.form.palettes:*`

**Field label overrides removed in showitem definitions:**

Field labels can be overriden in showitem definitions for types or palettes, but
should rather be kept in the field definition itself. The following field label
overrides have been removed from showitem strings in favor of using the field's
own label definition:

-   :php:`bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel`
    → :php:`frontend.db.tt_content:bodytext`
-   :php:`CType;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType_formlabel`
    → :php:`frontend.db.tt_content:type`
-   :php:`colPos;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos_formlabel`
    → :php:`frontend.db.tt_content:column`
-   :php:`header*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header`
    → :php:`frontend.db.tt_content:header`
-   :php:`header*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_formlabel`
    → :php:`frontend.db.tt_content:header`
-   :php:`header*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout_formlabel`
    → :php:`frontend.db.tt_content:header_type`
-   :php:`header*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link`
    → :php:`frontend.db.tt_content:header_link`
-   :php:`header*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link_formlabel`
    → :php:`frontend.db.tt_content:header_link`
-   :php:`header*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position`
    → :php:`frontend.db.tt_content:header_position`
-   :php:`subheader;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:subheader_formlabel`
    → :php:`frontend.db.tt_content:subheader`
-   :php:`date;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:date_formlabel`
    → :php:`frontend.db.tt_content:date`
-   :php:`image*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_zoom`
    → :php:`frontend.db.tt_content:image_zoom`
-   :php:`image*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_zoom_formlabel`
    → :php:`frontend.db.tt_content:image_zoom`
-   :php:`image*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageborder`
    → :php:`frontend.db.tt_content:imageborder`
-   :php:`image*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageborder_formlabel`
    → :php:`frontend.db.tt_content:imageborder`
-   :php:`image*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagecols`
    → :php:`frontend.db.tt_content:imagecols`
-   :php:`image*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagecols_formlabel`
    → :php:`frontend.db.tt_content:imagecols`
-   :php:`image*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient`
    → :php:`frontend.db.tt_content:imageorientation`
-   :php:`image*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient_formlabel`
    → :php:`frontend.db.tt_content:imageorientation`
-   :php:`image*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageheight`
    → :php:`frontend.db.tt_content:imageheight`
-   :php:`image*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageheight_formlabel`
    → :php:`frontend.db.tt_content:imageheight`
-   :php:`image*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagewidth`
    → :php:`frontend.db.tt_content:imagewidth`
-   :php:`image*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagewidth_formlabel`
    → :php:`frontend.db.tt_content:imagewidth`
-   :php:`sectionIndex;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sectionIndex_formlabel`
    → :php:`sectionIndex`
-   :php:`linkToTop;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:linkToTop_formlabel`
    → :php:`linkToTop`
-   :php:`layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel`
    → :php:`layout`
-   :php:`space_before_class;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_before_class_formlabel`
    → :php:`space_before_class`
-   :php:`space_after_class;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_after_class_formlabel`
    → :php:`space_after_class`

**Field label overrides changed in palette definitions:**

-   :php:`hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden`
    → :php:`hidden;frontend.db.tt_content:hidden`
-   :php:`starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel`
    → :php:`starttime;frontend.db.tt_content:starttime`
-   :php:`endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel`
    → :php:`endtime;frontend.db.tt_content:endtime`
-   :php:`fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:fe_group_formlabel`
    → :php:`fe_group;frontend.db.tt_content:fe_group`

Affected installations
======================

Installations with custom extensions that:

-   Programmatically read and manipulate TCA :php:`showitem` strings from
    :php:`$GLOBALS['TCA']`
-   Override core TCA by copying and modifying existing :php:`showitem`
    configurations
-   Perform string operations on :php:`showitem` definitions expecting
    specific :php:`LLL:EXT:` path formats
-   Generate documentation or analysis tools based on TCA label path references

The extension scanner will not detect these usages as they involve runtime
string manipulation rather than direct PHP API usage.

**Note:** Additional TCA elements beyond tab labels may follow this pattern
in future TYPO3 versions, further extending the use of short form references
in :php:`showitem` strings.

Migration
=========

Extension developers should review their :file:`Configuration/TCA/Overrides/`
files and any PHP code that manipulates TCA :php:`showitem` strings
programmatically.

**Option 1: Support both formats in string operations**

Update your code to handle both the old :php:`LLL:EXT:` path format and the
new short form references:

.. code-block:: php

    // Before - hardcoded search for old format
    if (str_contains($showitem, 'LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general')) {
        // Will not work in TYPO3 v14+
    }

    // After - handle new format
    if (str_contains($showitem, 'core.form.tabs:general') ||
        str_contains($showitem, 'LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general')) {
        // Works in both versions
    }

**Option 2: Use TCA API instead of string manipulation**

Instead of manipulating TCA strings directly, use TYPO3's TCA manipulation
APIs:

.. code-block:: php

    // Instead of string manipulation
    use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

    // Add fields using the API
    ExtensionManagementUtility::addToAllTCAtypes(
        'tx_myext_domain_model_foo',
        'my_field',
        '',
        'after:title'
    );


**Recommended action for all extension developers**

Scan your extension's :file:`Configuration/TCA/Overrides/` directory and any
PHP code that works with :php:`showitem` strings for patterns like:

-   :php:`str_contains()`, :php:`str_replace()`, :php:`preg_match()` or similar
    string functions operating on :php:`showitem` values
-   String operations looking for :php:`'LLL:EXT:'` patterns in TCA
    configurations
-   Custom parsing of :php:`$GLOBALS['TCA']` :php:`showitem` strings expecting
    specific path formats

This review is particularly important as additional TCA elements may adopt
short form references in future versions.

..  index:: TCA, NotScanned, ext:core
