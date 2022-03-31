.. include:: /Includes.rst.txt

=================================================
Deprecation: #92628 - Login Logo without Alt-Text
=================================================

See :issue:`92628`

Description
===========

The configuration of the extension "backend" has now the possibility to
provide an alt-text for a custom login logo.

As an alt-text is needed for accessibility reasons, not setting an alt-text has been marked as
deprecated.


Impact
======

Not configuring an alt-text will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All instances that have defined a custom login logo are affected.


Migration
=========

Configure an alt-text for your custom login logo.

.. index:: Backend, NotScanned, ext:backend
