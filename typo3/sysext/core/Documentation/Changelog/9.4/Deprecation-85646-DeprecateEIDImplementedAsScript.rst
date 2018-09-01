.. include:: ../../Includes.txt

=========================================================
Deprecation: #85646 - Deprecate eID implemented as script
=========================================================

See :issue:`85646`

Description
===========

Calling a frontend eID as a direct script call has been marked as deprecated.

Setting a PHP eID include like this triggers PHP :php:`E_USER_DEPRECATED` error::

    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['myEid'] = 'EXT:myExt/Resources/Php/MyAjax.php';

This is not valid anymore. Instead, a class / method combination should be used::

    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['myEid'] = \MyVendor\MyExt\Controller\MyEidController::class . '::myMethod';

The main difference is that a script call does not execute code if calling :php:`require()` on
it directly anymore, but needs a proper registration including an entry method to be called.
This increases encapsulation and security.

Impact
======

eIDs which are registered with a direct script include trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

3rd party extensions which implement eIDs with a script to a file instead of
a class->method combination.


Migration
=========

Register eID with a class::method syntax like :php:`\TYPO3\CMS\Frontend\MyClass::myMethod` instead.

.. index:: Frontend, NotScanned
