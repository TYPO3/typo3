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

namespace TYPO3\CMS\Extbase\Service;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Attribute\Authorize;
use TYPO3\CMS\Extbase\Authorization\AuthorizationFailureReason;
use TYPO3\CMS\Extbase\Authorization\AuthorizationResult;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
readonly class ActionAuthorizationService implements SingletonInterface
{
    public function __construct(protected Context $context) {}

    /**
     * Checks all authorize attributes
     *
     * @param array<Authorize> $authorizeAttributes
     */
    public function checkAuthorization(
        ActionController $controller,
        array $authorizeAttributes,
        array $preparedArguments
    ): AuthorizationResult {
        if ($authorizeAttributes === []) {
            return AuthorizationResult::allowed();
        }

        foreach ($authorizeAttributes as $authorize) {
            $result = $this->evaluateAuthorizeAttribute($authorize, $controller, $preparedArguments);
            if ($result->isDenied()) {
                return $result;
            }
        }

        return AuthorizationResult::allowed();
    }

    protected function evaluateAuthorizeAttribute(
        Authorize $authorize,
        ActionController $controller,
        array $preparedArguments
    ): AuthorizationResult {
        $userAspect = $this->context->getAspect('frontend.user');

        if ($authorize->requireLogin && !$userAspect->isLoggedIn()) {
            return AuthorizationResult::denied(AuthorizationFailureReason::NOT_LOGGED_IN, $authorize);
        }

        if (!$this->checkGroupAccess($authorize, $userAspect)) {
            return AuthorizationResult::denied(AuthorizationFailureReason::MISSING_GROUP, $authorize);
        }

        if ($authorize->callback !== null && !$this->executeCallback($authorize, $controller, $preparedArguments)) {
            return AuthorizationResult::denied(AuthorizationFailureReason::CALLBACK_DENIED, $authorize);
        }

        return AuthorizationResult::allowed();
    }

    protected function checkGroupAccess(Authorize $authorize, object $userAspect): bool
    {
        if (empty($authorize->requireGroups)) {
            return true;
        }

        $userGroupIds = $userAspect->getGroupIds();
        $userGroupNames = $userAspect->getGroupNames();

        foreach ($authorize->requireGroups as $requiredGroup) {
            if (is_numeric($requiredGroup) && in_array((int)$requiredGroup, $userGroupIds, true)) {
                return true;
            }
            if (!is_numeric($requiredGroup) && in_array($requiredGroup, $userGroupNames, true)) {
                return true;
            }
        }

        return false;
    }

    protected function executeCallback(
        Authorize $authorize,
        ActionController $controller,
        array $preparedArguments
    ): bool {
        if (is_array($authorize->callback)) {
            return $this->executeClassCallback($authorize->callback, $preparedArguments);
        }
        return $this->executeControllerCallback($controller, $authorize->callback, $preparedArguments);
    }

    protected function executeClassCallback(array $callback, array $arguments): bool
    {
        [$className, $methodName] = $callback;

        $instance = $this->getCallbackInstance($className);
        $this->validateCallbackMethod($instance, $methodName, $className);

        return (bool)$instance->$methodName(...$arguments);
    }

    protected function executeControllerCallback(ActionController $controller, string $methodName, array $arguments): bool
    {
        $this->validateCallbackMethod($controller, $methodName, $controller::class);
        return (bool)$controller->$methodName(...$arguments);
    }

    protected function getCallbackInstance(string $className): object
    {
        if (!class_exists($className)) {
            throw new \RuntimeException(
                sprintf('Authorization callback class "%s" does not exist', $className),
                1761287267
            );
        }
        return GeneralUtility::makeInstance($className);
    }

    protected function validateCallbackMethod(object $instance, string $methodName, string $className): void
    {
        if (!method_exists($instance, $methodName)) {
            throw new \RuntimeException(
                sprintf('Authorization callback method "%s::%s" does not exist', $className, $methodName),
                1761287268
            );
        }

        $reflectionMethod = new \ReflectionMethod($instance, $methodName);
        if (!$reflectionMethod->isPublic()) {
            throw new \RuntimeException(
                sprintf('Authorization callback method "%s::%s" must be public', $className, $methodName),
                1761287269
            );
        }
    }
}
