.. include:: /Includes.rst.txt

===============================================
Deprecation: #81600 - Unused Extbase Exceptions
===============================================

See :issue:`81600`

Description
===========

Extbase ships with a lot of PHP Exception classes which are not used (partially anymore) due to
refactorings or backports 8 years ago - they are never thrown within TYPO3 / Extbase itself.

These PHP classes have been removed.


Impact
======

Using these exception classes will not work anymore in TYPO3 v10.


Affected Installations
======================

Any TYPO3 extbase extension using these extraordinary exceptions in their own code.


Migration
=========

PHP class aliases are in place, so all code will still work throughout TYPO3 v9, but extension authors
should migrate to other exceptions.

Use :php:`TYPO3\CMS\Extbase\Configuration\Exception` instead of

* :php:`TYPO3\CMS\Extbase\Configuration\Exception\ContainerIsLockedException`
* :php:`TYPO3\CMS\Extbase\Configuration\Exception\NoSuchFileException`
* :php:`TYPO3\CMS\Extbase\Configuration\Exception\NoSuchOptionException`

Use :php:`TYPO3\CMS\Extbase\Exception` instead of

* :php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidMarkerException`
* :php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidViewHelperException`

Use :php:`TYPO3\CMS\Extbase\Mvc\Exception` instead of

* :php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidRequestTypeException`
* :php:`TYPO3\CMS\Extbase\Mvc\Exception\RequiredArgumentMissingException`
* :php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidCommandIdentifierException`

Use :php:`TYPO3\CMS\Core\Cache\Exception\InvalidCacheException` instead of

* :php:`TYPO3\CMS\Extbase\Object\Container\Exception\CannotInitializeCacheException`

Use :php:`TYPO3\CMS\Extbase\Object\Exception` instead of

* :php:`TYPO3\CMS\Extbase\Object\Container\Exception\TooManyRecursionLevelsException`
* :php:`TYPO3\CMS\Extbase\Object\Exception\WrongScopeException`
* :php:`TYPO3\CMS\Extbase\Object\InvalidClassException`
* :php:`TYPO3\CMS\Extbase\Object\InvalidObjectConfigurationException`
* :php:`TYPO3\CMS\Extbase\Object\InvalidObjectException`
* :php:`TYPO3\CMS\Extbase\Object\ObjectAlreadyRegisteredException`
* :php:`TYPO3\CMS\Extbase\Object\UnknownClassException`
* :php:`TYPO3\CMS\Extbase\Object\UnknownInterfaceException`
* :php:`TYPO3\CMS\Extbase\Object\UnresolvedDependenciesException`

Use :php:`TYPO3\CMS\Extbase\Persistence\Generic\Exception` instead of

* :php:`TYPO3\CMS\Extbase\Persistence\Generic\Exception\CleanStateNotMemorizedException`
* :php:`TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidPropertyTypeException`
* :php:`TYPO3\CMS\Extbase\Persistence\Generic\Exception\MissingBackendException`

Use :php:`TYPO3\CMS\Extbase\Property\Exception` instead of

* :php:`TYPO3\CMS\Extbase\Property\Exception\FormatNotSupportedException`
* :php:`TYPO3\CMS\Extbase\Property\Exception\InvalidFormatException`
* :php:`TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyException`

Use :php:`TYPO3\CMS\Extbase\Reflection\Exception` instead of

* :php:`TYPO3\CMS\Extbase\Reflection\Exception\InvalidPropertyTypeException`

Use :php:`TYPO3\CMS\Extbase\Security\Exception` instead of

* :php:`TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForRequestHashGenerationException`
* :php:`TYPO3\CMS\Extbase\Security\Exception\SyntacticallyWrongRequestHashException`
* :php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidUriPatternException`

Use :php:`TYPO3\CMS\Extbase\Security\Exception\InvalidHashException` instead of

* :php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidOrNoRequestHashException`

Use :php:`TYPO3\CMS\Extbase\Validation\Exception` instead of

* :php:`TYPO3\CMS\Extbase\Validation\Exception\InvalidSubjectException`
* :php:`TYPO3\CMS\Extbase\Validation\Exception\NoValidatorFoundException`

Use :php:`TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException` instead of

* :php:`TYPO3\CMS\Extbase\Mvc\Exception\InvalidTemplateResourceException`

.. index:: PHP-API, FullyScanned, ext:extbase
