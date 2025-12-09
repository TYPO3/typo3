..  include:: /Includes.rst.txt

..  _important-107971-1730384000:

==========================================================
Important: #107971 - XLF files now use 2-space indentation
==========================================================

See :issue:`107971`

Description
===========

TYPO3 Core XLF (XLIFF) translation files now consistently use 2-space
indentation instead of tabs. This aligns with the formatting used by Crowdin
and by PHP's :php:`\DOMDocument`. The :file:`.editorconfig` file has been updated
accordingly.

In addition to indentation, all XLF files have been normalized using a unified
XML formatter. This ensures consistent XML declaration, attribute ordering, and
whitespace structure across all XLF files in the Core.

Checking and normalizing XLF formatting
=======================================

A new script has been introduced to check and normalize XLF formatting.

Checking formatting via `runTests.sh`
-------------------------------------

To check whether XLF files have correct formatting (dry-run, no modifications):

..  code-block:: bash

    ./Build/Scripts/runTests.sh -s normalizeXliff -n

This command scans all XLF files in :file:`typo3/sysext/` and reports files that
would be changed.

Normalizing XLF files via `runTests.sh`
---------------------------------------

To normalize all XLF files in-place:

..  code-block:: bash

    ./Build/Scripts/runTests.sh -s normalizeXliff

This command applies consistent indentation and XML normalization to all XLF
files in :file:`typo3/sysext/`.

Using the standalone script
---------------------------

The script can also be run directly. Requires PHP 8.2+ with DOM and intl
extensions enabled.

..  code-block:: bash

    # Show help
    ./Build/Scripts/xliffNormalizer.php --help

    # Check files only (dry-run)
    ./Build/Scripts/xliffNormalizer.php --root typo3/sysext --dry-run

    # Normalize files in place
    ./Build/Scripts/xliffNormalizer.php --root typo3/sysext

    # Check a custom directory
    ./Build/Scripts/xliffNormalizer.php --root path/to/xlf/files --dry-run

Impact
======

Extension developers should update their XLF files to use 2-space indentation
and expect normalized XML formatting.
The provided script can also be used within extensions to keep XLF files
consistent with TYPO3 Core standards.

.. index:: Backend, Localization, XLF
