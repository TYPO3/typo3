.. include:: /Includes.rst.txt

.. _deprecation-100307-1679924603:

====================================================================
Deprecation: #100307 - Various hooks related to authentication users
====================================================================

See :issue:`100307`

Description
===========

The following hooks have been marked as deprecated:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_pre_processing']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['logoff_post_processing']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['backendUserLogin']`

They can be used to add notifications or actions to a TYPO3 installation
after a frontend user or a backend user has actively logged
in or logged out.

Impact
======

If one of the hooks is registered in a TYPO3 installation,
a PHP :php:`E_USER_DEPRECATED` error is triggered when a user logs
in or logs out.


Affected installations
======================

TYPO3 installations with custom extensions using one of these hooks.

The extension scanner detects any usage of the hooks.


Migration
=========

Migrate to the newly introduced PSR-14 events:

* :php:`\TYPO3\CMS\Core\Authentication\Event\BeforeUserLogoutEvent`
* :php:`\TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedOutEvent`
* :php:`\TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedInEvent`

..  seealso::
    :ref:`feature-100307-1679924551`

.. index:: Backend, Frontend, PHP-API, FullyScanned, ext:core
