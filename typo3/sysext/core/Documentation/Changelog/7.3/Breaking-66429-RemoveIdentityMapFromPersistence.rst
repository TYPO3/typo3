
.. include:: /Includes.rst.txt

======================================================
Breaking: #66429 - Remove IdentityMap from persistence
======================================================

See :issue:`66429`

Description
===========

The `IdentityMap` class and its usage has been removed from the Extbase persistence.


Impact
======

Upgraded installations will throw a `ReflectionException`. Accessing the previously existing `IdentityMap`
properties within `DataMapper` and `Repository` will now fail. Creating `IdentityMap` instances is not possible
anymore.


Affected Installations
======================

All installations, especially extensions using the `IdentityMap` class directly or accessing the properties within
`DataMapper` or `Repository`.


Migration
=========

The Extbase reflection cache of existing installations needs to be cleared once.

Existing code can be migrated to the persistence `Session` class which provides a drop-in replacement for the
`IdentityMap`.


Usage example
=============

How to use the `Session` class to retrieve objects by an identifier:

.. code-block:: php

	$session = GeneralUtility::makeInstance(ObjectManager::class)->get(\TYPO3\CMS\Extbase\Persistence\Generic\Session::class);
	$session->registerObject($object, $identifier);
	if ($session->hasIdentifier($identifier)) {
		$object = $session->getObjectByIdentifier($identifier, $className);
	}


.. index:: PHP-API, ext:extbase
