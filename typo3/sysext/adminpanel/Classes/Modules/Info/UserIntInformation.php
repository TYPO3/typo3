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

namespace TYPO3\CMS\Adminpanel\Modules\Info;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * UserIntInformation admin panel sub module
 *
 * @internal
 */
#[Autoconfigure(public: true)]
class UserIntInformation extends AbstractSubModule implements DataProviderInterface
{
    public function __construct(
        private readonly ViewFactoryInterface $viewFactory,
    ) {}

    public function getIdentifier(): string
    {
        return 'info_userint';
    }

    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_info.xlf:sub.userint.label'
        );
    }

    public function getDataToStore(ServerRequestInterface $request, ResponseInterface $response): ModuleData
    {
        return new ModuleData(
            [
                'userIntInfo' => $this->getUserIntInfo(),
            ]
        );
    }

    public function getContent(ModuleData $data): string
    {
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:adminpanel/Resources/Private/Templates'],
            partialRootPaths: ['EXT:adminpanel/Resources/Private/Partials'],
            layoutRootPaths: ['EXT:adminpanel/Resources/Private/Layouts'],
        );
        $view = $this->viewFactory->create($viewFactoryData);
        $view->assignMultiple($data->getArrayCopy());
        $view->assign('languageKey', $this->getBackendUser()->user['lang'] ?? null);
        return $view->render('Modules/Info/UserInt');
    }

    protected function getUserIntInfo(): array
    {
        $userIntInfo = [];
        $intScripts = $GLOBALS['TSFE']->config['INTincScript'] ?? [];

        foreach ($intScripts as $intScriptName => $intScriptConf) {
            $info = isset($intScriptConf['type']) ? ['TYPE' => $intScriptConf['type']] : [];
            foreach (($intScriptConf['conf'] ?? []) as $key => $conf) {
                if (is_array($conf)) {
                    $conf = ArrayUtility::flatten($conf);
                }
                $info[$key] = $conf;
            }
            $userIntInfo[$intScriptName] = $info;
        }

        return $userIntInfo;
    }
}
