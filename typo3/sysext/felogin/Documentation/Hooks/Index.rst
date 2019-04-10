.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _hooks:

Hooks
-----

The following hooks are available:

$GLOBALS['TYPO3\_CONF\_VARS']['EXTCONF']['felogin']['beforeRedirect']
  Receives loginType and redirectUrl as parameters, allows to change
  the redirectUrl

$GLOBALS['TYPO3\_CONF\_VARS']['EXTCONF']['felogin']['postProcContent']
  Postprocessing of the output just before it is returned

$GLOBALS['TYPO3\_CONF\_VARS']['EXTCONF']['felogin']['login\_confirmed']
  Hook for general actions after login has been confirmed

$GLOBALS['TYPO3\_CONF\_VARS']['EXTCONF']['felogin']['login\_error']
  Hook for general actions on login error

$GLOBALS['TYPO3\_CONF\_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs']
  This hook can be used to set hidden fields and onsubmit-scripts

$GLOBALS['TYPO3\_CONF\_VARS']['EXTCONF']['felogin']['logout\_confirmed']
  Hook for general actions after logout has been confirmed

$GLOBALS['TYPO3\_CONF\_VARS']['EXTCONF']['felogin']['forgotPasswordMail ']
  Hook to change the contents of the forgot password mail

$GLOBALS['TYPO3\_CONF\_VARS']['EXTCONF']['felogin']['password\_changed']
  Receives an array containing the user and the newPassword.
