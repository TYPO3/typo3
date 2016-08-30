<?php
namespace TYPO3\CMS\Core\FormProtection;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Lang\LanguageService;

/**
 * This class creates and manages instances of the various form protection
 * classes.
 *
 * This class provides only static methods. It can not be instantiated.
 *
 * Usage for the back-end form protection:
 *
 * <pre>
 * $formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
 * </pre>
 *
 * Usage for the install tool form protection:
 *
 * <pre>
 * $formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
 * </pre>
 */
class FormProtectionFactory
{
    /**
     * created instances of form protections using the type as array key
     *
     * @var array<AbstracFormtProtection>
     */
    protected static $instances = [];

    /**
     * Private constructor to prevent instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Gets a form protection instance for the requested type or class.
     *
     * If there already is an existing instance of the requested $classNameOrType, the
     * existing instance will be returned. If no $classNameOrType is provided, the factory
     * detects the scope and returns the appropriate form protection object.
     *
     * @param string $classNameOrType Name of a form protection class, or one
     *                                of the pre-defined form protection types:
     *                                frontend, backend, installtool
     * @return \TYPO3\CMS\Core\FormProtection\AbstractFormProtection the requested instance
     */
    public static function get($classNameOrType = 'default')
    {
        if (isset(self::$instances[$classNameOrType])) {
            return self::$instances[$classNameOrType];
        }
        if ($classNameOrType === 'default' || $classNameOrType === 'installtool' || $classNameOrType === 'frontend' || $classNameOrType === 'backend') {
            $classNameAndConstructorArguments = self::getClassNameAndConstructorArgumentsByType($classNameOrType);
        } else {
            $classNameAndConstructorArguments = func_get_args();
        }
        self::$instances[$classNameOrType] = self::createInstance($classNameAndConstructorArguments);
        return self::$instances[$classNameOrType];
    }

    /**
     * Returns the class name and parameters depending on the given type.
     * If the type cannot be used currently, protection is disabled.
     *
     * @param string $type Valid types: default, installtool, frontend, backend. "default" makes an autodection on the current state
     * @return array Array of arguments
     */
    protected static function getClassNameAndConstructorArgumentsByType($type)
    {
        if (self::isInstallToolSession() && ($type === 'default' || $type === 'installtool')) {
            $classNameAndConstructorArguments = [
                InstallToolFormProtection::class
            ];
        } elseif (self::isFrontendSession() && ($type === 'default' || $type === 'frontend')) {
            $classNameAndConstructorArguments = [
                FrontendFormProtection::class,
                $GLOBALS['TSFE']->fe_user
            ];
        } elseif (self::isBackendSession() && ($type === 'default' || $type === 'backend')) {
            $classNameAndConstructorArguments = [
                BackendFormProtection::class,
                $GLOBALS['BE_USER'],
                GeneralUtility::makeInstance(Registry::class),
                self::getMessageClosure(
                    $GLOBALS['LANG'],
                    GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier(),
                    (bool)(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX)
                )
            ];
        } else {
            // failed to use preferred type, disable form protection
            $classNameAndConstructorArguments = [
                DisabledFormProtection::class
            ];
        }
        return $classNameAndConstructorArguments;
    }

    /**
     * Check if we are in the install tool
     *
     * @return bool
     */
    protected static function isInstallToolSession()
    {
        return defined('TYPO3_enterInstallScript') && TYPO3_enterInstallScript;
    }

    /**
     * Checks if a user is logged in and the session is active.
     *
     * @return bool
     */
    protected static function isBackendSession()
    {
        return isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER'] instanceof BackendUserAuthentication && isset($GLOBALS['BE_USER']->user['uid']);
    }

    /**
     * Checks if a frontend user is logged in and the session is active.
     *
     * @return bool
     */
    protected static function isFrontendSession()
    {
        return TYPO3_MODE === 'FE' && is_object($GLOBALS['TSFE']) && $GLOBALS['TSFE']->fe_user instanceof FrontendUserAuthentication && isset($GLOBALS['TSFE']->fe_user->user['uid']);
    }

    /**
     * @param LanguageService $languageService
     * @param FlashMessageQueue $messageQueue
     * @param bool $isAjaxCall
     * @internal Only public to be used in tests
     * @return \Closure
     */
    public static function getMessageClosure(LanguageService $languageService, FlashMessageQueue $messageQueue, $isAjaxCall)
    {
        return function () use ($languageService, $messageQueue, $isAjaxCall) {
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $languageService->sL('LLL:EXT:lang/locallang_core.xlf:error.formProtection.tokenInvalid'),
                '',
                FlashMessage::ERROR,
                !$isAjaxCall
            );
            $messageQueue->enqueue($flashMessage);
        };
    }

    /**
     * Creates an instance for the requested class $className
     * and stores it internally.
     *
     * @param array $classNameAndConstructorArguments
     * @throws \InvalidArgumentException
     * @return AbstractFormProtection
     */
    protected static function createInstance(array $classNameAndConstructorArguments)
    {
        $className = $classNameAndConstructorArguments[0];
        if (!class_exists($className)) {
            throw new \InvalidArgumentException('$className must be the name of an existing class, but ' . 'actually was "' . $className . '".', 1285352962);
        }
        $instance = call_user_func_array([GeneralUtility::class, 'makeInstance'], $classNameAndConstructorArguments);
        if (!$instance instanceof AbstractFormProtection) {
            throw new \InvalidArgumentException('$className must be a subclass of ' . AbstractFormProtection::class . ', but actually was "' . $className . '".', 1285353026);
        }
        return $instance;
    }

    /**
     * Sets the instance that will be returned by get() for a specific class
     * name.
     *
     * Note: This function is intended for testing purposes only.
     *
     * @access private
     * @param string $classNameOrType
     * @param AbstractFormProtection $instance
     * @return void
     */
    public static function set($classNameOrType, AbstractFormProtection $instance)
    {
        self::$instances[$classNameOrType] = $instance;
    }

    /**
     * Purges all existing instances.
     *
     * This function is particularly useful when cleaning up in unit testing.
     *
     * @return void
     */
    public static function purgeInstances()
    {
        foreach (self::$instances as $key => $instance) {
            unset(self::$instances[$key]);
        }
    }
}
