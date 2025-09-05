..  include:: /Includes.rst.txt

..  _breaking-107482-1758143015:

======================================================================
Breaking: #107482 - Environment::getComposerRootPath method removed
======================================================================

See :issue:`107482`

Description
===========

The following method in :php:`TYPO3\CMS\Core\Core\Environment` has been
removed in TYPO3 v14.0:

* :php:`Environment::getComposerRootPath()`

Since composer installers v4/v5 which are required since TYPO3 v12
:php:`getComposerRootPath()` and :php:`getProjectPath()` are the same,
since the project path can not be changed any more through configuration.

Therefore the method :php:`Environment::getComposerRootPath()` can be removed.

This method was marked internal from the beginning.

Impact
======

Calling this method will trigger a PHP error.

Affected installations
======================

TYPO3 installations with custom extensions or code that directly call the
deprecated method:

* :php:`Environment::getComposerRootPath()`

The extension scanner will report any usage as strong match.

Migration
=========

Instead of calculating relative paths manually, use absolute paths or the
appropriate TYPO3 APIs for path handling:

* Use :php:`Environment::getProjectPath()` instead

..  index:: PHP-API, FullyScanned, ext:core
