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

namespace TYPO3\CMS\Core\Page\Event;

use TYPO3\CMS\Core\Page\AssetCollector;

abstract class AbstractBeforeAssetRenderingEvent
{
    /**
     * @var AssetCollector
     */
    protected $assetCollector;

    /**
     * @var bool
     */
    protected $inline;

    /**
     * @var bool
     */
    protected $priority;

    public function getAssetCollector(): AssetCollector
    {
        return $this->assetCollector;
    }

    public function isInline(): bool
    {
        return $this->inline;
    }

    public function isPriority(): bool
    {
        return $this->priority;
    }
}
