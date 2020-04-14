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

namespace TYPO3\CMS\Frontend\Aspect;

use TYPO3\CMS\Core\Context\AspectInterface;
use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;

/**
 * The aspect contains information on whether TYPO3 is currently in preview mode
 *
 * Allowed properties:
 * - isPreview
 */
class PreviewAspect implements AspectInterface
{

    /**
     * @var bool
     */
    private $isPreview;

    public function __construct(bool $isPreview = false)
    {
        $this->isPreview = $isPreview;
    }

    public function isPreview(): bool
    {
        return $this->isPreview;
    }

    /**
     * Get a property from aspect
     *
     * @param string $name
     * @return mixed
     * @throws AspectPropertyNotFoundException
     */
    public function get(string $name)
    {
        switch ($name) {
            case 'isPreview':
                return $this->isPreview;
        }
        throw new AspectPropertyNotFoundException('Property "' . $name . '" not found in Aspect "' . __CLASS__ . '".', 1563375558);
    }
}
