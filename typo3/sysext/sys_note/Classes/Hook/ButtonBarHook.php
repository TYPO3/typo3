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

namespace TYPO3\CMS\SysNote\Hook;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook for the button bar
 *
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
class ButtonBarHook
{
    private const TABLE_NAME = 'sys_note';
    private const ALLOWED_MODULES = ['web_layout', 'web_list', 'web_info'];

    /**
     * Add a sys_note creation button to the button bar of defined modules
     *
     * @param array $params
     * @param ButtonBar $buttonBar
     *
     * @return array
     * @throws RouteNotFoundException
     */
    public function getButtons(array $params, ButtonBar $buttonBar): array
    {
        $buttons = $params['buttons'];
        $request = $this->getRequest();

        $id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);
        $route = $request->getAttribute('route');
        $normalizedParams = $request->getAttribute('normalizedParams');
        $pageTSconfig = BackendUtility::getPagesTSconfig($id);

        if (!$id
            || $route === null
            || $normalizedParams === null
            || !empty($pageTSconfig['mod.']['SHARED.']['disableSysNoteButton'])
            || !$this->canCreateNewRecord($id)
            || !in_array($route->getOption('moduleName'), self::ALLOWED_MODULES, true)
            || ($route->getOption('moduleName') === 'web_list' && !$this->isCreationAllowed($pageTSconfig['mod.']['web_list.'] ?? []))
        ) {
            return $buttons;
        }

        $uri = (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute(
            'record_edit',
            [
                'edit' => [
                    self::TABLE_NAME => [
                        $id => 'new',
                    ],
                ],
                'returnUrl' => $normalizedParams->getRequestUri(),
            ]
        );

        $buttons[ButtonBar::BUTTON_POSITION_RIGHT][2][] = $buttonBar
            ->makeLinkButton()
            ->setTitle(htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:sys_note/Resources/Private/Language/locallang.xlf:new_internal_note')))
            ->setIcon(GeneralUtility::makeInstance(IconFactory::class)->getIcon('sysnote-type-0', Icon::SIZE_SMALL))
            ->setHref($uri);

        ksort($buttons[ButtonBar::BUTTON_POSITION_RIGHT]);

        return $buttons;
    }

    /**
     * Check if the user is allowed to create a sys_note record
     *
     * @param int $id
     * @return bool
     */
    protected function canCreateNewRecord(int $id): bool
    {
        $tableConfiguration = $GLOBALS['TCA'][self::TABLE_NAME]['ctrl'];
        $pageRow = BackendUtility::getRecord('pages', $id);
        $backendUser = $this->getBackendUserAuthentication();

        return !($pageRow === null
            || ($tableConfiguration['readOnly'] ?? false)
            || ($tableConfiguration['hideTable'] ?? false)
            || ($tableConfiguration['is_static'] ?? false)
            || (($tableConfiguration['adminOnly'] ?? false) && !$backendUser->isAdmin())
            || !$backendUser->doesUserHaveAccess($pageRow, Permission::CONTENT_EDIT)
            || !$backendUser->check('tables_modify', self::TABLE_NAME)
            || !$backendUser->workspaceCanCreateNewRecord(self::TABLE_NAME));
    }

    /**
     * Check if creation is allowed / denied in web_list via mod TSconfig
     *
     * @param array $modTSconfig
     * @return bool
     */
    protected function isCreationAllowed(array $modTSconfig): bool
    {
        $allowedNewTables = GeneralUtility::trimExplode(',', $modTSconfig['allowedNewTables'] ?? '', true);
        $deniedNewTables = GeneralUtility::trimExplode(',', $modTSconfig['deniedNewTables'] ?? '', true);

        return ($allowedNewTables === [] && $deniedNewTables === [])
            || (!in_array(self::TABLE_NAME, $deniedNewTables)
                && ($allowedNewTables === [] || in_array(self::TABLE_NAME, $allowedNewTables)));
    }

    protected function getRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
