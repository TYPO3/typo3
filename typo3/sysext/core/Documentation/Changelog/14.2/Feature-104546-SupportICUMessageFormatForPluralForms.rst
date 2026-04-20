..  include:: /Includes.rst.txt

..  _feature-104546-1737580000:

==============================================================
Feature: #104546 - Support ICU MessageFormat for plural forms
==============================================================

See :issue:`104546`

Description
===========

TYPO3 now supports ICU MessageFormat for translations. This enables the proper handling
of plural forms, gender-based selections, and other locale-aware formatting
in language labels.

ICU MessageFormat is an internationalization standard that allows messages to
contain placeholders that can vary based on parameters such as quantity,
gender, or other conditions. This is particularly useful for proper
pluralization in languages with complex plural rules.

The format is detected automatically when named arguments, that is,
associative arrays, are used in translation calls. If the message contains ICU
patterns like `{count, plural, ...}` or `{name}`, and named arguments are
provided, the ICU MessageFormatter is used automatically.

Language file format
--------------------

ICU MessageFormat strings are stored as regular translation strings in XLIFF
files:

..  code-block:: xml
    :caption: EXT:my_extension/Resources/Private/Language/locallang.xlf

    <?xml version="1.0" encoding="UTF-8"?>
    <xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
        <file source-language="en" datatype="plaintext" original="locallang.xlf">
            <body>
                <!-- Simple plural form -->
                <trans-unit id="file_count">
                    <source>{count, plural, one {# file} other {# files}}</source>
                </trans-unit>

                <!-- Plural with zero case -->
                <trans-unit id="item_count">
                    <source>{count, plural, =0 {no items} one {# item} other {# items}}</source>
                </trans-unit>

                <!-- Combined placeholder and plural -->
                <trans-unit id="greeting">
                    <source>Hello {name}, you have {count, plural, one {# message} other {# messages}}.</source>
                </trans-unit>

                <!-- Gender selection -->
                <trans-unit id="profile_update">
                    <source>{gender, select, male {He} female {She} other {They}} updated the profile.</source>
                </trans-unit>

                <!-- Simple named placeholder -->
                <trans-unit id="welcome">
                    <source>Welcome, {name}!</source>
                </trans-unit>
            </body>
        </file>
    </xliff>

PHP usage
---------

Use named arguments in an associative array to trigger ICU
MessageFormat processing:

..  code-block:: php
    :caption: Using ICU MessageFormat with LanguageService

    use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

    $languageService = GeneralUtility::makeInstance(LanguageServiceFactory::class)
        ->createFromUserPreferences($backendUser);

    // ICU plural forms: use named arguments
    $label = $languageService->translate(
        'file_count',
        'my_extension.messages',
        ['count' => 5]
    );
    // Result: "5 files"

    // Combined placeholder and plural
    $label = $languageService->translate(
        'greeting',
        'my_extension.messages',
        ['name' => 'John', 'count' => 3]
    );
    // Result: "Hello John, you have 3 messages."

    // sprintf-style still works with positional arguments
    $label = $languageService->translate(
        'downloaded_times',  // Label: "Downloaded %d times"
        'my_extension.messages',
        [42]  // Positional arguments use sprintf
    );
    // Result: "Downloaded 42 times"

..  code-block:: php
    :caption: Using ICU MessageFormat with LocalizationUtility

    use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

    // Use named arguments for ICU format
    $label = LocalizationUtility::translate(
        'file_count',
        'MyExtension',
        ['count' => 1]
    );
    // Result: "1 file"

Fluid usage
-----------

In Fluid templates use named arguments in the `arguments` attribute:

..  code-block:: html
    :caption: EXT:my_extension/Resources/Private/Templates/Example.html

    <!-- ICU plural forms with named arguments -->
    <f:translate key="file_count" arguments="{count: numberOfFiles}" />

    <!-- Combined placeholder and plural -->
    <f:translate key="greeting" arguments="{name: userName, count: messageCount}" />

    <!-- Gender selection -->
    <f:translate key="profile_update" arguments="{gender: userGender}" />

    <!-- sprintf-style with positional arguments still works -->
    <f:translate key="downloaded_times" arguments="{0: downloadCount}" />

ICU MessageFormat syntax reference
----------------------------------

**Plural forms:**

..  code-block:: text

    {variable, plural,
        =0 {zero case}
        one {singular case}
        other {plural case}
    }

**Select (gender/choice):**

..  code-block:: text

    {variable, select,
        male {He}
        female {She}
        other {They}
    }

**Number formatting:**

..  code-block:: text

    {count, number}           - Basic number
    {price, number, currency} - Currency format

The `#` symbol in plural patterns is replaced by the actual number.

Impact
======

This feature provides a standards-based approach to pluralization that:

*   uses the well-tested ICU library, via PHP's intl extension
*   handles locale-specific plural rules
*   supports complex pluralization for languages such as Russian and Arabic
*   is backward compatible; existing sprintf-style translations will continue to
    work

The system detects which format to use based on arguments:

*   **Named arguments** (associative array): Uses ICU MessageFormat
*   **Positional arguments** (indexed array): Uses sprintf

..  index:: PHP-API, Fluid, ext:core, ext:extbase, ext:fluid
