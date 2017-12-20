
.. include:: ../../Includes.txt

=============================================================
Breaking: #64714 - Catch exceptions for inaccessible storages
=============================================================

See :issue:`64714`

Description
===========

Changed `\TYPO3\CMS\Core\Resource\Exception\ResourcePermissionsUnavailableException` to extend
from `\TYPO3\CMS\Core\Resource\Exception` instead of `\RuntimeExtension`


Impact
======

If a call to `\TYPO3\CMS\Core\Resource\Driver\LocalDriver->getPermissions()` throws an exception and
your extension catches `\RuntimeExtension` it breaks on permission read errors.


Migration
=========

To write a compatible extension you can do following in your catch block:

.. code-block:: php

	} catch(\RuntimeException $e) {
		// Do exception handling
	} catch(\TYPO3\CMS\Core\Resource\Exception\ResourcePermissionsUnavailableException $e) {
		// Do same exception handling
	}


.. index:: PHP-API, FAL, Backend
