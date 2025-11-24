..  include:: /Includes.rst.txt

..  _breaking-107789-1729603200:

=========================================================================================
Breaking: #107789 - Core TCA and user settings showitem strings use short form references
=========================================================================================

See :issue:`107789`

Description
===========

TYPO3 Core TCA and user settings (:php:`$GLOBALS['TYPO3_USER_SETTINGS']`)
configurations have been updated to use short form
translation reference formats (e.g., `core.form.tabs:*`) instead of
the full `LLL:EXT:` path format in `showitem` strings.

This change affects all core TCA `showitem` definitions that previously
used full `LLL:EXT:` paths for labels. The most prominent updates are
tab labels using the `--div--` syntax, though this pattern may be applied
to other TCA elements in the future.

Examples of changed references in tab labels:

..  code-block:: php

    // Before (TYPO3 v13)
    '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general'
    '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access'
    '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language'

..  code-block:: php

    // After (TYPO3 v14)
    '--div--;core.form.tabs:general'
    '--div--;core.form.tabs:access'
    '--div--;core.form.tabs:language'

Impact
======

Custom extensions that programmatically manipulate TCA or
:php:`$GLOBALS['TYPO3_USER_SETTINGS']` `showitem` strings
from core tables and expect the full `LLL:EXT:` path format will break.

This particularly affects code that:

-   Uses string search/replace operations on `showitem` strings to find or
    modify specific labels (tabs, palettes, or other elements)
-   Parses `showitem` strings using regular expressions expecting the
    `LLL:EXT:` pattern
-   Extracts translation keys from TCA configurations for analysis or
    documentation purposes
-   Builds custom TCA configurations by copying and modifying core
    `showitem` strings

Currently, the following label categories have been migrated to short-form:

**Tab labels (--div--):**

-   `LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:*`
    → `core.form.tabs:*`
-   `LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:tabs.*`
    → `core.form.tabs:*`
-   `LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.tabs.*`
    → `core.form.tabs:*`

**Tab labels (--div--) in TYPO3_USER_SETTINGS:**

-   `LLL:EXT:setup/Resources/Private/Language/locallang.xlf:personal_data`
    → `core.form.tabs:personaldata`
-   `LLL:EXT:setup/Resources/Private/Language/locallang.xlf:accountSecurity`
    → `core.form.tabs:account_security`
-   `LLL:EXT:setup/Resources/Private/Language/locallang.xlf:opening`
    → `core.form.tabs:backend_appearance`
-   `LLL:EXT:setup/Resources/Private/Language/locallang.xlf:personalization`
    → `core.form.tabs:personalization`
-   `LLL:EXT:setup/Resources/Private/Language/locallang.xlf:resetTab`
    → `core.form.tabs:reset_configuration`

**Palette labels (palette definitions):**

-   `LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.palettes.*`
    → `core.form.palettes:*`
-   `LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.palettes.*`
    → `core.form.palettes:*`
-   `LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.*`
    → `core.form.palettes:*`
-   `LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.*`
    → `core.form.palettes:*`
-   `LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:palette.*`
    → `core.form.palettes:*`
-   `LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.palettes.*`
    → `core.form.palettes:*`

**Replaced hardcoded palette label:**

-   Removed the hardcoded palette name in string `--palette--;Capabilities;capabilities`
    in table :sql:`sys_file_storage` in favor of a label attached directly to
    the palette using the short syntax `core.form.palettes:*`.

**Field label overrides removed in showitem definitions:**

Field labels can be overridden in showitem definitions for types or palettes, but
should rather be kept in the field definition itself. The following field label
overrides have been removed from showitem strings in favor of using the field's
own label definition:

-   `bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel`
    → `bodytext`
-   `bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.table.bodytext`
    → `bodytext`
-   `CType;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType_formlabel`
    → `CType`
-   `colPos;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos_formlabel`
    → `colPos`
-   `header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_formlabel`
    → `header`
-   `header_layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout_formlabel`
    → `header_layout`
-   `header_link;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link_formlabel`
    → `header_link`
-   `header_position;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position_formlabel`
    → `header_position`
-   `subheader;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:subheader_formlabel`
    → `subheader`
-   `date;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:date_formlabel`
    → `date`
-   `file_collections;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:file_collections.ALT.uploads_formlabel`
    → `file_collections`
-   `filelink_size;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_size_formlabel`
    → `filelink_size`
-   `image_zoom;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_zoom_formlabel`
    → `image_zoom`
-   `imageborder;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.mediaAdjustments.imageborder`
    → `imageborder`
-   `image*;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageborder_formlabel`
    → `frontend.db.tt_content:imageborder`
-   `imagecols;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagecols_formlabel`
    → `imagecols`
-   `imageorient;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient_formlabel`
    → `imageorient`
-   `imageheight;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.mediaAdjustments.imageheight`
    → `imageheight`
-   `imagewidth;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.mediaAdjustments.imagewidth`
    → `imagewidth`
-   `frame_class;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class_formlabel`
    → `frame_class`
-   `starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.starttime_formlabel`
    → `starttime`
-   `endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.endtime_formlabel`
    → `endtime`
-   `fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.fe_group_formlabel`
    → `fe_group`
-   `media;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media.ALT.uploads_formlabel`
    → `media`
-   `sectionIndex;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sectionIndex_formlabel`
    → `sectionIndex`
-   `linkToTop;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:linkToTop_formlabel`
    → `linkToTop`
-   `layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel`
    → `layout`
-   `space_before_class;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_before_class_formlabel`
    → `space_before_class`
-   `space_after_class;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_after_class_formlabel`
    → `space_after_class`
-   `doktype;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.doktype_formlabel`
    → `doktype`
-   `shortcut_mode;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.shortcut_mode_formlabel`
    → `shortcut_mode`
-   `shortcut;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.shortcut_formlabel`
    → `shortcut`
-   `mount_pid_ol;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.mount_pid_ol_formlabel`
    → `mount_pid_ol`
-   `mount_pid;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.mount_pid_formlabel`
    → `mount_pid`
-   `url;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.url_formlabel`
    → `url`
-   `title;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.title_formlabel`
    → `title`
-   `nav_title;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.nav_title_formlabel`
    → `nav_title`
-   `subtitle;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.subtitle_formlabel`
    → `subtitle`
-   `nav_hide;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:pages.nav_hide_toggle_formlabel`
    → `nav_hide`
-   `extendToSubpages;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.extendToSubpages_formlabel`
    → `extendToSubpages`
-   `abstract;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.abstract_formlabel`
    → `abstract`
-   `keywords;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.keywords_formlabel`
    → `keywords`
-   `author;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.author_formlabel`
    → `author`
-   `author_email;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.author_email_formlabel`
    → `author_email`
-   `lastUpdated;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.lastUpdated_formlabel`
    → `lastUpdated`
-   `newUntil;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.newUntil_formlabel`
    → `newUntil`
-   `backend_layout;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.backend_layout_formlabel`
    → `backend_layout`
-   `backend_layout_next_level;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.backend_layout_next_level_formlabel`
    → `backend_layout_next_level`
-   `module;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.module_formlabel`
    → `module`
-   `content_from_pid;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.content_from_pid_formlabel`
    → `content_from_pid`
-   `cache_timeout;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.cache_timeout_formlabel`
    → `cache_timeout`
-   `l18n_cfg;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.l18n_cfg_formlabel`
    → `l18n_cfg`
-   `is_siteroot;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.is_siteroot_formlabel`
    → `is_siteroot`
-   `no_search;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.no_search_formlabel`
    → `no_search`
-   `php_tree_stop;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.php_tree_stop_formlabel`
    → `php_tree_stop`
-   `editlock;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.editlock_formlabel`
    → `editlock`
-   `media;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.media_formlabel`
    → `media`
-   `tsconfig_includes;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tsconfig_includes`
    → `tsconfig_includes`
-   `TSconfig;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.TSconfig_formlabel`
    → `TSconfig`

**Field label overrides changed in palette definitions:**

-   `hidden;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:pages.hidden_toggle_formlabel`
    → `hidden;core.db.pages:hidden`
-   `hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden`
    → `hidden;frontend.db.tt_content:hidden`
-   `hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.hidden_formlabel`
    → `hidden;core.db.pages:hidden`
-   `starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel`
    → `starttime;core.db.general:starttime`
-   `endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel`
    → `endtime;core.db.general:endtime`
-   `fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:fe_group_formlabel`
    → `fe_group;core.db.general:fe_group`
-   `target;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.target_formlabel`
    → `target;core.db.pages:link.target`

Affected installations
======================

Installations with custom extensions that:

-   Programmatically read and manipulate TCA `showitem` strings from
    :php:`$GLOBALS['TCA']` or :php:`$GLOBALS['TYPO3_USER_SETTINGS']`.
-   Override core TCA or TYPO3_USER_SETTINGS by copying and modifying
    existing `showitem` configurations
-   Perform string operations on `showitem` definitions expecting
    specific `LLL:EXT:` path formats
-   Generate documentation or analysis tools based on TCA label path references

The extension scanner will not detect these usages, as they involve runtime
string manipulation rather than direct PHP API usage.

**Note:**
Additional TCA elements beyond tab labels may follow this pattern in future
TYPO3 versions, further extending the use of short-form references in
`showitem` strings.

Migration
=========

TCA Migration
-------------

Extension developers should review their :file:`Configuration/TCA/Overrides/`
files and any PHP code that manipulates TCA `showitem` strings
programmatically.

**Option 1: Support both formats in string operations**

Update your code to handle both the old `LLL:EXT:` path format and the
new short-form references:

..  code-block:: php

    // Before - hardcoded search for old format
    if (str_contains($showitem, 'LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general')) {
        // Will not work in TYPO3 v14+
    }

..  code-block:: php

    // After - handle new format
    if (str_contains($showitem, 'core.form.tabs:general') ||
        str_contains($showitem, 'LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general')) {
        // Works in both versions
    }

**Option 2: Use the TCA API instead of string manipulation**

Rather than manipulating `showitem` strings directly, use TYPO3's TCA
manipulation APIs:

..  code-block:: php

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
PHP code that interacts with `showitem` strings for patterns such as:

-   :php:`str_contains()`, :php:`str_replace()`, :php:`preg_match()` or similar
    string functions operating on `showitem` values
-   String operations looking for `'LLL:EXT:'` patterns in TCA
    configurations
-   Custom parsing of :php:`$GLOBALS['TCA']` `showitem` strings expecting
    specific path formats

This review is especially important since future TYPO3 versions may further
expand the use of short-form references across additional TCA elements.

TYPO3_USER_SETTINGS migrations
------------------------------

Update your code to handle the new short form references:

**Before:**

..  code-block:: php
    :caption: EXT:my_extension/ext_tables.php

    // Before - hardcoded search for old format
    $showitem = $GLOBALS['TYPO3_USER_SETTINGS']['showitem'];
    if (str_contains($showitem, 'LLL:EXT:setup/Resources/Private/Language/locallang.xlf:personal_data')) {
        // Will not work in TYPO3 v14+
    }

**After:**

..  code-block:: php
    :caption: EXT:my_extension/ext_tables.php

    // After - handle new format
    $showitem = $GLOBALS['TYPO3_USER_SETTINGS']['showitem'];
    if (str_contains($showitem, 'core.form.tabs:personaldata')
        || str_contains($showitem, 'LLL:EXT:setup/Resources/Private/Language/locallang.xlf:personal_data')) {
        // Works in both versions
    }

..  index:: TCA, NotScanned, ext:core
