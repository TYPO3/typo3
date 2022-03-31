.. include:: /Includes.rst.txt

============================================
Feature: #92628 - Add Alt-Text To Login Logo
============================================

See :issue:`92628`

Description
===========

The configuration of the extension "backend" has now the possibility to
provide an alt-text for a custom login logo.

In the module "Admin tools > Settings" go to card "Extension Configuration"
and open the dialog. Select extension "backend" and fill in the field
"Logo Alt-Text" on the "Login" tab. You can also set :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['backend']['loginLogoAlt']`.


Impact
======

Setting the alt-text enhances the accessibility of the login page.

.. index:: Backend, ext:backend
