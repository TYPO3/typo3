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

namespace TYPO3\CMS\Core\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Imaging\IconState;

/**
 * Controller for icon handling
 *
 * @internal
 */
#[AsController]
readonly class IconController
{
    public function __construct(
        private IconFactory $iconFactory
    ) {}

    public function getIcon(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $requestedIcon = json_decode($parsedBody['icon'] ?? $queryParams['icon'], true);

        [$identifier, $size, $overlayIdentifier, $iconState, $alternativeMarkupIdentifier] = $requestedIcon;

        if (empty($overlayIdentifier)) {
            $overlayIdentifier = null;
        }

        $iconState = IconState::tryFrom($iconState);
        $icon = $this->iconFactory->getIcon($identifier, IconSize::from($size), $overlayIdentifier, $iconState);

        return new HtmlResponse($icon->render($alternativeMarkupIdentifier));
    }
}
