================================================================================
Feature: #68757 - Provide untouched newPassword in felogin password_changed hook
================================================================================

Description
===========

The new 'newPasswordUnencrypted' parameter in the ext:felogin password_changed hook won't be salted if ext:saltedpaswords is enabled. It is now possible to work with the real new password.