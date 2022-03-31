.. include:: /Includes.rst.txt

===========================================================
Important: #91496 - Changes to password reset functionality
===========================================================

See :issue:`91496`

Description
===========

For various reasons, administrators may disable the password reset functionality,
introduced in :issue:`89513`, by setting
:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset']= false`.
If disabled, TYPO3 Backend users are not able to initiate the
password reset process anymore.

This however previously also disabled the password reset cli command as well as
the reset password action in the backend user module. Since it is a valid
use case for administrators to disallow a password reset initiated by users on
the login screen, they might still need the possibility to do so, on their own.

Therefore, some things changed in the password reset implementation:

*  :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset']` now only
   affects the password reset functionality on the login screen
*  :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins']` is
   unchanged and affects all places
*  Initiating the password reset on CLI is now always enabled, while still
   taking the `passwordResetForAdmins` option into account
*  Initiating the password reset in the backend can now be disabled separately
   with a new user TSconfig option :typoscript:`options.passwordReset`

For compatibility reasons, the new user TSconfig option defaults to :php:`true`.
To completely disable the password reset in the backend for all users, you can
set the user TSconfig globally in your :php:`ext_localconf.php`:

.. code-block:: php

   // use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
   ExtensionManagementUtility::addUserTSConfig(
      'options.passwordReset = 0'
   );

If  required, this can of course still be overwritten on a per user basis
in the corresponding :guilabel:`TSconfig` field.

.. index:: Backend, CLI, TSConfig, ext:backend
