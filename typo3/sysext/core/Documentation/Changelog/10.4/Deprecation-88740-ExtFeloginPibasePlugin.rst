.. include:: /Includes.rst.txt

=============================================================
Deprecation: #88740 - ext:felogin pibase plugin related hooks
=============================================================

See :issue:`88740`

Description
===========

All legacy hooks related to the pibase plugin of EXT:felogin have been disabled
and will be removed in TYPO3v11.


Impact
======

Extensions that use any of the following hooks will trigger a PHP :php:`E_USER_DEPRECATED` error:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['beforeRedirect']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['postProcContent']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['password_changed']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['forgotPasswordMail']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_error']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['logout_confirmed']`


Affected Installations
======================

All instances using extensions that use any of the previously named hooks.

Migration
=========

All of the hooks have been replaced by equivalent PSR-14 events.

+-----------------------------------------------------------------------------------+----------------------------------------------------------------------+
| Pibase hook                                                                       | PSR-14 event                                                         |
+===================================================================================+======================================================================+
|:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['beforeRedirect']`         | :php:`\TYPO3\CMS\FrontendLogin\Event\BeforeRedirectEvent`            |
+-----------------------------------------------------------------------------------+----------------------------------------------------------------------+
|:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['postProcContent']`        | :php:`\TYPO3\CMS\FrontendLogin\Event\ModifyLoginFormViewEvent`       |
+-----------------------------------------------------------------------------------+----------------------------------------------------------------------+
|:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['forgotPasswordMail']`     | :php:`\TYPO3\CMS\FrontendLogin\Event\SendRecoveryEmailEvent`         |
+-----------------------------------------------------------------------------------+----------------------------------------------------------------------+
|:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['password_changed']`       | :php:`\TYPO3\CMS\FrontendLogin\Event\PasswordChangeEvent`            |
+-----------------------------------------------------------------------------------+----------------------------------------------------------------------+
|:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed']`        | :php:`\TYPO3\CMS\FrontendLogin\Event\LoginConfirmedEvent`            |
+-----------------------------------------------------------------------------------+----------------------------------------------------------------------+
|:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_error']`            | :php:`\TYPO3\CMS\FrontendLogin\Event\LoginErrorOccurredEvent`        |
+-----------------------------------------------------------------------------------+----------------------------------------------------------------------+
|:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['logout_confirmed']`       | :php:`\TYPO3\CMS\FrontendLogin\Event\LogoutConfirmedEvent`           |
+-----------------------------------------------------------------------------------+----------------------------------------------------------------------+
|:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs']` | :php:`\TYPO3\CMS\FrontendLogin\Event\ModifyLoginFormViewEvent`       |
+-----------------------------------------------------------------------------------+----------------------------------------------------------------------+

.. index:: Frontend, FullyScanned, ext:felogin
