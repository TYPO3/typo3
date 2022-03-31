
.. include:: /Includes.rst.txt

==================================================================
Breaking: #77182 - Removed BasicFileUtility methods and properties
==================================================================

See :issue:`77182`

Description
===========

The `BasicFileUtility` class was used for checking file mounts and paths, and is only
used for non-FAL files.
Now, old legacy functionality has been removed.

The :php:`init()` method has been replaced by a real constructor. A possibility to set the
file extension permissions has been added via `setFileExtensionPermissions()`.

The DefaultConfiguration setting :php:`$GLOBALS[TYPO3_CONF_VARS][BE][fileExtensions][ftpspace]`
has been removed.

The following public properties within BasicFileUtility have been removed:

* `getUniqueNamePrefix`
* `tempFN`
* `f_ext`
* `mounts`
* `webPath`
* `isInit`

The following public methods within `BasicFileUtility` have been removed:

* `checkPathAgainstMounts()`
* `findFirstWebFolder()`
* `slashPath()`
* `is_webpath()`
* `checkIfFullAccess()`
* `init()`

The following public properties within `BasicFileUtility` have been set to have a protected visibility:

* `is_directory`
* `is_allowed`


Impact
======

Calling any of the methods above or using one of the properties above will result in PHP errors and warnings respectively.

Using the `TYPO3_CONF_VARS` setting has no effect anymore.


Affected Installations
======================

Any installation using pre - 6.0 core functionality within extensions.


Migration
=========

Use the File Abstraction Layer to achieve the same functionality.

.. index:: PHP-API, FAL, LocalConfiguration
