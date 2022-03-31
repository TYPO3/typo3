.. include:: /Includes.rst.txt

=========================================================================================
Feature: #80374 - Frontend Login configuration now available through TypoScript constants
=========================================================================================

See :issue:`80374`

Description
===========

The most common configuration options for the "Frontend Login" configuration are
now available as TypoScript constants, and moved to a new section "Frontend Login"
in the constant editor.

Storage
-------

styles.content.loginform.pid
   Storage Folder: Define the Storage Folder with the Website User Records,
   using a comma separated list or single value

styles.content.loginform.recursive
   Recursive: If set, also any subfolders of the storagePid will be used

Template
--------

styles.content.loginform.templateFile
   Login template: Enter the path for the HTML template to be used

styles.content.loginform.feloginBaseURL
   BaseURL for generated links: Base url if something other than the system
   base URL is needed

styles.content.loginform.dateFormat
   Date format: Format for the link is valid until message (forget password email)

Features
--------

styles.content.loginform.showForgotPasswordLink
   Display Password Recovery Link: If set, the section in the template to
   display the link to the forget password dialogue is visible.

styles.content.loginform.showPermaLogin
   Display Remember Login Option: If set, the section in the template to
   display the option to remember the login (with a cookie) is visible.

styles.content.loginform.showLogoutFormAfterLogin
   Disable redirect after successful login, but display logout-form: If set,
   the logout form will be displayed immediately after successful login.

E-Mail
------

styles.content.loginform.emailFrom
   E-Mail Sender Address: E-Mail address used as sender of the change password emails

styles.content.loginform.emailFromName
   E-Mail Sender Name: Name used as sender of the change password emails

styles.content.loginform.replyToEmail
   Reply To E-Mail Address: Reply-to address used in the change password emails

Redirects
---------

styles.content.loginform.redirectMode
   Redirect Mode: Comma separated list of redirect modes. Possible values: groupLogin,
   userLogin, login, getpost, referer, refererDomains, loginError, logout

styles.content.loginform.redirectFirstMethod
   Use First Supported Mode from Selection: If set the first method from redirectMode
   which is possible will be used

styles.content.loginform.redirectPageLogin
   After Successful Login Redirect to Page: Page id to redirect to after Login

styles.content.loginform.redirectPageLoginError
   After Failed Login Redirect to Page: Page id to redirect to after Login Error

styles.content.loginform.redirectPageLogout
   After Logout Redirect to Page: Page id to redirect to after Logout

styles.content.loginform.redirectDisable
   Disable Redirect: If set redirecting is disabled

Security
--------

styles.content.loginform.forgotLinkHashValidTime
   Time in hours how long the link for forget password is valid: How many
   hours the link for forget password is valid

styles.content.loginform.newPasswordMinLength
   Minimum amount of characters, when setting a new password: Minimum length
   of the new password a user sets

styles.content.loginform.domains
   Allowed Referrer-Redirect-Domains: Comma separated list of domains which
   are allowed for the referrer redirect mode

styles.content.loginform.exposeNonexistentUserInForgotPasswordDialog
   Expose existing users: Expose the information on whether or not the account
   for which a new password was requested exists. By default, that information
   is not disclosed for privacy reasons.

Impact
======

Frontend Login configuration is now always added first and not depending anymore
and not depending anymore on the configuration of the TypoScript template.
This allows reliable configuration since the configuration is not a moving target.

.. index:: TypoScript, Frontend
