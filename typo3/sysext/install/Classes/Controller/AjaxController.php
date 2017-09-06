<?php
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
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Install tool ajax controller, handles ajax requests
 */
class AjaxController extends AbstractController
{
    /**
     * @var string
     */
    protected $unauthorized = 'unauthorized';

    /**
     * @var array List of valid action names that need authentication
     */
    protected $authenticationActions = [
        'changeInstallToolPassword',
        'clearAllCache',
        'clearTablesClear',
        'clearTablesStats',
        'clearTypo3tempFiles',

        'coreUpdateActivate',
        'coreUpdateCheckPreConditions',
        'coreUpdateDownload',
        'coreUpdateIsUpdateAvailable',
        'coreUpdateMove',
        'coreUpdateUnpack',
        'coreUpdateUpdateVersionMatrix',
        'coreUpdateVerifyChecksum',

        'createAdmin',
        'databaseAnalyzerAnalyze',
        'databaseAnalyzerExecute',
        'dumpAutoload',
        'environmentCheckGetStatus',
        'extensionCompatibilityTester',
        'extensionScannerFiles',
        'extensionScannerScanFile',
        'extensionScannerMarkFullyScannedRestFiles',

        'folderStructureGetStatus',
        'folderStructureFix',
        'imageProcessing',
        'localConfigurationWrite',
        'mailTest',
        'presetActivate',
        'resetBackendUserUc',

        'systemMaintainerGetList',
        'systemMaintainerWrite',

        'tcaExtTablesCheck',
        'tcaMigrationsCheck',

        'uninstallExtension',

        'upgradeDocsMarkRead',
        'upgradeDocsUnmarkRead',

        'upgradeWizardsBlockingDatabaseAdds',
        'upgradeWizardsBlockingDatabaseExecute',
        'upgradeWizardsBlockingDatabaseCharsetTest',
        'upgradeWizardsBlockingDatabaseCharsetFix',
        'upgradeWizardsDoneUpgrades',
        'upgradeWizardsExecute',
        'upgradeWizardsInput',
        'upgradeWizardsList',
        'upgradeWizardsMarkUndone',
        'upgradeWizardsSilentUpgrades',
    ];

    /**
     * Main entry point
     *
     * @param $request ServerRequestInterface
     * @return ResponseInterface
     * @throws Exception
     */
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $action = $this->sanitizeAction($request->getParsedBody()['install']['action'] ?? $request->getQueryParams()['install']['action'] ?? '');
        if ($action === '') {
            $this->output('noAction');
        }
        $this->validateAuthenticationAction($action);
        $actionClass = ucfirst($action);
        /** @var \TYPO3\CMS\Install\Controller\Action\ActionInterface $toolAction */
        $toolAction = GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Controller\\Action\\Ajax\\' . $actionClass);
        if (!($toolAction instanceof Action\ActionInterface)) {
            throw new Exception(
                $action . ' does not implement ActionInterface',
                1369474308
            );
        }
        $toolAction->setController('ajax');
        $toolAction->setAction($action);
        $toolAction->setContext($request->getAttribute('context', 'standalone'));
        $toolAction->setToken($this->generateTokenForAction($action));
        $toolAction->setPostValues($request->getParsedBody()['install'] ?? []);
        return $this->output($toolAction->handle());
    }

    /**
     * Render "unauthorized"
     *
     * @param ServerRequestInterface $request
     * @param FlashMessage $message
     * @return ResponseInterface
     */
    public function unauthorizedAction(ServerRequestInterface $request, FlashMessage $message = null): ResponseInterface
    {
        return $this->output($this->unauthorized);
    }

    /**
     * Creates a PSR-7 response
     *
     * @param string $content
     * @return ResponseInterface
     */
    public function output($content = ''): ResponseInterface
    {
        ob_clean();
        $response = new Response('php://temp', 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Pragma' => 'no-cache'
        ]);
        $response->getBody()->write($content);
        return $response;
    }
}
