
.. include:: /Includes.rst.txt

================================================================================
Feature: #68757 - Provide untouched newPassword in felogin password_changed hook
================================================================================

See :issue:`68757`

Description
===========

The new parameter `newPasswordUnencrypted`  in the EXT:felogin password_changed
hook won't be salted if EXT:saltedpaswords is enabled. It is now possible to
work with the real new password.


.. index:: PHP-API, ext:saltedpasswords, ext:felogin
