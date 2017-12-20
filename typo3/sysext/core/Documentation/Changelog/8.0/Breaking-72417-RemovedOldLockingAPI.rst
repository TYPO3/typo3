
.. include:: ../../Includes.txt

==========================================
Breaking: #72417 - Removed old locking API
==========================================

See :issue:`72417`

Description
===========

The old locking mechanism was replaced by a more sophisticated a robust LockFactory,
and is now completely removed from the TYPO3 Core.


Impact
======

Using the `Locker` class will result in a fatal error. The option `$TYPO3_CONF_VARS[SYS][lockingMode]` has
no effect anymore.

.. index:: PHP-API, LocalConfiguration
