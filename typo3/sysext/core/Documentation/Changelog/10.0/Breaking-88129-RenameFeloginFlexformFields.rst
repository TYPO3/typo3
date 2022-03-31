.. include:: /Includes.rst.txt

==================================================
Breaking: #88129 - Renamed felogin flexform fields
==================================================

See :issue:`88129`

Description
===========

In preparation to :issue:`84262` the felogin flexform field definition has been changed
and all field names are now prefixed with `settings.`. This has been done to easily access all
of the flexform values in the later extbase controller via :php:`$this->settings['foo']` and also in
the fluid templates via :html:`{settings.foo}`.


Impact
======

Any PageTsConfig that overrides felogin flexform fields will be ignored.


Affected Installations
======================

All installations with a felogin plugin need to migrate their flexform database values.
PageTsConfig that overrides the flexform needs to be adjusted.


Migration
=========

An update wizard is provided to easily update all used felogin plugins. To migrate the flexform values, execute
`Migrate felogin plugins to use prefixed flexform keys`.

All PageTsConfig that overrides felogin flexform fields e.g. :typoscript:`TCEFORM.tt_content.pi_flexform.login.sDEF.showForgotPassword.disabled = 1`
needs to add the `settings.` prefix to the keys.
Note the escaping backslash! :typoscript:`TCEFORM.tt_content.pi_flexform.login.sDEF.settings\.showForgotPassword.disabled = 1`.

.. index:: FlexForm, NotScanned, ext:felogin
