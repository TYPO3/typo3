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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Core\Bootstrap;

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
    /**
     * Bootstrap extbase and jump to WebInfo controller
     *
     * @return string
     */
    public function main(ServerRequestInterface $request)
    {
        $options = [];
        $queryParams = $request->getQueryParams();
        $queryParams['tx_belog_system_beloglog']['pageId'] = $request->getQueryParams()['id'] ?? $request->getParsedBody()['id'];
        $queryParams['tx_belog_system_beloglog']['layout'] = 'Plain';
        $request = $request->withQueryParams($queryParams);
        $options['moduleConfiguration'] = [
            'extensionName' => 'Belog',
        ];
        $options['moduleName'] = 'system_BelogLog';

        $route = GeneralUtility::makeInstance(Route::class, '/system/BelogLog/', $options);
        $request = $request->withAttribute('route', $route);
        // This can be removed, once https://review.typo3.org/c/Packages/TYPO3.CMS/+/67519 is merged
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $extbaseBootstrap = GeneralUtility::makeInstance(Bootstrap::class);
        return $extbaseBootstrap->handleBackendRequest($request);
    }
}
