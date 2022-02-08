<?php

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

namespace TYPO3\CMS\Belog\Module;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Core\Bootstrap as ExtbaseBootstrap;

/**
 * This class is a wrapper for WebInfo controller of belog.
 * It is registered in ext_tables.php with \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction()
 * and called by the info extension.
 *
 * Extbase currently provides no way to register a "TBE_MODULES_EXT" module directly,
 * therefore we need to bootstrap extbase on our own here to jump to the WebInfo controller.
 * @internal This class is experimental and is not considered part of the Public TYPO3 API.
 */
class BackendLogModuleBootstrap
{
    public function __construct(
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleProvider $moduleProvider,
        protected readonly ExtbaseBootstrap $extbaseBootstrap,
    ) {
    }

    /**
     * Bootstrap extbase and jump to WebInfo controller
     */
    public function main(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $queryParams['tx_belog_system_beloglog']['pageId'] = $request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0;
        $queryParams['tx_belog_system_beloglog']['layout'] = 'Plain';

        return $this->extbaseBootstrap->handleBackendRequest(
            $request
                ->withQueryParams($queryParams)
                ->withAttribute('module', $this->moduleProvider->getModule('system_BelogLog'))
        );
    }
}
