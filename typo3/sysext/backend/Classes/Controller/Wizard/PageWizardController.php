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

namespace TYPO3\CMS\Backend\Controller\Wizard;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\OnTheFly;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEffectivePid;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseParentPageRow;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowInitializeNew;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUniqueUidNewRow;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck;
use TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca;
use TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller providing AJAX endpoints for page wizard functionality.
 * Handles fetching doktypes, page details, and processed field values
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
final readonly class PageWizardController
{
    public function __construct(
        private IconFactory $iconFactory,
        private FormDataCompiler $formDataCompiler
    ) {}

    public function getDoktypesAction(ServerRequestInterface $request): ResponseInterface
    {
        $position = $request->getQueryParams()['data']['position'] ?? [];
        $pageUid = (int)($position['pageUid'] ?? 0);
        $insertPosition = $position['insertPosition'] ?? 'inside';

        $parentPageUid = $insertPosition === 'inside'
            ? $pageUid
            : (BackendUtility::getRecord('pages', $pageUid, 'pid')['pid'] ?? null);

        $backendUser = $this->getBackendUser();
        $parentPage = BackendUtility::readPageAccess((int)$parentPageUid, $backendUser->getPagePermsClause(Permission::PAGE_SHOW));
        if (!$parentPage || !$backendUser->doesUserHaveAccess($parentPage, Permission::PAGE_NEW)) {
            return new JsonResponse(null, 403);
        }

        $formDataGroup = GeneralUtility::makeInstance(OnTheFly::class);
        $formDataGroup->setProviderList([
            InitializeProcessedTca::class,
            DatabaseParentPageRow::class,
            DatabaseUserPermissionCheck::class,
            DatabaseEffectivePid::class,
            UserTsConfig::class,
            PageTsConfig::class,
            DatabaseRowInitializeNew::class,
            DatabaseUniqueUidNewRow::class,
            TcaSelectItems::class,
        ]);

        $doktypes = $this->formDataCompiler
            ->compile(
                [
                    'command' => 'new',
                    'request' => $request,
                    'tableName' => 'pages',
                    'vanillaUid' => $parentPageUid,
                ],
                $formDataGroup
            )['processedTca']['columns']['doktype']['config']['items'] ?? [];

        $result = [];
        foreach ($doktypes as $doktype) {
            $result[] = [
                'value' => $doktype['value'] ?? '',
                'label' => $doktype['label'] ?? '',
                'icon' => $doktype['icon'] ?? '',
                'description' => $doktype['description'] ?? '',
            ];
        }

        return new JsonResponse($result, 200);
    }

    public function getPageDetailAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $pageUid = $params['pageUid'] ?? null;

        if ($pageUid === null) {
            return new JsonResponse(['error' => 'Missing required query parameter: pageUid'], 400);
        }

        if ((int)$pageUid === 0) {
            return new JsonResponse([
                'uid' => 0,
                'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? 'TYPO3',
                'icon' => 'apps-pagetree-root',
            ]);
        }

        $page = BackendUtility::readPageAccess((int)$pageUid, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));

        if (!$page) {
            return new JsonResponse(null, 403);
        }

        $recordInfo = [
            'uid' => $page['uid'],
            'title' => $page['title'],
            'icon' => $this->iconFactory->getIconForRecord('pages', $page, IconSize::SMALL)->getIdentifier(),
        ];

        return new JsonResponse($recordInfo);
    }

    public function getProcessedValueAction(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $fields = $params['fields'] ?? [];
        $pageUid = (int)($params['pageUid'] ?? 0);

        $page = BackendUtility::readPageAccess($pageUid, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
        if (!$page) {
            return new JsonResponse(null, 403);
        }

        $result = [];
        foreach ($fields as $fieldName => $value) {
            $result[$fieldName] = BackendUtility::getProcessedValue('pages', $fieldName, $value, 0, false, false, 0, true, $pageUid);
        }

        return new JsonResponse($result);
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
