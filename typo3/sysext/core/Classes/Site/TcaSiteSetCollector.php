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
        foreach ($this->setRegistry->getAllSets() as $set) {
            $fieldConfiguration['items'][] = [
                'label' => $this->getLanguageService()->sL($set->label),
                'value' => $set->name,
            ];
            unset($selectedSets[$set->name]);
        }

        $flashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
        $languageService = $this->getLanguageService();
        foreach ($selectedSets as $invalidSet => $_) {
            $reason = $this->setRegistry->getInvalidSets()[$invalidSet] ?? [
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
                    $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noMatchingValue'),
                    $error
                ),
                'value' => $invalidSet,
            ];

            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $error,
                $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:error.site.invalidSetDependencies'),
                ContextualFeedbackSeverity::ERROR,
                false
            );
            $flashMessageQueue->enqueue($flashMessage);
        }
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
