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

namespace TYPO3\CMS\Core\Configuration\Processor;

use TYPO3\CMS\Core\Configuration\Processor\Placeholder\PlaceholderProcessorInterface;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Orders and returns given PlaceholderProcessors
 */
class PlaceholderProcessorList
{
    /**
     * @var PlaceholderProcessorInterface[]
     */
    protected $processors;

    public function __construct($processorList = [])
    {
        $this->processors = $processorList;
    }

    /**
     * @return PlaceholderProcessorInterface[]
     */
    public function compile(): array
    {
        $processors = [];
        $orderingService = GeneralUtility::makeInstance(DependencyOrderingService::class);
        $orderedProcessors = $orderingService->orderByDependencies($this->processors, 'before', 'after');

        foreach ($orderedProcessors as $processorClassName => $providerConfig) {
            if (isset($providerConfig['disabled']) && $providerConfig['disabled'] === true) {
                continue;
            }

            $processor = GeneralUtility::makeInstance($processorClassName);
            if (!$processor instanceof PlaceholderProcessorInterface) {
                throw new \UnexpectedValueException(
                    'Placeholder processor ' . $processorClassName . ' must implement PlaceholderProcessorInterface',
                    1581343410
                );
            }
            $processors[] = $processor;
        }
        return $processors;
    }
}
