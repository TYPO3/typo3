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

namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ViewHelper which returns the current page path as known from TYPO3 backend modules.
 *
 * ```
 *   <f:be.pagePath />
 * ```
 *
 * **Note:** This ViewHelper is experimental!
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-be-pagepath
 * @todo: Candidate to deprecate? The page info is typically displayed in doc header, done by ModuleTemplate in controllers.
 */
final class PagePathViewHelper extends AbstractBackendViewHelper
{
    /**
     * This ViewHelper renders HTML, thus output must not be escaped
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function render(): string
    {
        $id = 0;
        if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
            $id = $request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0;
        }
        $pageRecord = BackendUtility::readPageAccess($id, $GLOBALS['BE_USER']->getPagePermsClause(Permission::PAGE_SHOW));
        // Is this a real page
        if ($pageRecord['_thePathFull'] ?? false) {
            $title = (string)$pageRecord['_thePathFull'];
        } else {
            $title = (string)$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
        }
        // Setting the path of the page
        $pagePath = htmlspecialchars(self::getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.path')) . ': <span class="typo3-docheader-pagePath">';
        // crop the title to title limit (or 50, if not defined)
        $cropLength = empty($GLOBALS['BE_USER']->uc['titleLen']) ? 50 : $GLOBALS['BE_USER']->uc['titleLen'];
        $cropLength = (int)$cropLength;
        $croppedTitle = GeneralUtility::fixed_lgd_cs($title, -$cropLength);
        if ($croppedTitle !== $title) {
            $pagePath .= '<abbr title="' . htmlspecialchars($title) . '">' . htmlspecialchars($croppedTitle) . '</abbr>';
        } else {
            $pagePath .= htmlspecialchars($title);
        }
        $pagePath .= '</span>';
        return $pagePath;
    }

    protected static function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
