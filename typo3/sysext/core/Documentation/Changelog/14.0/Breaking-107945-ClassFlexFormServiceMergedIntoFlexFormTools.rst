..  include:: /Includes.rst.txt

..  _breaking-107945-1761875852:

===================================================================
Breaking: #107945 - Class FlexFormService merged into FlexFormTools
===================================================================

See :issue:`107945`

Description
===========

Class :php:`TYPO3\CMS\Core\Service\FlexFormService` has been merged
into :php:`TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools`.

This affected service methods:

* :php:`FlexFormService` :php:`convertFlexFormContentToArray($flexFormContent, $languagePointer = 'lDEF', $valuePointer = 'vDEF'): array`
  is now :php:`FlexFormTools` :php:`convertFlexFormContentToArray(string $flexFormContent): array`. Method name is kept, but note the signature change.

* :php:`FlexFormService` :php:`convertFlexFormContentToSheetsArray(string $flexFormContent, string $languagePointer = 'lDEF', string $valuePointer = 'vDEF'): array`
  is now :php:`FlexFormTools` :php:`convertFlexFormContentToSheetsArray(string $flexFormContent): array`. Method name is kept, but note the signature change.

* Helper method :php:`FlexFormService->walkFlexFormNode()` is a private member of :php:`FlexFormTools`.


Impact
======

Injecting and creating instances of class :php:`FlexFormService` is still possible due an
established class alias in TYPO3 v14. The alias will be removed in TYPO3 v15.


Affected installations
======================

Instances using :php:`FlexFormService` are affected. The extension scanner will find
affected extensions.


Migration
=========

Extensions most likely never used the now private helper method :php:`walkFlexFormNode()`. In case the unlikely
case the method was used, it should be implemented within the affected extensions.

Methods :php:`convertFlexFormContentToArray()` and :php:`convertFlexFormContentToSheetsArray()` loose their second
and third argument which where most likely not used: :php:`lDEF` and :php:`vDEF` are the only allowed language and value
strings within TYPO3 for a while, they can not be changed and have been hard coded.

To continue using :php:`convertFlexFormContentToArray()` and :php:`convertFlexFormContentToSheetsArray()`, consuming
classes should inject an instance of :php:`FlexFormTools` instead. Extensions with compatibility for TYPO3 v13 and v14
could continue using :php:`FlexFormService`, but must switch to :php:`FlexFormTools` when adding TYPO3 v15 compatibility
and dropping TYPO3 v13.


..  index:: FlexForm, PHP-API, FullyScanned, ext:core
