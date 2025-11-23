..  include:: /Includes.rst.txt

..  _breaking-106427-1742911405:

==========================================================
Breaking: #106427 - File Abstraction Layer related changes
==========================================================

See :issue:`106427`

Description
===========

In TYPO3 v14, the PHP API of the **File Abstraction Layer (FAL)** has undergone
major internal changes that may affect extension authors.

1.  Most PHP classes and interfaces within FAL are now strongly typed using
    native PHP type declarations.

2.  Several methods have been moved from
    :php-short:`\TYPO3\CMS\Core\Resource\AbstractFile` to its concrete
    implementation :php-short:`\TYPO3\CMS\Core\Resource\File`, since only this
    class should provide such behavior. The moved methods are:

    -   :php:`AbstractFile->rename()`
    -   :php:`AbstractFile->copyTo()`
    -   :php:`AbstractFile->moveTo()`

3.  The :php-short:`\TYPO3\CMS\Core\Resource\FileInterface` method
    :php:`rename()` has been removed, as this operation is only relevant to the
    concrete :php-short:`\TYPO3\CMS\Core\Resource\File` implementation.

4.  Classes implementing :php-short:`\TYPO3\CMS\Core\Resource\FolderInterface`
    must now implement the following methods to stay compatible with the new
    :php-short:`\TYPO3\CMS\Core\Resource\Folder` class:

    -   :php:`getSubFolder()`
    -   :php:`getReadablePath()`
    -   :php:`getFiles()`
    -   :php:`searchFiles()`

    Previously, this was not enforced, but it is now required to make
    :php:`Folder` more interchangeable across implementations.

Impact
======

Calling PHP classes or methods from the File Abstraction Layer directly may
result in fatal PHP errors if incorrect or missing argument types are used.

Affected installations
======================

TYPO3 installations with third-party extensions that interact with the FAL PHP
API in non-documented ways.

Migration
=========

Ensure that all FAL-related code passes and expects the correct PHP types when
using or extending the API.

If you provide custom implementations of
:php-short:`\TYPO3\CMS\Core\Resource\FolderInterface` or classes extending
:php-short:`\TYPO3\CMS\Core\Resource\AbstractFile`, make sure to implement the
newly required methods accordingly.

..  index:: FAL, PHP-API, NotScanned, ext:core
