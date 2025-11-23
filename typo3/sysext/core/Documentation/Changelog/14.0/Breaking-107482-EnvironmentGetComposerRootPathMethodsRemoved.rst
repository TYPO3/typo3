..  include:: /Includes.rst.txt

..  _breaking-107482-1758143015:

======================================================================
Breaking: #107482 - Environment::getComposerRootPath method removed
======================================================================

See :issue:`107482`

Description
===========

The following method in :php:`\TYPO3\CMS\Core\Core\Environment` has been
removed in TYPO3 v14.0:

*   :php:`Environment::getComposerRootPath()`

Since composer installers v4/v5 (which are required since TYPO3 v12),
:php:`getComposerRootPath()` and :php:`getProjectPath()` return the same
value, because the project path can no longer be changed through
configuration.

Therefore, the method :php:`Environment::getComposerRootPath()` has been
removed. It was marked as internal from the beginning.

Impact
======

Calling this method will result in a PHP error.

Affected installations
======================

TYPO3 installations with custom extensions or custom code that directly
call the removed method are affected:

* :php:`Environment::getComposerRootPath()`

The extension scanner will report any usage as a strong match.

Migration
=========

Instead of calculating relative paths manually, use absolute paths or
the appropriate TYPO3 APIs for path handling.

Use the following replacement:

*   :php:`Environment::getProjectPath()`

..  index:: PHP-API, FullyScanned, ext:core
