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
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * This class creates and manages instances of the various form protection classes.
 *
 * Previously this class provides only provided static methods and could not be instantiated.
 *
 * Since TYPO3 v12, this class can and should be used as a factory to be injected into other
 * controllers or middlewares, to handle FormProtections for HTTP Requests.
 */
class FormProtectionFactory
{
    public function __construct(
        protected readonly FlashMessageService $flashMessageService,
        protected readonly LanguageServiceFactory $languageServiceFactory,
        protected readonly Registry $registry,
        protected readonly FrontendInterface $runtimeCache
    ) {}

    /**
     * Method should be used whenever you do not have direct access to the request object.
     * It is however recommended to use createFromRequest() whenever you have a PSR-7
     * request object available.
     */
    public function createForType(string $type): AbstractFormProtection
    {
        if (!in_array($type, ['installtool', 'frontend', 'backend', 'disabled'], true)) {
            $type = 'disabled';
        }
        $identifier = $this->getIdentifierForType($type);
        if ($this->runtimeCache->has($identifier)) {
            return $this->runtimeCache->get($identifier);
        }
        $classNameAndConstructorArguments = $this->getClassNameAndConstructorArguments($type, $GLOBALS['TYPO3_REQUEST'] ?? null);
        $this->runtimeCache->set($identifier, $this->createInstance(...$classNameAndConstructorArguments));
        return $this->runtimeCache->get($identifier);
    }

    /**
     * Detect the right FormProtection implementation based on the request.
     */
    public function createFromRequest(ServerRequestInterface $request): AbstractFormProtection
    {
        $type = $this->determineTypeFromRequest($request);
        $identifier = $this->getIdentifierForType($type);
        if ($this->runtimeCache->has($identifier)) {
            return $this->runtimeCache->get($identifier);
        }
        $classNameAndConstructorArguments = $this->getClassNameAndConstructorArguments($type, $request);
        $this->runtimeCache->set($identifier, $this->createInstance(...$classNameAndConstructorArguments));
        return $this->runtimeCache->get($identifier);
    }

    /**
     * Detects the type of FormProtection which should be instantiated, based on the request.
     */
    protected function determineTypeFromRequest(ServerRequestInterface $request): string
    {
        if ($this->isInstallToolSession($request)) {
            return 'installtool';
        }
        if ($this->isFrontendSession($request)) {
            return 'frontend';
        }
        if ($this->isBackendSession()) {
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
    protected function getClassNameAndConstructorArguments(string $type, ?ServerRequestInterface $request): array
    {
        if ($type === 'installtool') {
            return [
                InstallToolFormProtection::class,
            ];
        }
        if ($type === 'frontend') {
            $user = $request?->getAttribute('frontend.user');
            if ($user && isset($user->user['uid'])) {
                return [
                    FrontendFormProtection::class,
                    $user,
                ];
            }
        }
        if ($type === 'backend') {
            $user = $GLOBALS['BE_USER'] ?? null;
            $isAjaxCall = (bool)($request ? $request->getAttribute('route')?->getOption('ajax') : false);
            if ($user && isset($user->user['uid'])) {
                return [
                    BackendFormProtection::class,
                    $user,
                    $this->registry,
                    $this->getMessageClosure(
                        $this->languageServiceFactory->createFromUserPreferences($user),
                        $this->flashMessageService->getMessageQueueByIdentifier(),
                        $isAjaxCall
                    ),
                ];
            }
        }
        // failed to use preferred type, disable form protection
        return [
            DisabledFormProtection::class,
        ];
    }

    /**
     * Conveniant method to create a deterministic cache identifier.
     */
    protected function getIdentifierForType(string $type): string
    {
        return 'formprotection-instance-' . hash('xxh3', $type);
    }

    /**
     * Check if we are in the install tool
     */
    protected function isInstallToolSession(ServerRequestInterface $request): bool
    {
        return (bool)((int)$request->getAttribute('applicationType') & SystemEnvironmentBuilder::REQUESTTYPE_INSTALL);
    }

    /**
     * Checks if a user is logged in and the session is active.
     */
    protected function isBackendSession(): bool
    {
        $user = $GLOBALS['BE_USER'] ?? null;
        return $user instanceof BackendUserAuthentication && isset($user->user['uid']);
    }

    /**
     * Checks if a frontend user is logged in and the session is active.
     */
    protected function isFrontendSession(ServerRequestInterface $request): bool
    {
        $user = $request->getAttribute('frontend.user');
        return $user instanceof FrontendUserAuthentication && isset($user->user['uid']);
    }

    protected function getMessageClosure(LanguageService $languageService, FlashMessageQueue $messageQueue, bool $isAjaxCall): \Closure
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
     * @param class-string $className
     * @param array<int,mixed> $constructorArguments
     * @throws \InvalidArgumentException
     */
    protected function createInstance(string $className, ...$constructorArguments): AbstractFormProtection
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
}
