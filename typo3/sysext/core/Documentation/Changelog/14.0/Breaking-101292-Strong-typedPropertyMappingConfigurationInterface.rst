..  include:: /Includes.rst.txt

..  _breaking-101292-1742899857:

======================================================================
Breaking: #101292 - Strong-typed PropertyMappingConfigurationInterface
======================================================================

See :issue:`101292`

Description
===========

Extbase's :php-short:`\TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface`
is now fully typed with native PHP types.

Impact
======

Existing implementations will no longer work without adjustment. According to
the Liskov Substitution Principle, all implementations must follow the updated
method signatures and type restrictions defined in the interface.

Affected installations
======================

TYPO3 installations with custom PHP code implementing a custom
:php-short:`\TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration` are
affected. Such cases are rare.

Migration
=========

Add the required native PHP types to all custom implementations of the
:php-short:`\TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface`
to fulfill the updated interface definition.

..  index:: PHP-API, NotScanned, ext:extbase
