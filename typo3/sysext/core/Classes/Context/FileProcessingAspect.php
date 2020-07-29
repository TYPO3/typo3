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

namespace TYPO3\CMS\Core\Context;

use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;

/**
 * The aspect contains the processed file representation that is requested to process locally.
 *
 * Allowed properties:
 * - file
 */
class FileProcessingAspect implements AspectInterface
{
    /**
     * @var bool
     */
    private $deferProcessing;

    public function __construct(bool $deferProcessing = true)
    {
        $this->deferProcessing = $deferProcessing;
    }

    /**
     * Fetch the values
     *
     * @param string $name
     * @return bool
     * @throws AspectPropertyNotFoundException
     */
    public function get(string $name)
    {
        if ($name === 'deferProcessing') {
            return $this->deferProcessing;
        }
        throw new AspectPropertyNotFoundException('Property "' . $name . '" not found in Aspect "' . __CLASS__ . '".', 1599164743);
    }

    public function isProcessingDeferred(): bool
    {
        return $this->deferProcessing;
    }
}
