..  include:: /Includes.rst.txt

..  _breaking-107310-1755533400:

===========================================================
Breaking: #107310 - Remove FreezableBackendInterface
===========================================================

See :issue:`107310`

Description
===========

The :php:`FreezableBackendInterface` has been removed from TYPO3 Core.

The interface defined the following methods:

- :php:`freeze()` - Freezes this cache backend.
- :php:`isFrozen()` - Tells if this backend is frozen.


Impact
======

Any code that implements or references :php:`\TYPO3\CMS\Core\Cache\Backend\FreezableBackendInterface`
will cause PHP fatal errors.

Since this interface was never implemented in TYPO3 Core and had no real-world
usage, the impact should be minimal for most installations.

Affected installations
======================

Installations with custom extensions that implement or reference the
:php:`FreezableBackendInterface` are affected.


Migration
=========

Remove any references to :php:`\TYPO3\CMS\Core\Cache\Backend\FreezableBackendInterface`
from your code.

If you need the freeze functionality, implement your own logic directly in your
cache backend class.

.. index:: PHP-API, FullyScanned
