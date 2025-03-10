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

namespace TYPO3\CMS\Fluid\ViewHelpers\Uri;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper for creating URIs to external targets, enforcing a specific scheme
 * (https by default).
 * The specified URI is passed through without further resolving or transformation.
 *
 * ```
 *   <f:uri.external uri="https://www.typo3.org" target="_blank">external link</f:uri.external>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-uri-external
 */
final class ExternalViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('uri', 'string', 'target URI', true);
        $this->registerArgument('defaultScheme', 'string', 'scheme the href attribute will be prefixed with if specified $uri does not contain a scheme already', false, 'https');
    }

    public function render(): string
    {
        $uri = $this->arguments['uri'];
        $defaultScheme = $this->arguments['defaultScheme'];
        $scheme = parse_url($uri, PHP_URL_SCHEME);
        if ($scheme === null && $defaultScheme !== '') {
            $uri = $defaultScheme . '://' . $uri;
        }
        return $uri;
    }
}
