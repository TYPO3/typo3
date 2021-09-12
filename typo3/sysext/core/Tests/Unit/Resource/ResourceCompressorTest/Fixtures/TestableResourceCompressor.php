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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\ResourceCompressorTest\Fixtures;

use TYPO3\CMS\Core\Resource\ResourceCompressor;

class TestableResourceCompressor extends ResourceCompressor
{
    /**
     * @todo Specifying the type in the declaration leads to test bench errors
     */
    protected $targetDirectory = 'typo3temp/var/tests/resource/';

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    public function getHtaccessTemplate(): string
    {
        return $this->htaccessTemplate;
    }
}
