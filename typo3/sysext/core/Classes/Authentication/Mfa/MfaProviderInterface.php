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

namespace TYPO3\CMS\Core\Authentication\Mfa;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * To be implemented by all MFA providers.
 *
 * @internal This is an experimental TYPO3 Core API and subject to change until v11 LTS
 */
interface MfaProviderInterface
{
    /**
     * Check if the current request can be handled by this provider (e.g.
     * necessary query arguments are set).
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    public function canProcess(ServerRequestInterface $request): bool;

    /**
     * Check if provider is active for the user by e.g. checking the user
     * record for some provider specific active state.
     *
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool
     */
    public function isActive(MfaProviderPropertyManager $propertyManager): bool;

    /**
     * Check if provider is temporarily locked for the user, because
     * of e.g. to much false authentication attempts. This differs
     * from the "isActive" state on purpose, so please DO NOT use
     * the "isActive" state for such check internally. This will
     * allow attackers to easily circumvent MFA!
     *
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool
     */
    public function isLocked(MfaProviderPropertyManager $propertyManager): bool;

    /**
     * Verifies the MFA request
     *
     * @param ServerRequestInterface $request
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool
     */
    public function verify(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool;

    /**
     * Generate the provider specific response for the given view type.
     * Note: Currently the calling controller only evaluates the response
     * body and directly injects it into the corresponding view. It's however
     * planned to also take further information like headers into account.
     *
     * @param ServerRequestInterface $request
     * @param MfaProviderPropertyManager $propertyManager
     * @param string $type
     * @return ResponseInterface
     * @see MfaViewType
     */
    public function handleRequest(
        ServerRequestInterface $request,
        MfaProviderPropertyManager $propertyManager,
        string $type
    ): ResponseInterface;

    /**
     * Activate / register this provider for the user
     *
     * @param ServerRequestInterface $request
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool TRUE in case operation was successful, FALSE otherwise
     */
    public function activate(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool;

    /**
     * Deactivate this provider for the user
     *
     * @param ServerRequestInterface $request
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool TRUE in case operation was successful, FALSE otherwise
     */
    public function deactivate(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool;

    /**
     * Unlock this provider for the user
     *
     * @param ServerRequestInterface $request
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool TRUE in case operation was successful, FALSE otherwise
     */
    public function unlock(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool;

    /**
     * Handle changes of the provider by the user
     *
     * @param ServerRequestInterface $request
     * @param MfaProviderPropertyManager $propertyManager
     * @return bool TRUE in case operation was successful, FALSE otherwise
     */
    public function update(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool;
}
