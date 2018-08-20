<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Service;

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

use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class for the Edit Toolbar
 *
 * @internal
 */
class EditToolbarService
{

    /**
     * Creates the tool bar links for the "edit" section of the Admin Panel.
     *
     * @return string A string containing images wrapped in <a>-tags linking them to proper functions.
     */
    public function createToolbar(): string
    {
        /** @var LanguageAspect $languageAspect */
        $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $tsfe = $this->getTypoScriptFrontendController();
        //  If mod.newContentElementWizard.override is set, use that extension's create new content wizard instead:
        $moduleName = BackendUtility::getPagesTSconfig($tsfe->page['uid'])['mod.']['newContentElementWizard.']['override'] ?? 'new_content_element';
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $perms = $this->getBackendUser()->calcPerms($tsfe->page);
        $langAllowed = $this->getBackendUser()->checkLanguageAccess($languageAspect->getId());
        $id = $tsfe->id;
        $returnUrl = GeneralUtility::getIndpEnv('REQUEST_URI');
        $classes = 'typo3-adminPanel-btn typo3-adminPanel-btn-default';
        $output = [];
        $output[] = '<div class="typo3-adminPanel-form-group">';
        $output[] = '  <div class="typo3-adminPanel-btn-group" role="group">';

        // History
        $link = (string)$uriBuilder->buildUriFromRoute(
            'record_history',
            [
                'element' => 'pages:' . $id,
                'returnUrl' => $returnUrl,
            ]
        );
        $title = $this->getLabel('edit_recordHistory');
        $output[] = '<a class="' .
                    $classes .
                    '" href="' .
                    htmlspecialchars($link, ENT_QUOTES | ENT_HTML5) .
                    '#latest" title="' .
                    $title .
                    '">';
        $output[] = '  ' . $iconFactory->getIcon('actions-document-history-open', Icon::SIZE_SMALL)->render();
        $output[] = '</a>';

        // New Content
        if ($perms & Permission::CONTENT_EDIT && $langAllowed) {
            $linkParameters = [
                'id' => $id,
                'returnUrl' => $returnUrl,
            ];
            if (!empty($languageAspect->getId())) {
                $linkParameters['sys_language_uid'] = $languageAspect->getId();
            }
            $link = (string)$uriBuilder->buildUriFromRoute($moduleName, $linkParameters);
            $icon = $iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)->render();
            $title = $this->getLabel('edit_newContentElement');
            $output[] = '<a class="' .
                        $classes .
                        '" href="' .
                        htmlspecialchars($link, ENT_QUOTES | ENT_HTML5) .
                        '" title="' .
                        $title .
                        '">';
            $output[] = '  ' . $icon;
            $output[] = '</a>';
        }

        // Move Page
        if ($perms & Permission::PAGE_EDIT) {
            $link = (string)$uriBuilder->buildUriFromRoute(
                'move_element',
                [
                    'table' => 'pages',
                    'uid' => $id,
                    'returnUrl' => $returnUrl,
                ]
            );
            $icon = $iconFactory->getIcon('actions-document-move', Icon::SIZE_SMALL)->render();
            $title = $this->getLabel('edit_move_page');
            $output[] = '<a class="' .
                        $classes .
                        '" href="' .
                        htmlspecialchars($link, ENT_QUOTES | ENT_HTML5) .
                        '" title="' .
                        $title .
                        '">';
            $output[] = '  ' . $icon;
            $output[] = '</a>';
        }

        // New Page
        if ($perms & Permission::PAGE_NEW) {
            $link = (string)$uriBuilder->buildUriFromRoute(
                'db_new',
                [
                    'id' => $id,
                    'pagesOnly' => 1,
                    'returnUrl' => $returnUrl,
                ]
            );
            $icon = $iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL)->render();
            $title = $this->getLabel('edit_newPage');
            $output[] = '<a class="' .
                        $classes .
                        '" href="' .
                        htmlspecialchars($link, ENT_QUOTES | ENT_HTML5) .
                        '" title="' .
                        $title .
                        '">';
            $output[] = '  ' . $icon;
            $output[] = '</a>';
        }

        // Edit Page
        if ($perms & Permission::PAGE_EDIT) {
            $link = (string)$uriBuilder->buildUriFromRoute(
                'record_edit',
                [
                    'edit[pages][' . $id . ']' => 'edit',
                    'noView' => 1,
                    'returnUrl' => $returnUrl,
                ]
            );
            $icon = $iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render();
            $title = $this->getLabel('edit_editPageProperties');
            $output[] = '<a class="' .
                        $classes .
                        '" href="' .
                        htmlspecialchars($link, ENT_QUOTES | ENT_HTML5) .
                        '" title="' .
                        $title .
                        '">';
            $output[] = '  ' . $icon;
            $output[] = '</a>';
        }

        // Edit Page Overlay
        if ($perms & Permission::PAGE_EDIT && $languageAspect->getId() > 0 && $langAllowed) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages');
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
            $row = $queryBuilder
                ->select('uid', 'pid', 't3ver_state')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'],
                        $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA']['pages']['ctrl']['languageField'],
                        $queryBuilder->createNamedParameter($languageAspect->getId(), \PDO::PARAM_INT)
                    )
                )
                ->setMaxResults(1)
                ->execute()
                ->fetch();
            $tsfe->sys_page->versionOL('pages', $row);
            if (is_array($row)) {
                $link = (string)$uriBuilder->buildUriFromRoute(
                    'record_edit',
                    [
                        'edit[pages][' . $row['uid'] . ']' => 'edit',
                        'noView' => 1,
                        'returnUrl' => $returnUrl,
                    ]
                );
                $icon = $iconFactory->getIcon('mimetypes-x-content-page-language-overlay', Icon::SIZE_SMALL)
                    ->render();
                $title = $this->getLabel('edit_editPageOverlay');
                $output[] = '<a class="' .
                            $classes .
                            '" href="' .
                            htmlspecialchars($link, ENT_QUOTES | ENT_HTML5) .
                            '" title="' .
                            $title .
                            '">';
                $output[] = '  ' . $icon;
                $output[] = '</a>';
            }
        }

        // Open list view
        if ($this->getBackendUser()->check('modules', 'web_list')) {
            $link = (string)$uriBuilder->buildUriFromRoute(
                'web_list',
                [
                    'id' => $id,
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                ]
            );
            $icon = $iconFactory->getIcon('actions-system-list-open', Icon::SIZE_SMALL)->render();
            $title = $this->getLabel('edit_db_list');
            $output[] = '<a class="' .
                        $classes .
                        '" href="' .
                        htmlspecialchars($link, ENT_QUOTES | ENT_HTML5) .
                        '" title="' .
                        $title .
                        '">';
            $output[] = '  ' . $icon;
            $output[] = '</a>';
        }

        $output[] = '  </div>';
        $output[] = '</div>';
        return implode('', $output);
    }

    /**
     * Translate given key
     *
     * @param string $key Key for a label in the $LOCAL_LANG array of "sysext/core/Resources/Private/Language/locallang_tsfe.xlf
     * @return string The value for the $key
     */
    protected function getLabel($key): ?string
    {
        return htmlspecialchars($this->getLanguageService()->getLL($key), ENT_QUOTES | ENT_HTML5);
    }

    /**
     * @return FrontendBackendUserAuthentication
     */
    protected function getBackendUser(): FrontendBackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }
}
