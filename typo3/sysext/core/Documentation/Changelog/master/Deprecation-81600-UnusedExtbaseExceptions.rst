.. include:: ../../Includes.txt

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

Use TYPO3\CMS\Extbase\Configuration\Exception instead of
* TYPO3\CMS\Extbase\Configuration\Exception\ContainerIsLockedException
* TYPO3\CMS\Extbase\Configuration\Exception\NoSuchFileException
* TYPO3\CMS\Extbase\Configuration\Exception\NoSuchOptionException

Use TYPO3\CMS\Extbase\Exception instead of
* TYPO3\CMS\Extbase\Mvc\Exception\InvalidMarkerException
* TYPO3\CMS\Extbase\Mvc\Exception\InvalidViewHelperException

Use TYPO3\CMS\Extbase\Mvc\Exception instead of
* TYPO3\CMS\Extbase\Mvc\Exception\InvalidRequestTypeException
* TYPO3\CMS\Extbase\Mvc\Exception\RequiredArgumentMissingException
* TYPO3\CMS\Extbase\Mvc\Exception\InvalidCommandIdentifierException

Use TYPO3\CMS\Core\Cache\Exception\InvalidCacheException instead of
* TYPO3\CMS\Extbase\Object\Container\Exception\CannotInitializeCacheException

Use TYPO3\CMS\Extbase\Object\Exception instead of
* TYPO3\CMS\Extbase\Object\Container\Exception\TooManyRecursionLevelsException
* TYPO3\CMS\Extbase\Object\Exception\WrongScopeException
* TYPO3\CMS\Extbase\Object\InvalidClassException
* TYPO3\CMS\Extbase\Object\InvalidObjectConfigurationException
* TYPO3\CMS\Extbase\Object\InvalidObjectException
* TYPO3\CMS\Extbase\Object\ObjectAlreadyRegisteredException
* TYPO3\CMS\Extbase\Object\UnknownClassException
* TYPO3\CMS\Extbase\Object\UnknownInterfaceException
* TYPO3\CMS\Extbase\Object\UnresolvedDependenciesException

Use TYPO3\CMS\Extbase\Persistence\Generic\Exception instead of
* TYPO3\CMS\Extbase\Persistence\Generic\Exception\CleanStateNotMemorizedException
* TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidPropertyTypeException
* TYPO3\CMS\Extbase\Persistence\Generic\Exception\MissingBackendException

Use TYPO3\CMS\Extbase\Property\Exception instead of
* TYPO3\CMS\Extbase\Property\Exception\FormatNotSupportedException
* TYPO3\CMS\Extbase\Property\Exception\InvalidFormatException
* TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyException

Use TYPO3\CMS\Extbase\Reflection\Exception instead of
* TYPO3\CMS\Extbase\Reflection\Exception\InvalidPropertyTypeException

Use TYPO3\CMS\Extbase\Security\Exception instead of
* TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForRequestHashGenerationException
* TYPO3\CMS\Extbase\Security\Exception\SyntacticallyWrongRequestHashException
* TYPO3\CMS\Extbase\Mvc\Exception\InvalidUriPatternException

Use TYPO3\CMS\Extbase\Security\Exception\InvalidHashException instead of
* TYPO3\CMS\Extbase\Mvc\Exception\InvalidOrNoRequestHashException

Use TYPO3\CMS\Extbase\Validation\Exception instead of
* TYPO3\CMS\Extbase\Validation\Exception\InvalidSubjectException
* TYPO3\CMS\Extbase\Validation\Exception\NoValidatorFoundException

Use TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException instead of
* TYPO3\CMS\Extbase\Mvc\Exception\InvalidTemplateResourceException

.. index:: PHP-API