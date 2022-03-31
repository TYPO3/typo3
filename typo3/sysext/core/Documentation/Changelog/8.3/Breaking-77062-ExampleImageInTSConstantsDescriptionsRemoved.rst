
.. include:: /Includes.rst.txt

=====================================================================
Breaking: #77062 - Example image in TS constants descriptions removed
=====================================================================

See :issue:`77062`

Description
===========

In previous TYPO3 versions it was possible to add help text and an help image to a certain category or
configuration option in the TypoScript Constant Editor of the TYPO3 Backend. This was previously done via an
additional Constant Editor option within the `TSConstantEditor` object.

The functionality has been removed without substitution.

Along with that change, the following PHP methods have been removed:

* :php:`ExtendedTemplateService::ext_getTSCE_config_image()`
* :php:`ConfigurationForm::ext_getTSCE_config_image()`

The following public properties have been removed:

* :php:`ExtendedTemplateService::$ext_localGfxPrefix`
* :php:`ExtendedTemplateService::$ext_localWebGfxPrefix`

Within :php:`ConfigurationForm::ext_initTSstyleConfig()` the second and third parameter have been removed.


Impact
======

Setting an option :typoscript:`TSConstantEditor.basic.image = EXT:sys_note/ext_icon.png` for a category or configuration option in TypoScript constants has no effect anymore.

Calling any of the removed methods will result in a fatal PHP error.

Using any of the removed properties will result in a PHP warning.

Calling :php:`ConfigurationForm::ext_initTSstyleConfig()` with the second or third parameter will result in a PHP warning.


Affected Installations
======================

Any TYPO3 installation with extended TypoScript constant editor configuration.


Migration
=========

Remove the affected TypoScript constant editor configuration code, and any reference to the removed PHP
methods and properties.

.. index:: TypoScript, PHP-API
