<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\FormProtection;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * This class creates and manages instances of the various form protection classes.
 *
 * Previously this class provides only provided static methods and could not be instantiated.
 *
 * Since TYPO3 v12.0, this class should be used as a factory to be injectable in other
 * controllers or middlewares, to handle FormProtections via Requests.
 */
class FormProtectionFactory
{
    /**
     * created instances of form protections using the type as array key
     *
     * @var array<string, AbstractFormProtection>
     */
    protected static $instances = [];

    public function __construct()
    {
    }

    /**
     * Detect the right FormProtection implementation based on the request. Should be used instead of
     * FormProtectionFactory::get()
     */
    public function createFromRequest(ServerRequestInterface $request): AbstractFormProtection
    {
        $type = $this->determineTypeFromRequest($request);
        if (isset(self::$instances[$type])) {
            return self::$instances[$type];
        }
        $classNameAndConstructorArguments = $this->getClassNameAndConstructorArguments($type, $request);
        self::$instances[$type] = self::createInstance(...$classNameAndConstructorArguments);
        return self::$instances[$type];
    }

    /**
     * Detects the type of FormProtection which should be instantiated, based on the request.
     */
    protected function determineTypeFromRequest(ServerRequestInterface $request): string
    {
        if (self::isInstallToolSession($request)) {
            return 'installtool';
        }
        if (self::isFrontendSession($request)) {
            return 'frontend';
        }
        if (self::isBackendSession($request)) {
            return 'backend';
        }
        return 'disabled';
    }

    /**
     * This is the equivalent to getClassNameAndConstructorArgumentsByType() but non-static.
     * It also does not handle "default" or class names, but is based on types previously resolved by
     * the request. See determineTypeFromRequest()
     *
     * @param string $type Valid types: installtool, frontend, backend.
     * @return array Array of arguments
     */
    protected function getClassNameAndConstructorArguments(string $type, ServerRequestInterface $request): array
    {
        if ($type === 'installtool') {
            return [
                InstallToolFormProtection::class,
            ];
        }
        if ($type === 'frontend') {
            $user = $request->getAttribute('frontend.user');
            return [
                FrontendFormProtection::class,
                $user,
            ];
        }
        if ($type === 'backend') {
            $user = $request->getAttribute('backend.user');
            $isAjaxCall = (bool)($request->getAttribute('route')?->getOption('ajax'));
            return [
                BackendFormProtection::class,
                $user,
                GeneralUtility::makeInstance(Registry::class),
                self::getMessageClosure(
                    $GLOBALS['LANG'],
                    GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier(),
                    $isAjaxCall
                ),
            ];
        }
        // failed to use preferred type, disable form protection
        return [
            DisabledFormProtection::class,
        ];
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
     * @param array<int,mixed> $constructorArguments Arguments for the class-constructor
     * @return \TYPO3\CMS\Core\FormProtection\AbstractFormProtection the requested instance
     */
    public static function get($classNameOrType = 'default', ...$constructorArguments)
    {
        if (isset(self::$instances[$classNameOrType])) {
            return self::$instances[$classNameOrType];
        }
        if ($classNameOrType === 'default' || $classNameOrType === 'installtool' || $classNameOrType === 'frontend' || $classNameOrType === 'backend') {
            $classNameAndConstructorArguments = self::getClassNameAndConstructorArgumentsByType($classNameOrType);
            self::$instances[$classNameOrType] = self::createInstance(...$classNameAndConstructorArguments);
        } else {
            self::$instances[$classNameOrType] = self::createInstance($classNameOrType, ...$constructorArguments);
        }
        return self::$instances[$classNameOrType];
    }

    /**
     * Returns the class name and parameters depending on the given type.
     * If the type cannot be used currently, protection is disabled.
     *
     * @param string $type Valid types: default, installtool, frontend, backend. "default" makes an autodetection on the current state
     * @return array Array of arguments
     */
    protected static function getClassNameAndConstructorArgumentsByType($type, ServerRequestInterface $request = null)
    {
        if (self::isInstallToolSession($request) && ($type === 'default' || $type === 'installtool')) {
            $classNameAndConstructorArguments = [
                InstallToolFormProtection::class,
            ];
        } elseif (self::isFrontendSession($request) && ($type === 'default' || $type === 'frontend')) {
            $classNameAndConstructorArguments = [
                FrontendFormProtection::class,
                $GLOBALS['TSFE']->fe_user,
            ];
        } elseif (self::isBackendSession($request) && ($type === 'default' || $type === 'backend')) {
            $isAjaxCall = false;
            $request = $request ?? $GLOBALS['TYPO3_REQUEST'] ?? null;
            if ($request instanceof ServerRequestInterface
                && (bool)($request->getAttribute('route')?->getOption('ajax'))
            ) {
                $isAjaxCall = true;
            }
            $classNameAndConstructorArguments = [
                BackendFormProtection::class,
                $GLOBALS['BE_USER'],
                GeneralUtility::makeInstance(Registry::class),
                self::getMessageClosure(
                    $GLOBALS['LANG'],
                    GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier(),
                    $isAjaxCall
                ),
            ];
        } else {
            // failed to use preferred type, disable form protection
            $classNameAndConstructorArguments = [
                DisabledFormProtection::class,
            ];
        }
        return $classNameAndConstructorArguments;
    }

    /**
     * Check if we are in the install tool
     *
     * @return bool
     */
    protected static function isInstallToolSession(?ServerRequestInterface $request = null): bool
    {
        $isInstallTool = false;
        $request = $request ?? $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($request instanceof ServerRequestInterface
            && (bool)((int)$request->getAttribute('applicationType') & SystemEnvironmentBuilder::REQUESTTYPE_INSTALL)
        ) {
            $isInstallTool = true;
        }
        return $isInstallTool;
    }

    /**
     * Checks if a user is logged in and the session is active.
     */
    protected static function isBackendSession(?ServerRequestInterface $request = null): bool
    {
        if ($request instanceof ServerRequestInterface) {
            $user = $request->getAttribute('backend.user');
        } else {
            $user = $GLOBALS['BE_USER'] ?? null;
        }
        return $user instanceof BackendUserAuthentication && isset($user->user['uid']);
    }

    /**
     * Checks if a frontend user is logged in and the session is active.
     */
    protected static function isFrontendSession(?ServerRequestInterface $request = null): bool
    {
        if ($request instanceof ServerRequestInterface) {
            $user = $request->getAttribute('frontend.user');
        } else {
            $user = ($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController ? $GLOBALS['TSFE']->fe_user : null;
        }
        return $user instanceof FrontendUserAuthentication && isset($user->user['uid']);
    }

    /**
     * @param LanguageService $languageService
     * @param FlashMessageQueue $messageQueue
     * @param bool $isAjaxCall
     * @internal Only public to be used in tests
     * @return \Closure
     */
    public static function getMessageClosure(LanguageService $languageService, FlashMessageQueue $messageQueue, bool $isAjaxCall)
    {
        return static function () use ($languageService, $messageQueue, $isAjaxCall) {
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:error.formProtection.tokenInvalid'),
                '',
                ContextualFeedbackSeverity::ERROR,
                !$isAjaxCall
            );
            $messageQueue->enqueue($flashMessage);
        };
    }

    /**
     * Creates an instance for the requested class $className
     * and stores it internally.
     *
     * @param string $className
     * @param array<int,mixed> $constructorArguments
     * @throws \InvalidArgumentException
     * @return AbstractFormProtection
     */
    protected static function createInstance($className, ...$constructorArguments)
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException('$className must be the name of an existing class, but actually was "' . $className . '".', 1285352962);
        }
        $instance = GeneralUtility::makeInstance($className, ...$constructorArguments);
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
     * @internal
     * @param string $classNameOrType
     * @param AbstractFormProtection $instance
     */
    public static function set($classNameOrType, AbstractFormProtection $instance)
    {
        self::$instances[$classNameOrType] = $instance;
    }

    /**
     * Purges all existing instances.
     *
     * This function is particularly useful when cleaning up in unit testing.
     */
    public static function purgeInstances()
    {
        foreach (self::$instances as $key => $instance) {
            unset(self::$instances[$key]);
        }
    }
}
