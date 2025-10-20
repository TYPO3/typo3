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

-   :php:`LLL:EXT:core/Resources/Private/Language/Form/locallang_tca.xlf:*`
    → :php:`core.form.tabs:*`
-   :php:`LLL:EXT:filemetadata/Resources/Private/Language/locallang_tca.xlf:tabs.*`
    → :php:`core.form.tabs:*`
-   :php:`LLL:EXT:seo/Resources/Private/Language/locallang_tca.xlf:pages.tabs.*`
    → :php:`core.form.tabs:*`

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
