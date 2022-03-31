.. include:: /Includes.rst.txt

==================================================================================
Breaking: #23736 - Page Language detection set earlier in Frontend Request Process
==================================================================================

See :issue:`23736`

Description
===========

Previous TYPO3 sites without Site Handling
used TypoScript conditions like `[globalVar = GP:L = 1]` to switch
between languages. For this, TYPO3's Frontend Request Process needed a parsed TypoScript before
doing the language overlay of the currently visited page.

This made it impossible to use conditions for accessing the translated page record like `[page["nav_title"] == "Bienvenue"]`,
which was a long outstanding conceptual issue that was finally made possible through Site Handling.

Now, the translated page is resolved directly after the actual page and rootline resolving.


Impact
======

The translated page record (based on the fallback handling in the
Site Configuration) is now available in :php:`$TSFE->page` at a much earlier stage of the Frontend Request process.

This means, TypoScript conditions based on the page record (see example above) might be different.

In addition, the two hooks

:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_preProcess']` and
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['settingLanguage_postProcess']`

are called earlier, no TypoScript is available yet.


Affected Installations
======================

TYPO3 installations with custom extensions using the hooks mentioned
above or that have language-specific "page-based" conditions.


Migration
=========

Review the hooks or use a PSR-15 middleware to use the same place
to extend TYPO3's Frontend Request process after TypoScript was
initialized.

Also, be sure to review any of the TypoScript conditions (possible
via the `Web->Template` module) if they are related to values only
available in the default language, which seems to be a very rare case however.

.. index:: PHP-API, TypoScript, NotScanned, ext:frontend
