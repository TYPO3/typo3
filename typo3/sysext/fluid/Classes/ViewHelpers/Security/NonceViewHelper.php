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

namespace TYPO3\CMS\Fluid\ViewHelpers\Security;

use TYPO3\CMS\Core\Core\RequestId;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to retrieve (and consume) a `nonce` attribute from
 * the global server request object pool, or from the `PolicyProvider`
 * service as a fall-back value.
 *
 * ```
 *   <script nonce="{f:security.nonce()}">const inline = 'script';</script>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-security-nonce
 * @see https://docs.typo3.org/permalink/t3coreapi:content-security-policy
 * @see \TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyProvider
 */
final class NonceViewHelper extends AbstractViewHelper
{
    public function __construct(
        private readonly RequestId $requestId,
    ) {}

    public function initializeArguments(): void
    {
        parent::initializeArguments();
    }

    public function render(): string
    {
        return $this->requestId->nonce->consume();
    }
}
