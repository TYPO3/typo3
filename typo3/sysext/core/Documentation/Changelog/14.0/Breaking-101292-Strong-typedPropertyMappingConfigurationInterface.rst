..  include:: /Includes.rst.txt

..  _breaking-101292-1742899857:

======================================================================
Breaking: #101292 - Strong-typed PropertyMappingConfigurationInterface
======================================================================

See :issue:`101292`

Description
===========

Extbase's :php:`PropertyMappingConfigurationInterface` is now fully typed with native
PHP types.


Impact
======

Existing implementations will fail to work, as per Liskov's Substitution Principle, implementations
need follow the contract restrictions.


Affected installations
======================

TYPO3 installations with custom PHP code implementing a custom PropertyMappingConfiguration, which is rather uncommon.


Migration
=========

Add the native PHP types in all custom implementations of the :php:`PropertyMappingConfigurationInterface` to fulfill the contract again.

..  index:: PHP-API, NotScanned, ext:extbase