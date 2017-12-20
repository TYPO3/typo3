.. include:: ../../Includes.txt

=============================================================
Important: #81474 - Combine modules "about" & "about modules"
=============================================================

See :issue:`81474`

Description
===========

The info screen and backend module "About modules" has been merged into "About TYPO3 CMS" into one module.

This now acts as the default module after login.

An update wizard is in place to migrate to the new module name if the "About modules" module was selected as a
default module after login for backend users.

.. index:: Backend, ext:about
