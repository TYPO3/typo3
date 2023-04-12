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

namespace TYPO3\CMS\Backend\Security\SudoMode\Access;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Factory to create `AccessClaim`, `AccessGrant` and `RouteAccessSubject` instances.
 *
 * @internal
 */
class AccessFactory
{
    protected const DEFAULT_CLAIM_LIFETIME = 300;

    protected readonly int $currentTimestamp;

    public function __construct()
    {
        $this->currentTimestamp = (int)($GLOBALS['EXEC_TIME'] ?? time());
    }

    public function buildClaimFromArray(array $data): AccessClaim
    {
        return GeneralUtility::makeInstance(
            AccessClaim::class,
            $this->buildSubjectFromArray($data['subject']),
            ServerRequestInstruction::buildFromArray($data['instruction']),
            $data['expiration'] ?? 0,
            $data['id'] ?? ''
        );
    }

    public function buildGrantFromArray(array $data): AccessGrant
    {
        return GeneralUtility::makeInstance(
            AccessGrant::class,
            $this->buildSubjectFromArray($data['subject']),
            $data['expiration']
        );
    }

    public function buildSubjectFromArray(array $data): AccessSubjectInterface
    {
        $className = $data['class'] ?? '[empty]';
        if (is_a($className, AccessSubjectInterface::class, true)) {
            return $className::fromArray($data);
        }
        throw new \LogicException(
            sprintf('Subject %s does not implement %s', $className, AccessSubjectInterface::class),
            1605861181
        );
    }

    public function buildRouteAccessSubject(ServerRequestInterface $request): RouteAccessSubject
    {
        /** @var ?Route $route */
        $route = $request->getAttribute('route');
        if ($route === null) {
            throw new \LogicException(
                'Missing route request attribute',
                1605861905
            );
        }
        $settings = $route->getOption('sudoMode');
        return GeneralUtility::makeInstance(
            RouteAccessSubject::class,
            rtrim($route->getPath(), '/'),
            $settings['lifetime'] ?? null,
            $settings['group'] ?? null
        );
    }

    public function buildClaimForSubjectRequest(ServerRequestInterface $request, AccessSubjectInterface $subject): AccessClaim
    {
        return GeneralUtility::makeInstance(
            AccessClaim::class,
            $subject,
            ServerRequestInstruction::createForServerRequest($request),
            $this->currentTimestamp + self::DEFAULT_CLAIM_LIFETIME
        );
    }

    public function buildGrantForSubject(AccessSubjectInterface $subject): AccessGrant
    {
        return GeneralUtility::makeInstance(
            AccessGrant::class,
            $subject,
            $this->currentTimestamp + $subject->getLifetime()->inSeconds()
        );
    }
}
