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

namespace TYPO3\CMS\Frontend\DataProcessing;

use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Registry for data processors, tagged with "data.processor"
 * @internal
 */
class DataProcessorRegistry
{
    public function __construct(private readonly ServiceLocator $dataProcessorLocator) {}

    public function getDataProcessor(string $identifer): ?DataProcessorInterface
    {
        if (!$this->dataProcessorLocator->has($identifer)) {
            return null;
        }

        $dataProcessor = $this->dataProcessorLocator->get($identifer);
        if (!($dataProcessor instanceof DataProcessorInterface)) {
            throw new \UnexpectedValueException(
                'Processor with alias / identifier "' . $identifer . '" ' .
                'must implement interface "' . DataProcessorInterface::class . '"',
                1666131903
            );
        }

        return $dataProcessor;
    }
}
