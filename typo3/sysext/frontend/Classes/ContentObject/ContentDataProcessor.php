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

namespace TYPO3\CMS\Frontend\ContentObject;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A class that contains methods that can be used to use the dataProcessing functionality
 */
class ContentDataProcessor
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

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
            $processorKeys = ArrayUtility::filterAndSortByNumericKeys($processors);

            foreach ($processorKeys as $key) {
                $dataProcessor = $this->getDataProcessor($processors[$key]);
                $processorConfiguration = $processors[$key . '.'] ?? [];
                $variables = $dataProcessor->process(
                    $cObject,
                    $configuration,
                    $processorConfiguration,
                    $variables
                );
            }
        }

        return $variables;
    }

    private function getDataProcessor(string $serviceName): DataProcessorInterface
    {
        if (!$this->container->has($serviceName)) {
            // assume serviceName is the class name if it is not available in the container
            return $this->instantiateDataProcessor($serviceName);
        }

        $dataProcessor = $this->container->get($serviceName);
        if (!$dataProcessor instanceof DataProcessorInterface) {
            throw new \UnexpectedValueException(
                'Processor with service name "' . $serviceName . '" ' .
                'must implement interface "' . DataProcessorInterface::class . '"',
                1635927108
            );
        }
        return $dataProcessor;
    }

    private function instantiateDataProcessor(string $className): DataProcessorInterface
    {
        if (!class_exists($className)) {
            throw new \UnexpectedValueException('Processor class or service name "' . $className . '" does not exist!', 1427455378);
        }

        if (!in_array(DataProcessorInterface::class, class_implements($className) ?: [], true)) {
            throw new \UnexpectedValueException(
                'Processor with class name "' . $className . '" ' .
                'must implement interface "' . DataProcessorInterface::class . '"',
                1427455377
            );
        }
        return GeneralUtility::makeInstance($className);
    }
}
