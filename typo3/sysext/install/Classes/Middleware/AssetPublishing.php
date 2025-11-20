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

namespace TYPO3\CMS\Install\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;

/**
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
final readonly class AssetPublishing implements MiddlewareInterface
{
    public function __construct(
        private PackageManager $packageManager,
        private SystemResourcePublisherInterface $resourcePublisher,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        foreach ($this->packageManager->getActivePackages() as $package) {
            if ($package->isPartOfMinimalUsableSystem()) {
                $this->resourcePublisher->publishResources($package);
            }
        }
        return $handler->handle($request);
    }
}
