<?php
return [
    // TYPO3 v8 replacements
    'TYPO3\\CMS\\Extbase\\Service\\TypoScriptService' => \TYPO3\CMS\Core\TypoScript\TypoScriptService::class,

    // TYPO3 v9 replacements
    // Configuration
    'TYPO3\\CMS\\Extbase\\Configuration\\Exception\\ContainerIsLockedException' => \TYPO3\CMS\Extbase\Configuration\Exception::class,
    'TYPO3\\CMS\\Extbase\\Configuration\\Exception\\NoSuchFileException' => \TYPO3\CMS\Extbase\Configuration\Exception::class,
    'TYPO3\\CMS\\Extbase\\Configuration\\Exception\\NoSuchOptionException' => \TYPO3\CMS\Extbase\Configuration\Exception::class,

    // no proper fallback
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidMarkerException' => \TYPO3\CMS\Extbase\Exception::class,

    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidRequestTypeException' => \TYPO3\CMS\Extbase\Mvc\Exception::class,
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\RequiredArgumentMissingException' => \TYPO3\CMS\Extbase\Mvc\Exception::class,
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidCommandIdentifierException' => \TYPO3\CMS\Extbase\Mvc\Exception::class,

    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidOrNoRequestHashException' => \TYPO3\CMS\Extbase\Security\Exception\InvalidHashException::class,
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidUriPatternException' => \TYPO3\CMS\Extbase\Security\Exception::class,

    // Object Container
    'TYPO3\\CMS\\Extbase\\Object\\Container\\Exception\\CannotInitializeCacheException' => \TYPO3\CMS\Core\Cache\Exception\InvalidCacheException::class,
    'TYPO3\\CMS\\Extbase\\Object\\Container\\Exception\\TooManyRecursionLevelsException' => \TYPO3\CMS\Extbase\Object\Exception::class,

    // ObjectManager
    'TYPO3\\CMS\\Extbase\\Object\\Exception\WrongScopeException' => \TYPO3\CMS\Extbase\Object\Exception::class,
    'TYPO3\\CMS\\Extbase\\Object\\InvalidClassException' => \TYPO3\CMS\Extbase\Object\Exception::class,
    'TYPO3\\CMS\\Extbase\\Object\\InvalidObjectConfigurationException' => \TYPO3\CMS\Extbase\Object\Exception::class,
    'TYPO3\\CMS\\Extbase\\Object\\InvalidObjectException' => \TYPO3\CMS\Extbase\Object\Exception::class,
    'TYPO3\\CMS\\Extbase\\Object\\ObjectAlreadyRegisteredException' => \TYPO3\CMS\Extbase\Object\Exception::class,
    'TYPO3\\CMS\\Extbase\\Object\\UnknownClassException' => \TYPO3\CMS\Extbase\Object\Exception::class,
    'TYPO3\\CMS\\Extbase\\Object\\UnknownInterfaceException' => \TYPO3\CMS\Extbase\Object\Exception::class,
    'TYPO3\\CMS\\Extbase\\Object\\UnresolvedDependenciesException' => \TYPO3\CMS\Extbase\Object\Exception::class,

    // Persistence
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\CleanStateNotMemorizedException' => \TYPO3\CMS\Extbase\Persistence\Generic\Exception::class,
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\InvalidPropertyTypeException' => \TYPO3\CMS\Extbase\Persistence\Generic\Exception::class,
    'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Exception\\MissingBackendException' => \TYPO3\CMS\Extbase\Persistence\Generic\Exception::class,

    // Property
    'TYPO3\\CMS\\Extbase\\Property\\Exception\\FormatNotSupportedException' => \TYPO3\CMS\Extbase\Property\Exception::class,
    'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidFormatException' => \TYPO3\CMS\Extbase\Property\Exception::class,
    'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidPropertyException' => \TYPO3\CMS\Extbase\Property\Exception::class,

    // Reflection
    'TYPO3\\CMS\\Extbase\\Reflection\\Exception\\InvalidPropertyTypeException' => \TYPO3\CMS\Extbase\Reflection\Exception::class,

    // Security
    'TYPO3\\CMS\\Extbase\\Security\\Exception\\InvalidArgumentForRequestHashGenerationException' => \TYPO3\CMS\Extbase\Security\Exception::class,
    'TYPO3\\CMS\\Extbase\\Security\\Exception\\SyntacticallyWrongRequestHashException' => \TYPO3\CMS\Extbase\Security\Exception::class,

    // Validation
    'TYPO3\\CMS\\Extbase\\Validation\\Exception\\InvalidSubjectException' => \TYPO3\CMS\Extbase\Validation\Exception::class,
    'TYPO3\\CMS\\Extbase\\Validation\\Exception\\NoValidatorFoundException' => \TYPO3\CMS\Extbase\Validation\Exception::class,

    // Fluid
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidViewHelperException' => \TYPO3\CMS\Extbase\Exception::class,
    'TYPO3\\CMS\\Extbase\\Mvc\\Exception\\InvalidTemplateResourceException' => \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException::class,

    // Service
    'TYPO3\\CMS\\Extbase\\Service\\FlexFormService' => \TYPO3\CMS\Core\Service\FlexFormService::class,
];
