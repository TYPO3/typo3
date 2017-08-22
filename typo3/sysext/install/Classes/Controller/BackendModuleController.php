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
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\FormProtection\AbstractFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Install\Service\EnableFileService;

/**
 * Backend module controller
 *
 * Embeds in backend and only shows the 'enable install tool button' or redirects
 * to step installer if install tool is enabled.
 *
 * This is a classic backend module that does not interfere with other code
 * within the install tool, it can be seen as a facade around install tool just
 * to embed the install tool in backend.
 */
class BackendModuleController
{
    /**
     * Index action shows install tool / step installer or redirect to action to enable install tool
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response)
    {
        /** @var EnableFileService $enableFileService */
        $enableFileService = GeneralUtility::makeInstance(EnableFileService::class);
        /** @var AbstractFormProtection $formProtection */
        $formProtection = FormProtectionFactory::get();

        if ($enableFileService->checkInstallToolEnableFile()) {
            // Install tool is open and valid, redirect to it
            $response = $response->withStatus(303)->withHeader('Location', 'install.php?install[context]=backend');
        } elseif ($request->getMethod() === 'POST' && $request->getParsedBody()['action'] === 'enableInstallTool') {
            // Request to open the install tool
            $installToolEnableToken = $request->getParsedBody()['installToolEnableToken'];
            if (!$formProtection->validateToken($installToolEnableToken, 'installTool')) {
                throw new \RuntimeException('Given form token was not valid', 1369161225);
            }
            $enableFileService->createInstallToolEnableFile();
            // Install tool is open and valid, redirect to it
            $response = $response->withStatus(303)->withHeader('Location', 'install.php?install[context]=backend');
        } else {
            // Show the "create enable install tool" button
            /** @var StandaloneView $view */
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplatePathAndFilename(
                GeneralUtility::getFileAbsFileName(
                'EXT:install/Resources/Private/Templates/BackendModule/ShowEnableInstallToolButton.html'
            )
            );
            $token = $formProtection->generateToken('installTool');
            $view->assign('installToolEnableToken', $token);
            /** @var ModuleTemplate $moduleTemplate */
            $moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
            $moduleTemplate->setContent($view->render());
            $response->getBody()->write($moduleTemplate->renderContent());
        }
        return $response;
    }
}
