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

namespace TYPO3Tests\ActionControllerTest\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Attribute\Authorize;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3Tests\ActionControllerTest\Service\ActionAccessCheckService;

/**
 * Fixture controller with test actions for #[Authorize] attribute
 */
class AccessCheckController extends ActionController
{
    #[Authorize(requireLogin: true)]
    public function feUserRequiredAction(int $accessCheckArgument): ResponseInterface
    {
        return $this->htmlResponse('Success');
    }

    #[Authorize(requireGroups: [1])]
    public function feGroupUidRequiredAction(int $accessCheckArgument): ResponseInterface
    {
        return $this->htmlResponse('Success');
    }

    #[Authorize(requireGroups: ['admin'])]
    public function feGroupNameRequiredAction(int $accessCheckArgument): ResponseInterface
    {
        return $this->htmlResponse('Success');
    }

    #[Authorize(requireGroups: [1, 'admin'])]
    public function oneOfMultipleFeGroupsRequiredAction(int $accessCheckArgument): ResponseInterface
    {
        return $this->htmlResponse('Success');
    }

    #[Authorize(callback: 'callbackForAuthorizationWithSimpleCallbackAction')]
    public function authorizationWithSimpleCallbackAction(int $accessCheckArgument): ResponseInterface
    {
        return $this->htmlResponse('Success');
    }

    #[Authorize(callback: [ActionAccessCheckService::class, 'performAccessCheckForIntegerArgument'])]
    public function authorizationWithCustomServiceClassCallbackAction(int $accessCheckArgument): ResponseInterface
    {
        return $this->htmlResponse('Success');
    }

    // This is a simple callback method that returns true if the argument is 1, false otherwise
    public function callbackForAuthorizationWithSimpleCallbackAction(int $accessCheckArgument): bool
    {
        return $accessCheckArgument === 1;
    }
}
