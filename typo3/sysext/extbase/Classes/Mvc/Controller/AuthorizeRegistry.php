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

namespace TYPO3\CMS\Extbase\Mvc\Controller;

use TYPO3\CMS\Extbase\Attribute\Authorize;
use TYPO3\CMS\Extbase\Authorization\AuthorizationResult;
use TYPO3\CMS\Extbase\Service\ActionAuthorizationService;

/**
 * Registry for authorization configurations of extbase controller actions,
 * populated at compile time via {@see \TYPO3\CMS\Extbase\DependencyInjection\AuthorizePass}.
 *
 * @internal
 */
final class AuthorizeRegistry
{
    /** @var array<string, array<string, list<Authorize>>> */
    private array $authorizations = [];

    public function __construct(
        private readonly ActionAuthorizationService $authorizationService,
    ) {}

    public function add(string $controllerClass, string $actionMethod, string|array|null $callback, bool $requireLogin, array $requireGroups): void
    {
        $this->authorizations[$controllerClass][$actionMethod][] = new Authorize($callback, $requireLogin, $requireGroups);
    }

    /**
     * @return list<Authorize>
     */
    public function getAuthorizeAttributes(string $controllerClass, string $actionMethod): array
    {
        return $this->authorizations[$controllerClass][$actionMethod] ?? [];
    }

    public function checkAuthorization(ActionController $controller, string $actionMethod, array $preparedArguments): ?AuthorizationResult
    {
        $authorizeAttributes = $this->getAuthorizeAttributes($controller::class, $actionMethod);
        if ($authorizeAttributes === []) {
            return null;
        }

        return $this->authorizationService->checkAuthorization($controller, $authorizeAttributes, $preparedArguments);
    }
}
