<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Configuration\ArrayProcessing;

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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Form\Domain\Configuration\Exception\ArrayProcessorException;

/**
 * Helper for array processing
 *
 * Scope: frontend / backend
 * @internal
 */
class ArrayProcessor
{

    /**
     * @var array
     */
    protected $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = ArrayUtility::flatten($data);
    }

    /**
     * @param ArrayProcessing[] $processings
     * @return array
     */
    public function forEach(...$processings): array
    {
        $result = [];

        $processings = $this->getValidProcessings($processings);
        foreach ($this->data as $key => $value) {
            foreach ($processings as $processing) {
                // explicitly escaping non-escaped '#' which is used
                // as PCRE delimiter in the following processing
                $expression = preg_replace(
                    '/(?<!\\\\)#/',
                    '\\#',
                    $processing->getExpression()
                );

                if (preg_match('#' . $expression . '#', $key, $matches)) {
                    $identifier = $processing->getIdentifier();
                    $processor = $processing->getProcessor();
                    $result[$identifier] = $result[$identifier] ?? [];
                    $result[$identifier][$key] = $processor($key, $value, $matches);
                }
            }
        }

        return $result;
    }

    /**
     * @param array $allProcessings
     * @return ArrayProcessing[]
     * @throws ArrayProcessorException
     */
    protected function getValidProcessings(array $allProcessings): array
    {
        $validProcessings = [];
        $identifiers = [];
        foreach ($allProcessings as $processing) {
            if ($processing instanceof ArrayProcessing) {
                if (in_array($processing->getIdentifier(), $identifiers, true)) {
                    throw new ArrayProcessorException(
                        'ArrayProcessing identifier must be unique.',
                        1528638085
                    );
                }
                $identifiers[] = $processing->getIdentifier();
                $validProcessings[] = $processing;
            }
        }
        return $validProcessings;
    }
}
