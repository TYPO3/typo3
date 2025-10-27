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

namespace TYPO3\CMS\Core\DataHandling;

use TYPO3\CMS\Core\Site\Entity\SiteInterface;

/**
 * Context object for ItemsProcessor implementations.
 * Encapsulates all parameters needed for item processing to avoid parameter bloat.
 */
final readonly class ItemsProcessorContext
{
    public function __construct(
        public string $table,
        public string $field,
        public array $row,
        public array $fieldConfiguration,
        public array $processorParameters,
        public int $realPid,
        public SiteInterface $site,
        public array $fieldTSconfig = [],
        public array $additionalParameters = [],
    ) {}
}
