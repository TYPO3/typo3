..  include:: /Includes.rst.txt

..  _breaking-107945-1761875852:

===================================================================
Breaking: #107945 - Class FlexFormService merged into FlexFormTools
===================================================================

See :issue:`107945`

Description
===========

The class :php:`\TYPO3\CMS\Core\Service\FlexFormService` has been merged into
:php:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools`.

The following methods are affected:

*   :php:`FlexFormService->convertFlexFormContentToArray($flexFormContent, $languagePointer = 'lDEF', $valuePointer = 'vDEF'): array`
    is now :php:`FlexFormTools->convertFlexFormContentToArray(string $flexFormContent): array`.
    The method name is unchanged, but the method signature has been simplified.

*   :php:`FlexFormService->convertFlexFormContentToSheetsArray(string $flexFormContent, string $languagePointer = 'lDEF', string $valuePointer = 'vDEF'): array`
    is now :php:`FlexFormTools->convertFlexFormContentToSheetsArray(string $flexFormContent): array`.
    Again, the name is identical, but the parameters have been reduced.

*   The helper method :php:`FlexFormService->walkFlexFormNode()` has been made a
    private method within :php:`FlexFormTools`.

Impact
======

Instantiating or injecting :php-short:`\TYPO3\CMS\Core\Service\FlexFormService`
remains possible in TYPO3 v14 due to a maintained class alias for backward
compatibility.

This alias will be **removed in TYPO3 v15**.

Affected installations
======================

Any extensions or TYPO3 installations using
:php-short:`\TYPO3\CMS\Core\Service\FlexFormService` are affected.

The extension scanner will automatically detect these usages.

Migration
=========

Extensions typically did not use the now internal helper method
:php:`walkFlexFormNode()`.

In the unlikely case this private method was used, its functionality must now be
implemented within the consuming extension.

The methods :php:`convertFlexFormContentToArray()` and
:php:`convertFlexFormContentToSheetsArray()` have lost their second and third
arguments.

These parameters (:php:`lDEF` and :php:`vDEF`) were already fixed internally in
TYPO3 and could no longer be changed, so their removal has no functional impact.

To continue using these methods, extensions should inject
:php-short:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools` instead of
:php:`FlexFormService`.

For extensions that need to remain compatible with both TYPO3 v13 and v14, it is
still possible to use :php-short:`\TYPO3\CMS\Core\Service\FlexFormService` for
now.

However, when adding compatibility for TYPO3 v15 (and dropping TYPO3 v13),
extensions must switch fully to
:php-short:`\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools`.

..  index:: FlexForm, PHP-API, FullyScanned, ext:core
