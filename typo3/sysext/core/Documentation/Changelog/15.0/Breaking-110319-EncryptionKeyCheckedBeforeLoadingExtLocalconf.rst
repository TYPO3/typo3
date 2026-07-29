.. include:: /Includes.rst.txt

.. _breaking-110319-1785315901:

==============================================================================
Breaking: #110319 - Encryption key is checked before ext_localconf.php loading
==============================================================================

See :issue:`110319`

Description
===========

:php:`\TYPO3\CMS\Core\Core\Bootstrap` verifies that
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']` is not empty and
throws a :php:`\RuntimeException` otherwise. This check has been moved to
run *before* the :php:`ext_localconf.php` files of all active extensions are
loaded. Previously it ran afterwards, and additionally after
:php:`$GLOBALS['TCA']` had been populated.

The encryption key is part of the system configuration and is used for
security-relevant functionality. It must therefore be in place before any
extension code is executed during bootstrap.

In addition, :php:`$GLOBALS['TCA']` is now assigned after
:php:`TcaSchemaFactory->load()` has been called, so consumers can not
observe a state where the global array is populated but the schema
information is not yet available.

Impact
======

Installations that set :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']`
from within an :php:`ext_localconf.php` file now run into the
"TYPO3 Encryption Key is empty" exception, as the check is executed before
the file is evaluated.

Code within :php:`ext_localconf.php` files can not rely on
:php:`$GLOBALS['TCA']` being available anymore - which was never a
guaranteed state, as :php:`ext_localconf.php` files are loaded before TCA
is built. This is unchanged, but the assignment now happens later within
the bootstrap.

Affected installations
======================

Installations that populate the encryption key at runtime from an
:php:`ext_localconf.php` file, for instance by reading it from an
environment variable or an external secret store.

Migration
=========

Set the encryption key in the system configuration, either directly in
:file:`config/system/settings.php` or in
:file:`config/system/additional.php`, both of which are evaluated before
the check:

..  code-block:: php
    :caption: config/system/additional.php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = getenv('TYPO3_ENCRYPTION_KEY');

.. index:: LocalConfiguration, PHP-API, NotScanned, ext:core
