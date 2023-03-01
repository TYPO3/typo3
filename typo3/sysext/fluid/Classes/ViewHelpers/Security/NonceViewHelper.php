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
 * This ViewHelper resolves the `nonce` attribute from the global server request object,
 * or from the `PolicyProvider` service as a fall-back value.
 *
 * Examples
 * ========
 *
 * Basic usage
 * -----------
 *
 * ::
 *
 *    <script nonce="{f:security.nonce()}">const inline = 'script';</script>
 */
final class NonceViewHelper extends AbstractViewHelper
{
    public function __construct(private readonly RequestId $requestId)
    {
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
    }

    public function render(): string
    {
        return $this->requestId->nonce->b64;
    }
}
