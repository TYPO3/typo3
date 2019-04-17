<?php
namespace {
    die('Access denied');
}

namespace TYPO3\CMS\Lang {
    class LanguageService extends \TYPO3\CMS\Core\Localization\LanguageService
    {
    }
}

namespace TYPO3\CMS\ContextHelp\Controller {
    class ContextHelpAjaxController extends \TYPO3\CMS\Backend\Controller\ContextHelpAjaxController
    {
    }
}

namespace TYPO3\CMS\Sv {
    class AbstractAuthenticationService extends \TYPO3\CMS\Core\Authentication\AbstractAuthenticationService
    {
    }
    class AuthenticationService extends \TYPO3\CMS\Core\Authentication\AuthenticationService
    {
    }
}

namespace TYPO3\CMS\Saltedpasswords {
    class SaltedPasswordService extends \TYPO3\CMS\Core\Crypto\PasswordHashing\SaltedPasswordService
    {
    }
}

namespace TYPO3\CMS\Saltedpasswords\Exception {
    class InvalidSaltException extends \TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException
    {
    }
}

namespace TYPO3\CMS\Saltedpasswords\Salt {
    abstract class AbstractSalt extends \TYPO3\CMS\Core\Crypto\PasswordHashing\AbstractComposedSalt
    {
    }
    abstract class AbstractComposedSalt extends \TYPO3\CMS\Core\Crypto\PasswordHashing\AbstractComposedSalt
    {
    }
    class Argon2iSalt extends \TYPO3\CMS\Core\Crypto\PasswordHashing\Argon2iPasswordHash
    {
    }
    class BcryptSalt extends \TYPO3\CMS\Core\Crypto\PasswordHashing\BcryptPasswordHash
    {
    }
    class BlowfishSalt extends \TYPO3\CMS\Core\Crypto\PasswordHashing\BlowfishPasswordHash
    {
    }
    interface ComposedSaltInterface extends \TYPO3\CMS\Core\Crypto\PasswordHashing\ComposedPasswordHashInterface
    {
    }
    class Md5Salt extends \TYPO3\CMS\Core\Crypto\PasswordHashing\Md5PasswordHash
    {
    }
    class SaltFactory extends \TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory
    {
    }
    interface SaltInterface extends \TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashInterface
    {
    }
    class Pbkdf2Salt extends \TYPO3\CMS\Core\Crypto\PasswordHashing\Pbkdf2PasswordHash
    {
    }
    class PhpassSalt extends \TYPO3\CMS\Core\Crypto\PasswordHashing\PhpassPasswordHash
    {
    }
}

namespace TYPO3\CMS\Saltedpasswords\Utility {
    class ExensionManagerConfigurationUtility extends \TYPO3\CMS\Core\Crypto\PasswordHashing\ExtensionManagerConfigurationUtility
    {
    }
    class SaltedPasswordsUtility extends \TYPO3\CMS\Core\Crypto\PasswordHashing\SaltedPasswordsUtility
    {
    }
}
