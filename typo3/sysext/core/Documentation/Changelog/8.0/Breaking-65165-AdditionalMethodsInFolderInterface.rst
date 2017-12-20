
.. include:: ../../Includes.txt

=====================================================
Breaking: #65165 - AdditionalMethodsInFolderInterface
=====================================================

See :issue:`65165`

Description
===========

The interface `FolderInterface` has received two additional methods. Classes that implement
`FolderInterface` have to implement those methods as well. The new methods are:

* `getModificationTime()` - Returns the modification time of the folder as Unix timestamp.
* `getCreationTime()` - Returns the creation time of the folder as Unix timestamp.


Impact
======

Classes implementing the `FolderInterface` no longer fulfill the requirements of the interface.


Affected Installations
======================

Installations that use custom implementations of the `FolderInterface`.


Migration
=========

Implement the two new methods in custom implementations of the `FolderInterface`.

.. index:: PHP-API, FAL
