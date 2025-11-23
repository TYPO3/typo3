..  include:: /Includes.rst.txt

..  _breaking-105686-1732289792:

=================================================================
Breaking: #105686 - Avoid obsolete $charset in sanitizeFileName()
=================================================================

See :issue:`105686`

Description
===========

The interface
:php:`\TYPO3\CMS\Core\Resource\Driver\DriverInterface` has been updated.

The method signature

..  code-block:: php

    public function sanitizeFileName(string $fileName, string $charset = ''): string

has been simplified to:

..  code-block:: php

    public function sanitizeFileName(string $fileName): string

Implementing classes no longer need to handle a second argument.

Impact
======

This change has little to no impact, since the main API caller - the Core
class :php-short:`\TYPO3\CMS\Core\Resource\ResourceStorage` - never passed a
second argument. The default implementation,
:php-short:`\TYPO3\CMS\Core\Resource\Driver\LocalDriver`, has therefore always
behaved as if handling UTF-8 strings.

Affected installations
======================

TYPO3 installations with custom File Abstraction Layer (FAL) drivers
implementing :php-short:`\TYPO3\CMS\Core\Resource\Driver\DriverInterface` may
be affected.

Migration
=========

Implementing classes should drop support for the second argument. Retaining it
does not cause a conflict with the interface, but the TYPO3 Core will never
call :php:`sanitizeFileName()` with a second parameter.

..  index:: FAL, PHP-API, NotScanned, ext:core
