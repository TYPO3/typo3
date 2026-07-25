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

namespace TYPO3\CMS\Backend\View;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\View\RecordIdentity\RecordTypeLabelResolver;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Renders the record identity (type icon, type label, uid).
 *
 * @internal
 */
#[Autoconfigure(public: true)]
readonly class RecordIdentityRenderer
{
    public function __construct(
        private IconFactory $iconFactory,
        private RecordTypeLabelResolver $recordTypeLabelResolver,
    ) {}

    public function render(string $table, array $row): string
    {
        $icon = $this->iconFactory->getIconForRecord($table, $row, IconSize::SMALL)->render();
        $tableTitle = $this->recordTypeLabelResolver->resolve($table, $row);
        $uid = (string)($row['uid'] ?? '');

        $recordType = '<span class="recordidentity-type">' . $icon . htmlspecialchars($tableTitle) . '</span>';

        $debugInfo = $this->getBackendUser()->shallDisplayDebugInformation() ? $table . ':' : '';
        $recordIdentity = sprintf(
            '<small class="recordidentity-id" title="%s">[%s%s]</small>',
            htmlspecialchars($this->getLanguageService()->sL('core.core:labels.uid') . ' ' . $uid),
            htmlspecialchars($debugInfo),
            htmlspecialchars($uid),
        );

        return '<div class="recordidentity">' . $recordType . $recordIdentity . '</div>';
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
