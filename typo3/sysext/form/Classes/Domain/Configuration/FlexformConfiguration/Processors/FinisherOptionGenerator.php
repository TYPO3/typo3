<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Configuration\FlexformConfiguration\Processors;

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

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generate a FlexForm element for a finisher option
 *
 * @internal
 */
class FinisherOptionGenerator extends AbstractProcessor
{
    /**
     * @var \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected $languageService;

    /**
     * @param ProcessorDto $converterDto
     */
    public function __construct(ProcessorDto $converterDto)
    {
        parent::__construct($converterDto);

        $this->languageService = GeneralUtility::makeInstance(LanguageService::class);
        $this->languageService->includeLLFile('EXT:form/Resources/Private/Language/Database.xlf');
    }

    /**
     * @param string $_ unused in this context
     * @param mixed $__ unused in this context
     * @param array $matches the expression matches from the ArrayProcessor - for example matches of ^(.*)\.config\.type$
     */
    public function __invoke(string $_, $__, array $matches)
    {
        [, $optionKey] = $matches;

        $finisherIdentifier = $this->converterDto->getFinisherIdentifier();
        $finisherDefinitionFromSetup = $this->converterDto->getFinisherDefinitionFromSetup();
        $finisherDefinitionFromFormDefinition = $this->converterDto->getFinisherDefinitionFromFormDefinition();

        try {
            $elementConfiguration = ArrayUtility::getValueByPath(
                $finisherDefinitionFromSetup['FormEngine']['elements'],
                $optionKey,
                '.'
            );
        } catch (MissingArrayPathException $exception) {
            return;
        }

        // use the option value from the ext:form setup from the current finisher as default value
        try {
            $optionValue = ArrayUtility::getValueByPath(
                $finisherDefinitionFromSetup,
                sprintf('options.%s', $optionKey),
                '.'
            );
        } catch (MissingArrayPathException $exception) {
            $optionValue = null;
        }

        // use the option value from the form definition from the current finisher (if exists) as default value
        try {
            $optionValue = ArrayUtility::getValueByPath(
                $finisherDefinitionFromFormDefinition,
                sprintf('options.%s', $optionKey),
                '.'
            );
        } catch (MissingArrayPathException $exception) {
        }

        if (empty($optionValue)) {
            $elementConfiguration['label'] .= sprintf(' (%s: "%s")', $this->languageService->getLL('default'), $this->languageService->getLL('empty'));
        } else {
            $elementConfiguration['label'] .= sprintf(' (%s: "' . $optionValue . '")', $this->languageService->getLL('default'));
        }

        $elementConfiguration['config']['default'] = $optionValue;

        $sheetElements = $this->converterDto->getResult();
        $sheetElements['settings.finishers.' . $finisherIdentifier . '.' . $optionKey]['TCEforms'] = $elementConfiguration;

        $this->converterDto->setResult($sheetElements);
    }
}
