.. include:: ../../Includes.txt

=================================================
Deprecation: #82725 - Deprecate ConfigurationForm
=================================================

See :issue:`82725`

Description
===========

Class :php:`TYPO3\CMS\Core\TypoScript\ConfigurationForm` has been deprecated and should
not be used any longer.


Impact
======

Extending or instantiating this class will throw a deprecation warning.


Affected Installations
======================

Instance with extensions using this class.


Migration
=========

Class :php:`ConfigurationForm` was used to parse the ext_conf_template.txt file of extensions. 
The parser has been integrated at a different place in the core. The
class is mostly core internal and extensions should not have needed to parse that syntax directly.
There is no direct substitution for this functionality usable by extensions in the core.

.. index:: Backend, PHP-API, FullyScanned