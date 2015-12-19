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

use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
    protected static $instances = array();

    /**
     * Private constructor to prevent instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Gets a form protection instance for the requested class $className.
     *
     * If there already is an existing instance of the requested $className, the
     * existing instance will be returned. If no $className is provided, the factory
     * detects the scope and returns the appropriate form protection object.
     *
     * @param string $className
     * @return \TYPO3\CMS\Core\FormProtection\AbstractFormProtection the requested instance
     */
    public static function get($className = 'default')
    {
        if (isset(self::$instances[$className])) {
            return self::$instances[$className];
        }
        if ($className === 'default') {
            $classNameAndConstructorArguments = self::getClassNameAndConstructorArgumentsByState();
        } else {
            $classNameAndConstructorArguments = func_get_args();
        }
        self::$instances[$className] = self::createInstance($classNameAndConstructorArguments);
        return self::$instances[$className];
    }

    /**
     * Returns the class name depending on TYPO3_MODE and
     * active backend session.
     *
     * @return array
     */
    protected static function getClassNameAndConstructorArgumentsByState()
    {
        switch (true) {
            case self::isInstallToolSession():
                $classNameAndConstructorArguments = [
                    InstallToolFormProtection::class
                ];
                break;
            case self::isFrontendSession():
                $classNameAndConstructorArguments = [
                    FrontendFormProtection::class,
                    $GLOBALS['TSFE']->fe_user
                ];
                break;
            case self::isBackendSession():
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
                break;
            default:
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
        return (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL);
    }

    /**
     * Checks if a user is logged in and the session is active.
     *
     * @return bool
     */
    protected static function isBackendSession()
    {
        return isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER'] instanceof \TYPO3\CMS\Core\Authentication\BackendUserAuthentication && isset($GLOBALS['BE_USER']->user['uid']);
    }

    /**
     * Checks if a frontend user is logged in and the session is active.
     *
     * @return bool
     */
    protected static function isFrontendSession()
    {
        return TYPO3_MODE === 'FE' && is_object($GLOBALS['TSFE']) && $GLOBALS['TSFE']->fe_user instanceof \TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication && isset($GLOBALS['TSFE']->fe_user->user['uid']);
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
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                $languageService->sL('LLL:EXT:lang/locallang_core.xlf:error.formProtection.tokenInvalid'),
                '',
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
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
        $instance = call_user_func_array([\TYPO3\CMS\Core\Utility\GeneralUtility::class, 'makeInstance'], $classNameAndConstructorArguments);
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
     * @param string $className
     * @param AbstractFormProtection $instance
     * @return void
     */
    public static function set($className, AbstractFormProtection $instance)
    {
        self::$instances[$className] = $instance;
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
