..  include:: /Includes.rst.txt

..  _breaking-105686-1732289792:

=================================================================
Breaking: #105686 - Avoid obsolete $charset in sanitizeFileName()
=================================================================

See :issue:`105686`

Description
===========

Class :php:`TYPO3\CMS\Core\Resource\Driver\DriverInterface`:

.. code-block:: php

    public function sanitizeFileName(string $fileName, string $charset = ''): string

has been simplified to:

.. code-block:: php

    public function sanitizeFileName(string $fileName): string

Classes implementing the interface no longer need to take care of
a second argument.

Impact
======

This most likely has little to no impact since the main API caller,
the core class :php:`ResourceStorage` never hands over the second
argument. Default implementing class :php:`TYPO3\CMS\Core\Resource\Driver\LocalDriver`
thus always fell back as if handling utf-8 strings.


Affected installations
======================

Projects with instances implementing own FAL drivers using :php:`DriverInterface`
may be affected.


Migration
=========

Implementing classes should drop support for the second argument. It does
not collide with the interface if the second argument is kept, but core
code will never call method :php:`sanitizeFileName()` with handing over
a value for a second argument.

..  index:: FAL, PHP-API, NotScanned, ext:core
