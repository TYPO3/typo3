<?php
namespace {
    die('Access denied');
}

// Configuration
namespace TYPO3\CMS\Extbase\Configuration\Exception {
    class ContainerIsLockedException extends \TYPO3\CMS\Extbase\Configuration\Exception
    {
    }
    class NoSuchFileException extends \TYPO3\CMS\Extbase\Configuration\Exception
    {
    }
    class NoSuchOptionException extends \TYPO3\CMS\Extbase\Configuration\Exception
    {
    }
}

namespace TYPO3\CMS\Extbase\Mvc\Exception {
    class InvalidMarkerException extends \TYPO3\CMS\Extbase\Exception
    {
    }
    class RequiredArgumentMissingException extends \TYPO3\CMS\Extbase\Mvc\Exception
    {
    }
    class InvalidRequestTypeException extends \TYPO3\CMS\Extbase\Mvc\Exception
    {
    }
    class InvalidCommandIdentifierException extends \TYPO3\CMS\Extbase\Mvc\Exception
    {
    }
    class InvalidOrNoRequestHashException extends \TYPO3\CMS\Extbase\Security\Exception\InvalidHashException
    {
    }
    class InvalidUriPatternException extends \TYPO3\CMS\Extbase\Security\Exception
    {
    }
    class InvalidViewHelperException extends \TYPO3\CMS\Extbase\Exception
    {
    }
    class InvalidTemplateResourceException extends \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
    {
    }
}

namespace TYPO3\CMS\Extbase\Object\Container\Exception {
    class CannotInitializeCacheException extends \TYPO3\CMS\Core\Cache\Exception\InvalidCacheException
    {
    }
    class TooManyRecursionLevelsException extends \TYPO3\CMS\Extbase\Object\Exception
    {
    }
}

namespace TYPO3\CMS\Extbase\Object\Exception {
    class WrongScopeException extends \TYPO3\CMS\Extbase\Object\Exception
    {
    }
}

namespace TYPO3\CMS\Extbase\Object {
    class InvalidClassException extends \TYPO3\CMS\Extbase\Object\Exception
    {
    }
    class InvalidObjectConfigurationException extends \TYPO3\CMS\Extbase\Object\Exception
    {
    }
    class InvalidObjectException extends \TYPO3\CMS\Extbase\Object\Exception
    {
    }
    class ObjectAlreadyRegisteredException extends \TYPO3\CMS\Extbase\Object\Exception
    {
    }
    class UnknownClassException extends \TYPO3\CMS\Extbase\Object\Exception
    {
    }
    class UnknownInterfaceException extends \TYPO3\CMS\Extbase\Object\Exception
    {
    }
    class UnresolvedDependenciesException extends \TYPO3\CMS\Extbase\Object\Exception
    {
    }
}

namespace TYPO3\CMS\Extbase\Persistence\Generic\Exception {
    class CleanStateNotMemorizedException extends \TYPO3\CMS\Extbase\Persistence\Generic\Exception
    {
    }
    class InvalidPropertyTypeException extends \TYPO3\CMS\Extbase\Persistence\Generic\Exception
    {
    }
    class MissingBackendException extends \TYPO3\CMS\Extbase\Persistence\Generic\Exception
    {
    }
}

namespace TYPO3\CMS\Extbase\Property\Exception {
    class FormatNotSupportedException extends \TYPO3\CMS\Extbase\Property\Exception
    {
    }
    class InvalidFormatException extends \TYPO3\CMS\Extbase\Property\Exception
    {
    }
    class InvalidPropertyException extends \TYPO3\CMS\Extbase\Property\Exception
    {
    }
}

namespace TYPO3\CMS\Extbase\Reflection\Exception {
    class InvalidPropertyTypeException extends \TYPO3\CMS\Extbase\Reflection\Exception
    {
    }
}

namespace TYPO3\CMS\Extbase\Security\Exception {
    class InvalidArgumentForRequestHashGenerationException extends \TYPO3\CMS\Extbase\Security\Exception
    {
    }
    class SyntacticallyWrongRequestHashException extends \TYPO3\CMS\Extbase\Security\Exception
    {
    }
}

namespace TYPO3\CMS\Extbase\Validation\Exception {
    class InvalidSubjectException extends \TYPO3\CMS\Extbase\Validation\Exception
    {
    }
    class NoValidatorFoundException extends \TYPO3\CMS\Extbase\Validation\Exception
    {
    }
}
