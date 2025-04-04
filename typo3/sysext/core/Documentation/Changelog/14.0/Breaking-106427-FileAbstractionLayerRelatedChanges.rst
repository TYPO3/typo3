..  include:: /Includes.rst.txt

..  _breaking-106427-1742911405:

==========================================================
Breaking: #106427 - File Abstraction Layer related changes
==========================================================

See :issue:`106427`

Description
===========

In TYPO3 v14, the PHP code API for the File Abstraction Layer (FAL) has undergone
some major changes, which might affect extension authors:

1. Most PHP code from FAL is now strongly typed by PHP native typing system

2. Some methods have been moved from :php:`AbstractFile` to its concrete
   implementation within :php:`File` as other derivatives should implement
   this on their own. The moved methods are:
    - :php:`AbstractFile->rename()`
    - :php:`AbstractFile->copyTo()`
    - :php:`AbstractFile->moveTo()`

3. The :php:`FileInterface`'s method :php:`rename()` has been removed as it
   is only necessary within the implementation of :php:`File`.

4. Classes implementing :php:`FolderInterface` must now implement
   :php:`FolderInterface::getSubFolder()`,:php:`FolderInterface::getReadablePath()`
   as well as :php:`FolderInterface::getFiles()` and :php:`FolderInterface::searchFiles()`
   to be interchangeable with the new :php:`Folder` class.

   In previous implementations this was not possible to make Folder more
   interchangeable but this is a first step towards that process.

Impact
======

Calling the PHP classes and methods from File Abstraction Layer directly might
result in fatal PHP errors due to specific types required as method arguments.


Affected installations
======================

TYPO3 installations with third-party extensions that have used FAL API in a
non-documented way.


Migration
=========

Ensure to hand in or expect proper PHP types when using or extending FAL API.

In addition, ensure to implement the new methods in your own :php:`Folder`
implementations or derivatives of :php:`AbstractFile`.

..  index:: FAL, PHP-API, NotScanned, ext:core
