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

namespace TYPO3\CMS\FrontendLogin\Hooks;

use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Hook to display verbose information about the felogin plugin in the page module
 *
 * @internal this is a TYPO3 hook implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 * @deprecated The pibase felogin plugin is deprecated since TYPO3 10.4 and will be removed in version 11.0
 */
class CmsLayout implements PageLayoutViewDrawItemHookInterface
{
    /**
     * Preprocesses the preview rendering of a content element.
     *
     * @param PageLayoutView $parentObject Calling parent object
     * @param bool $drawItem Whether to draw the item using the default functionalities
     * @param string $headerContent Header content
     * @param string $itemContent Item content
     * @param array $row Record row of tt_content
     * @deprecated The pibase felogin plugin is deprecated since TYPO3 10.4 and will be removed in version 11.0
     */
    public function preProcess(PageLayoutView &$parentObject, &$drawItem, &$headerContent, &$itemContent, array &$row)
    {
        if ($row['CType'] !== 'login') {
            return;
        }
        trigger_error(
            'The pibase felogin plugin is deprecated since TYPO3 10.4 and will be removed in version 11.0',
            E_USER_DEPRECATED
        );
        $drawItem = false;
        $itemContent .= $parentObject->linkEditContent(
            '<strong>' . htmlspecialchars(
                $this->getLanguageService()->sL(
                    'LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:forms_login_title'
                )
            ) . '</strong>',
            $row
        );
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
