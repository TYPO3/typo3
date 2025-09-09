..  include:: /Includes.rst.txt

..  _deprecation-107413-1725875222:

==============================================================
Deprecation: #107413 - PathUtility getRelativePath(to) methods
==============================================================

See :issue:`107413`

Description
===========

The following methods in :php:`TYPO3\CMS\Core\Utility\PathUtility` have been
marked as deprecated and will be removed in TYPO3 v15.0:

* :php:`PathUtility::getRelativePath()`
* :php:`PathUtility::getRelativePathTo()`

These methods are not needed anymore as TYPO3's path handling has been
simplified due to the unification of the entry point URLs.

Since TYPO3 v13, both frontend and backend use the same main entry point
("htdocs/index.php"), making these relative path calculations obsolete.

Impact
======

Calling these methods will trigger a PHP deprecation warning. The methods will
continue to work as before until they are removed in TYPO3 v15.0.

Affected installations
======================

TYPO3 installations with custom extensions or code that directly call these
deprecated methods:

* :php:`PathUtility::getRelativePath()`
* :php:`PathUtility::getRelativePathTo()`

The extension scanner will report any usage as strong match.

Migration
=========

Instead of calculating relative paths manually, use absolute paths or the
appropriate TYPO3 APIs for path handling:

* Use :php:`GeneralUtility::getFileAbsFileName()` for extension resources
* Use the `EXT:` prefix for referencing extension resources
* Use :php:`PathUtility::getPublicResourceWebPath()` for public extension resources
* Reference paths relative to the public web path of the TYPO3 installation

..  code-block:: php
    :caption: Before (deprecated)

    $relativePath = PathUtility::getRelativePath($sourcePath, $targetPath);
    $relativeToPath = PathUtility::getRelativePathTo($absolutePath);

..  code-block:: php
    :caption: After (recommended)

    // Use absolute paths or appropriate TYPO3 APIs
    $absolutePath = GeneralUtility::getFileAbsFileName('EXT:my_extension/Resources/Public/file.js');
    $webPath = PathUtility::getPublicResourceWebPath('EXT:my_extension/Resources/Public/file.js');

..  index:: PHP-API, FullyScanned, ext:core
