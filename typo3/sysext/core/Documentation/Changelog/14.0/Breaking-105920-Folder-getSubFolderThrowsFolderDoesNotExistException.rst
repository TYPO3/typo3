..  include:: /Includes.rst.txt

..  _breaking-105920-1736777357:

=============================================================================
Breaking: #105920 - Folder->getSubFolder() throws FolderDoesNotExistException
=============================================================================

See :issue:`105920`

Description
===========

An exception handling detail within FAL/Resource handling has been changed: When
calling :php:`getSubFolder('mySubFolderName')` on a :php:`\TYPO3\CMS\Core\Resource\Folder`
object, and if this sub folder does not exist, the specific
:php:`\TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException` is now raised
instead of the global :php:`\InvalidArgumentException`.


Impact
======

The change may have impact on extensions that directly or indirectly call
:php:`Folder->getSubFolder()` and expect a :php:`\InvalidArgumentException`
to be thrown.


Affected installations
======================

:php:`FolderDoesNotExistException` does not extend :php:`\InvalidArgumentException`.
Code that currently expects a :php:`\InvalidArgumentException` to be thrown, needs
adaption.



Migration
=========

The change is breaking for code that takes an "optimistic" approach like
"get the sub folder object, and if this throws, create one". Example:

.. code-block:: php

    try {
        $mySubFolder = $myFolder->getSubFolder('mySubFolder');
    } catch (\InvalidArgumentException) {
        $mySubFolder = $myFolder->createFolder('mySubFolder');
    }

This should be changed to catch a :php:`FolderDoesNotExistException` instead:

.. code-block::php

    use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;

    try {
        $mySubFolder = $myFolder->getSubFolder('mySubFolder');
    } catch (FolderDoesNotExistException) {
        $mySubFolder = $myFolder->createFolder('mySubFolder');
    }

Extensions that need to stay compatible with both TYPO3 v13 and v14 should catch
both exceptions and should later avoid catching :php:`\InvalidArgumentException`
when v13 compatibility is dropped:

.. code-block::php

    use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;

    try {
        $mySubFolder = $myFolder->getSubFolder('mySubFolder');
    } catch (\InvalidArgumentException|FolderDoesNotExistException) {
        // @todo: Remove \InvalidArgumentException from catch list when
        //        TYPO3 v13 compatibility is dropped.
        $mySubFolder = $myFolder->createFolder('mySubFolder');
    }


..  index:: FAL, PHP-API, NotScanned, ext:core
