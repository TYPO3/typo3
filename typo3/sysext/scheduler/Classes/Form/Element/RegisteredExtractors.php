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

namespace TYPO3\CMS\Scheduler\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Resource\Index\ExtractorRegistry;

/**
 * Renders registered extractors
 *
 * This is rendered for config type=none, renderType=registeredExtractors
 *
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
final class RegisteredExtractors extends AbstractFormElement
{
    public function __construct(
        private readonly ExtractorRegistry $extractorRegistry
    ) {}

    public function render(): array
    {
        $lang = $this->getLanguageService();
        $extractors = $this->extractorRegistry->getExtractors();

        if ($extractors !== []) {
            $bullets = [];
            foreach ($extractors as $extractor) {
                $bullets[] = sprintf(
                    '<li class="list-group-item" title="%s">%s%s</li>',
                    get_class($extractor),
                    sprintf(
                        $lang->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.fileStorageExtraction.registeredExtractors.extractor'),
                        $this->formatExtractorClassName($extractor),
                        $extractor->getPriority()
                    ),
                    $this->getBackendUser()->shallDisplayDebugInformation() ? (' <code>[' . get_class($extractor) . ']</code>') : ''
                );
            }
            $html = '
                <div class="form-description">' . htmlspecialchars($lang->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.fileStorageExtraction.registeredExtractors.with_extractors')) . '</div>
                <ul class="list-group mt-2">' . implode(LF, $bullets) . '</ul>
            ';
        } else {
            $html = '<div class="alert alert-warning">' . htmlspecialchars($lang->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.fileStorageExtraction.registeredExtractors.without_extractors')) . '/div>';
        }

        $resultArray['html'] = '
            <fieldset>
                <legend class="form-label t3js-formengine-label">
                    ' . htmlspecialchars($lang->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.fileStorageExtraction.registeredExtractors')) . '
                </legend>
                ' . $html . '
            </fieldset>
        ';

        return $resultArray;
    }

    /**
     * Since the class name can be very long considering the namespace, only take the final
     * part for better readability. The FQN of the class will be displayed as tooltip.
     */
    protected function formatExtractorClassName(ExtractorInterface $extractor): string
    {
        $extractorParts = explode('\\', get_class($extractor));
        return (string)array_pop($extractorParts);
    }
}
