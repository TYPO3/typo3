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

namespace TYPO3\CMS\Core\Resource;

/**
 * Resources that can be processed with TYPO3's imaging pipeline - a FAL File, a FAL
 * FileReference, or a resource outside the File Abstraction Layer (for example a
 * system resource shipped with a package). FileReference does not implement
 * SystemResourceInterface, so this cannot extend it the way ProcessedResourceInterface
 * does - consumers that need SystemResourceInterface-level members (getImageDimension()
 * and friends) narrow with an instanceof check first, same as for File-specific members.
 *
 * @internal
 */
interface ProcessableFileInterface
{
    /**
     * Returns a modified version of the file.
     *
     * @param string $taskType The task type of this processing
     * @param array $configuration the processing configuration, see manual for that
     */
    public function process(string $taskType, array $configuration): ProcessedFile|ProcessedResource;
}
