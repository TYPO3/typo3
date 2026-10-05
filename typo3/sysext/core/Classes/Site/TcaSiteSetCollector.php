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

namespace TYPO3\CMS\Core\Site;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\Set\SetError;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
#[Autoconfigure(public: true)]
final readonly class TcaSiteSetCollector
{
    public function __construct(
        private SetRegistry $setRegistry,
        private FlashMessageService $flashMessageService,
    ) {}

    public function populateSiteSets(array &$fieldConfiguration): void
    {
        $currentValue = $fieldConfiguration['row'][$fieldConfiguration['field']] ?? '';
        $selectedSets = $currentValue === '' ? [] : array_fill_keys(GeneralUtility::trimExplode(',', $currentValue), true);

        $hiddenSets = GeneralUtility::trimExplode(',', $this->getBackendUser()->getTSConfig()['options.']['sites.']['hideSets'] ?? '', true);
        foreach ($this->setRegistry->getAllSets() as $set) {
            $hidden = $set->hidden || in_array($set->name, $hiddenSets, true);
            if ($hidden && !isset($selectedSets[$set->name])) {
                continue;
            }
            $fieldConfiguration['items'][] = [
                'label' => $this->getLanguageService()->sL($set->label) . (
                    $hidden ? ' (' . $this->getLanguageService()->sL('core.core:labels.hidden') . ')' : ''
                ),
                'value' => $set->name,
            ];
            unset($selectedSets[$set->name]);
        }

        $invalidSets = $this->setRegistry->getInvalidSets();
        $invalidSetNames = array_keys($selectedSets);
        foreach (array_keys($invalidSets) as $invalidSet) {
            if (!isset($selectedSets[$invalidSet]) && !in_array($invalidSet, $hiddenSets, true)) {
                $invalidSetNames[] = $invalidSet;
            }
        }
        if ($invalidSetNames === []) {
            return;
        }

        $flashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
        $languageService = $this->getLanguageService();
        $fieldConfiguration['items'][] = [
            'label' => $languageService->sL('core.core:labels.invalidSiteSets'),
            'value' => '--div--',
        ];
        foreach ($invalidSetNames as $invalidSet) {
            $reason = $invalidSets[$invalidSet] ?? [
                'error' => SetError::notFound,
                'name' => $invalidSet,
                'context' => 'site:' . ($fieldConfiguration['row']['identifier'] ?? ''),
            ];
            $error = sprintf(
                $languageService->sL($reason['error']->getLabel()),
                $reason['name'],
                $reason['context'],
            );

            $fieldConfiguration['items'][] = [
                'label' => sprintf(
                    $languageService->sL('core.core:labels.noMatchingValue'),
                    $error
                ),
                'value' => $invalidSet,
            ];

            if (!isset($selectedSets[$invalidSet])) {
                continue;
            }
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $error,
                $languageService->sL('core.core:error.site.invalidSetDependencies'),
                ContextualFeedbackSeverity::ERROR,
                false
            );
            $flashMessageQueue->enqueue($flashMessage);
        }
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
