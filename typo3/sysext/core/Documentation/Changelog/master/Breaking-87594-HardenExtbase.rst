.. include:: ../../Includes.txt

=================================
Breaking: #87594 - Harden extbase
=================================

See :issue:`87594`

Description
===========

While hardening Extbase classes, method signatures changed due to an enforced strict type mode and introduced type hints for scalars. The change of signatures is considered breaking for the following methods of the following interfaces and their implementations and for the following classes and their derivatives:

- :php:`\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface::getUid`
- :php:`\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface::setPid`
- :php:`\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface::getPid`
- :php:`\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface::_isNew`
- :php:`\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface::_setProperty`
- :php:`\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface::_getProperty`
- :php:`\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface::_getProperties`
- :php:`\TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface::_getCleanProperty`
- :php:`\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject::getUid`
- :php:`\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject::setPid`
- :php:`\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject::getPid`
- :php:`\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject::_isNew`
- :php:`\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject::_setProperty`
- :php:`\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject::_getProperty`
- :php:`\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject::_getProperties`
- :php:`\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject::_getCleanProperty`
- :php:`\TYPO3\CMS\Extbase\Service\ImageService::applyProcessingInstructions`
- :php:`\TYPO3\CMS\Extbase\Service\ImageService::getImageUri`
- :php:`\TYPO3\CMS\Extbase\Service\ImageService::getImage`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverterInterface::getSupportedSourceTypes()`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverterInterface::getSupportedTargetType()`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverterInterface::getTargetTypeForSource()`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverterInterface::getPriority()`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverterInterface::canConvertFrom()`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverterInterface::getSourceChildPropertiesToBeConverted()`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverterInterface::getTypeOfChildProperty()`
- :php:`\TYPO3\CMS\Extbase\Property\TypeConverterInterface::convertFrom()`
- :php:`\TYPO3\CMS\Extbase\Error\Message::__construct`
- :php:`\TYPO3\CMS\Extbase\Error\Message::getMessage`
- :php:`\TYPO3\CMS\Extbase\Error\Message::getCode`
- :php:`\TYPO3\CMS\Extbase\Error\Message::getArguments`
- :php:`\TYPO3\CMS\Extbase\Error\Message::getTitle`
- :php:`\TYPO3\CMS\Extbase\Error\Message::render`
- :php:`\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::getContentObject`
- :php:`\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::getConfiguration`
- :php:`\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::isFeatureEnabled`


Impact
======

PHP might throw a fatal error if the method signature(s) of your implementations/derivatives aren't compatible with the interface(s) and/or parent class(es).


Affected Installations
======================

- All installations that use classes, that implement mentioned interfaces and their methods.
- All installations that use classes, that inherit mentioned classes and overwrite their methods.


Migration
=========

Methods need to be adjusted to be compatible with the parent class and/or interface signature.

.. index:: PHP-API, NotScanned
