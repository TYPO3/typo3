..  include:: /Includes.rst.txt

..  _deprecation-107938-1762181263:

===================================================
Deprecation: #107938 - Deprecate unused XLIFF files
===================================================

See :issue:`107938`

Description
===========

The following XLIFF files have been deprecated as they are not used in the Core anymore:

*     `EXT:backend/Resources/Private/Language/locallang_view_help.xlf`

They will be removed with TYPO3 v15.0.

The console command `vendor/bin/typo3 language:domain:list`does not list deprecated language domains unless option `--deprecated` is used.


Impact
======

Using a label reference from one of these files triggers a :php:`E_USER_DEPRECATED` error.


Affected installations
======================

Third party extensions and site packages that use labels from the listed sources will not be able to display the affected labels with TYPO3 v15.0.


Migration
=========

If the desired string is contained in another language domain, consider to use that domain. Otherwise move the required labels into your extension or site package.

..  index:: Backend, Frontend, TCA, TypoScript, NotScanned, ext:core