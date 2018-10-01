<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Type\Icon\IconState;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller for icon handling
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class IconController extends AbstractController
{
    /**
     * @var IconRegistry
     */
    protected $iconRegistry;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Set up dependencies
     */
    public function __construct()
    {
        $this->iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * @return ResponseInterface
     * @internal
     */
    public function getCacheIdentifierAction(): ResponseInterface
    {
        return new HtmlResponse($this->iconRegistry->getCacheIdentifier());
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @internal
     */
    public function getIconAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $requestedIcon = json_decode($parsedBody['icon'] ?? $queryParams['icon'], true);

        list($identifier, $size, $overlayIdentifier, $iconState, $alternativeMarkupIdentifier) = $requestedIcon;

        if (empty($overlayIdentifier)) {
            $overlayIdentifier = null;
        }

        $iconState = IconState::cast($iconState);
        $icon = $this->iconFactory->getIcon($identifier, $size, $overlayIdentifier, $iconState);

        return new HtmlResponse($icon->render($alternativeMarkupIdentifier));
    }
}
