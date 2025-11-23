..  include:: /Includes.rst.txt

..  _breaking-107310-1755533400:

===========================================================
Breaking: #107310 - Remove FreezableBackendInterface
===========================================================

See :issue:`107310`

Description
===========

The interface :php:`\TYPO3\CMS\Core\Cache\Backend\FreezableBackendInterface`
has been removed from the TYPO3 Core.

It previously defined the following methods:

-   :php:`freeze()` — Freezes the cache backend.
-   :php:`isFrozen()` — Returns whether the backend is frozen.

Impact
======

Any code implementing or referencing
:php-short:`\TYPO3\CMS\Core\Cache\Backend\FreezableBackendInterface`
will now trigger a PHP fatal error.

Since this interface was never implemented in the TYPO3 Core and had no known
real-world usage, the overall impact is expected to be minimal.

Affected installations
======================

Installations with custom extensions that implement or reference the
:php-short:`\TYPO3\CMS\Core\Cache\Backend\FreezableBackendInterface`
are affected.

Migration
=========

Remove any references to
:php-short:`\TYPO3\CMS\Core\Cache\Backend\FreezableBackendInterface`
from your extension code.

If you require freeze functionality, implement the desired behavior directly
in your custom cache backend class.

..  index:: PHP-API, FullyScanned
