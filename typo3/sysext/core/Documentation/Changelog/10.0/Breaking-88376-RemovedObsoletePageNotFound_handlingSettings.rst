.. include:: /Includes.rst.txt

====================================================================
Breaking: #88376 - Removed obsolete "pageNotFound_handling" settings
====================================================================

See :issue:`88376`

Description
===========

The following global TYPO3 settings, usually set within :file:`LocalConfiguration.php` have been removed:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling_statheader']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['pageNotFound_handling_accessdeniedheader']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['pageUnavailable_handling_statheader']`

These settings are effectively replaced by the error handling of the newly introduced Site Handling
which is more flexible and robust, and is used instead of these options when Site Handling was
enabled in TYPO3 v9. For TYPO3 v10 Site Handling is a requirement, making these options useless.


Impact
======

Setting any of the options will have no effect any more. Executing the Silent Upgrade Wizard
will remove the settings automatically.


Affected Installations
======================

Any TYPO3 installations having these settings overridden in :file:`LocalConfiguration.php`
file of an installation.


Migration
=========

Access the install tool to automatically update the :file:`LocalConfiguration.php` file and remove the
settings.

Ensure to set up Site Handling with proper error handlers. Avoid accessing these settings but
rather use the available :php:`TYPO3\CMS\Frontend\Controller\ErrorController` class, when trying to manually trigger a 404/500
in the Frontend (e.g. custom plugin) instead.

.. index:: Frontend, LocalConfiguration, PartiallyScanned, ext:frontend
