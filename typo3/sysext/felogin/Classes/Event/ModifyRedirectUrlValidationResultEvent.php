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

namespace TYPO3\CMS\FrontendLogin\Event;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Allows to modify the result of the redirect URL validation (e.g. allow redirect to specific external URLs).
 */
final class ModifyRedirectUrlValidationResultEvent
{
    public function __construct(
        private readonly string $redirectUrl,
        private bool $validationResult,
        private readonly ServerRequestInterface $request,
    ) {}

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    public function getValidationResult(): bool
    {
        return $this->validationResult;
    }

    public function setValidationResult(bool $validationResult): void
    {
        $this->validationResult = $validationResult;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
