.. include:: /Includes.rst.txt

=========================================================
Feature: #87072 - Added Configuration Options for Locking
=========================================================

See :issue:`87072`

Description
===========

With change `Feature: #47712 - New Locking API
<https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/7.2/Feature-47712-NewLockingAPI.html>`__ a new Locking API was introduced.
This API can be extended. It provides three locking strategies and an interface for adding your own locking strategy in an extension.
However, until now, the default behaviour could not be changed using only the TYPO3 core.

The introduction of new options makes some of the default properties of the locking API configurable:

* The priority of each locking strategy can be changed.
* The directory where the lock files are written can be configured.

Configuration example
---------------------

:file:`typo3conf/AdditionalConfiguration.php`:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'][\TYPO3\CMS\Core\Locking\FileLockStrategy::class]['priority'] = 10;
   // The directory specified here must exist und must be a subdirectory of `Environment::getProjectPath()`
   $GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'][\TYPO3\CMS\Core\Locking\FileLockStrategy::class]['lockFileDir'] = 'mylockdir';


This sets the priority of FileLockStrategy to 10, thus making it the locking strategy with the lowest priority, which
will be chosen last by the LockFactory.

The directory for storing the locks is changed to :file:`mylockdir`.

Impact
======

For administrators
------------------

Nothing changes by default. The default values are used for the Locking API, same as before this change.

If :file:`AdditionalConfiguration.php` is used to change Global Configuration settings for Locking API, and not used with care,
it can seriously compromise the stability of the system. As usual, when overriding Global Configuration with
:file:`LocalConfiguration.php` or :file:`AdditionalConfiguration.php`, great caution must be exercised.

Specifically, do the following:

* Test this on a test system first
* If you change the priorities, make sure your system fully supports the locking strategy which will be chosen by default.
* If you change the directory, make sure the directory exists and will always exist in the future.

For developers
--------------

If a locking strategy is added by an extension, the priority and possibly directory for storing locks should be made
configurable as well.

.. code-block:: php

   public static function getPriority()
   {
       return $GLOBALS['TYPO3_CONF_VARS']['SYS']['locking']['strategies'][self::class]['priority']
           ?? self::DEFAULT_PRIORITY;
   }

.. index:: ext:core
