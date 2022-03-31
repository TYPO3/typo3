
.. include:: /Includes.rst.txt

=================================
Feature: #47712 - New Locking API
=================================

See :issue:`47712`

Description
===========

The new Locking API follows a new approach. Due to the problem of a very scattered support of locking methods
in the various operating systems, the new API introduces a locking service, which provides access to the various
locking methods. Some basic methods are shipped with the Core, but the available methods may be extended by
extensions.

A locking method has to implement the `LockingStrategyInterface`. Each method has a set of capabilities, which
may vary depending on the current system, and a priority.

If a function requires a lock, the locking service is asked for the best fitting mechanism matching the requested
capabilities.
e.g. Semaphore locking is only available on Linux systems.

Usage example
=============

Acquire a simple exclusive lock:

.. code-block:: php

	$lockFactory = GeneralUtility::makeInstance(LockFactory::class);
	$locker = $lockFactory->createLocker('someId');
	$locker->acquire() || die('ups, lock couldn\'t be acquired. That should never happen.');
	...
	$locker->release();


Some methods also support non-blocking locks:

.. code-block:: php

	$lockFactory = GeneralUtility::makeInstance(LockFactory::class);
	$locker = $lockFactory->createLocker(
		'someId',
		LockingStrategyInterface::LOCK_CAPABILITY_SHARED | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK
	);
	try {
		$result = $locker->acquire(LockingStrategyInterface::LOCK_CAPABILITY_SHARED | LockingStrategyInterface::LOCK_CAPABILITY_NOBLOCK);
	catch (LockAcquireWouldBlockException $e) {
		// some process owns the lock, let's do something else meanwhile
	}
	if ($result) {
		$locker->release();
	}


.. index:: PHP-API
