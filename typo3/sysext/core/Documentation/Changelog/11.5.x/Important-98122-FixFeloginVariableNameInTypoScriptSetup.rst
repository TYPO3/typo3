.. include:: /Includes.rst.txt

.. _important-98122-1671636081:

=================================================================
Important: #98122 - Fix felogin variable name in TypoScript setup
=================================================================

See :issue:`98122`

Description
===========

The showForgotPasswordLink setting was renamed to showForgotPassword during the
refactoring to fluid templates. It is now also renamed in the TypoScript setup.

The TypoScript constant name is not changed to keep compatibility.

Migration
=========

Use :typoscript:`plugin.tx_felogin_login.settings.showForgotPassword` instead of
:typoscript:`plugin.tx_felogin_login.settings.showForgotPasswordLink` in TypoScript setup.
And :typoscript:`styles.content.loginform.showForgotPassword` instead of
:typoscript:`styles.content.loginform.showForgotPasswordLink` in TypoScript constants.

.. index:: TypoScript, ext:felogin
