<?php
namespace TYPO3\CMS\Frontend\ContentObject;

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

use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A class that contains methods that can be used to use the dataProcessing functionality
 */
class ContentDataProcessor
{
    /**
     * Check for the availability of processors, defined in TypoScript, and use them for data processing
     *
     * @param ContentObjectRenderer $cObject
     * @param array $configuration Configuration array
     * @param array $variables the variables to be processed
     * @return array the processed data and variables as key/value store
     * @throws \UnexpectedValueException If a processor class does not exist
     */
    public function process(ContentObjectRenderer $cObject, array $configuration, array $variables)
    {
        if (
            !empty($configuration['dataProcessing.'])
            && is_array($configuration['dataProcessing.'])
        ) {
            $processors = $configuration['dataProcessing.'];
            $processorKeys = TemplateService::sortedKeyList($processors);

            foreach ($processorKeys as $key) {
                $className = $processors[$key];
                if (!class_exists($className)) {
                    throw new \UnexpectedValueException('Processor class name "' . $className . '" does not exist!', 1427455378);
                }

                if (!in_array(DataProcessorInterface::class, class_implements($className), true)) {
                    throw new \UnexpectedValueException(
                        'Processor with class name "' . $className . '" ' .
                        'must implement interface "' . DataProcessorInterface::class . '"',
                        1427455377
                    );
                }

                $processorConfiguration = isset($processors[$key . '.']) ? $processors[$key . '.'] : [];

                $variables = GeneralUtility::makeInstance($className)->process(
                    $cObject,
                    $configuration,
                    $processorConfiguration,
                    $variables
                );
            }
        }

        return $variables;
    }
}
