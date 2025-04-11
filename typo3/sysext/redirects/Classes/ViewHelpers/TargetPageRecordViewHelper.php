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

namespace TYPO3\CMS\Redirects\ViewHelpers;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownUrnException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * The target of a redirect can contain a t3://page link.
 * This ViewHelper checks for such a case and returns the Page ID
 *
 * @internal
 */
final class TargetPageRecordViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('target', 'string', 'The target of the redirect.', true);
    }

    /**
     * Renders the page ID
     */
    public function render(): array
    {
        if (!str_starts_with($this->arguments['target'] ?? '', 't3://page')) {
            return [];
        }
        try {
            $linkService = GeneralUtility::makeInstance(LinkService::class);
            $resolvedLink = $linkService->resolveByStringRepresentation($this->arguments['target']);
            if (!($resolvedLink['pageuid'] ?? '')) {
                return [];
            }
            return BackendUtility::getRecord('pages', $resolvedLink['pageuid']) ?? [];
        } catch (UnknownUrnException|UnknownLinkHandlerException) {
            return [];
        }
    }
}
