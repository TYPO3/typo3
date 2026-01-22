..  include:: /Includes.rst.txt

..  _important-109672-1745000000:

=============================================================================
Important: #109672 - Translation domain syntax supported in resourceOverrides
=============================================================================

See :issue:`109672`

Description
===========

The translation domain syntax (introduced in TYPO3 v14.0, see
:ref:`feature-93334-1729000000`) can now also be used as the key in the
:php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']` configuration.

Previously, only file-based paths were accepted as keys. Now both the
traditional file path and the short domain syntax are valid:

..  code-block:: php

    // File path syntax (still supported)
    $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']
        ['EXT:core/Resources/Private/Language/locallang_common.xlf'][]
        = 'EXT:my_extension/Resources/Private/Language/locallang_common_override.xlf';

    // Domain syntax (new)
    $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']['core.common'][]
        = 'EXT:my_extension/Resources/Private/Language/locallang_common_override.xlf';

Language-specific overrides also support domain syntax:

..  code-block:: php

    // Language-specific override with domain syntax
    $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']['de']['core.common'][]
        = 'EXT:my_extension/Resources/Private/Language/de.locallang_common_override.xlf';

Impact
======

Existing configurations using file-based keys continue to work without changes.
The domain syntax is an optional, shorter alternative that removes the need to
know the exact file path of the language file being overridden.

..  index:: LocalConfiguration, Localization, ext:core
