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

namespace TYPO3\CMS\Frontend\Authentication;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Event listener to allow to add custom Frontend Groups to a (frontend) request
 * regardless if a user is logged in or not.
 */
final class ModifyResolvedFrontendGroupsEvent
{
    private FrontendUserAuthentication $user;
    private array $groups;
    private ?ServerRequestInterface $request;

    public function __construct(FrontendUserAuthentication $user, array $groups, ?ServerRequestInterface $request)
    {
        $this->user = $user;
        $this->groups = $groups;
        $this->request = $request;
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    public function getUser(): FrontendUserAuthentication
    {
        return $this->user;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function setGroups(array $groups): void
    {
        $this->groups = $groups;
    }
}
