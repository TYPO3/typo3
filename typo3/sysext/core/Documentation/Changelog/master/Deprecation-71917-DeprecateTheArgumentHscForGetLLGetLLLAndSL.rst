===========================================================================
Deprecation: #71917 - Deprecate the argument 'hsc' for getLL, getLLL and sL
===========================================================================

Description
===========

The parameter :php:`$hsc` within the following methods of :php:`TYPO3\CMS\Lang\LanguageService` has been marked as deprecated:

* :php:`getLL()`
* :php:`getLLL()`
* :php:`sL()`


Impact
======

Directly or indirectly using any of the methods :php:`getLL()`, :php:`getLLL()` or :php:`sL()` with the parameter :php:`$hsc` will trigger a deprecation log entry.


Affected Installations
======================

Any installation with a third-party extension calling one of the methods in its PHP code.


Migration
=========

If the return value of these methods is output in HTML context use :php:`htmlspecialchars` directly to properly escape the content.