.. include:: ../../Includes.txt

====================================================================================
Important: #84623 - Configuration files moved from "typo3conf" to "config" directory
====================================================================================

See :issue:`84623`

Description
===========

The TYPO3 configuration files for instances running in Composer Mode or having the environment
variable `TYPO3_PATH_APP` set to a different location than :php:`PATH_site`, have been moved from
the :file:`<web-dir>/typo3conf` to the :file:`config` directory.

The following files are affected:

- :file:`LocalConfiguration.php`
- :file:`AdditionalConfiguration.php`
- :file:`AdditionalFactoryConfiguration.php`

The first access to the TYPO3 frontend or backend will redirect to the Install Tool which
automatically creates the :file:`config` directory and moves all of the affected files currently
present in :file:`<web-dir>/typo3conf` to :file:`config`. Afterwards backend and frontend are
accessible as usual.

Using the environment variable `TYPO3_PATH_APP` allows for storing relevant code like configuration
outside of the document root / Composer `<web-dir>`. This effectively hardens a TYPO3 instance,
as less files are accessible in public. All new installations in Composer Mode use this setup by
default. Installations in Classic Mode (without Composer setup) will continue to use the previous
setup since it cannot be ensured that they have access outside of the document root.

If you have a setup with a :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern']` set, please
go to the install tool first. The migration is done automatically and you can just go to the backend
afterwards. If you have not set your trustedHostsPattern, you will be redirected automatically and
you don't have to do anything.

.. index:: LocalConfiguration
