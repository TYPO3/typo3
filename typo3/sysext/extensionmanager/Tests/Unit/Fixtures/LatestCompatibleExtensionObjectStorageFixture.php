<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Fixtures;

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

/**
 * Latest compatible extension object storage fixture
 */
class LatestCompatibleExtensionObjectStorageFixture
{
    /**
     * @var array
     */
    public $extensions = [];

    /**
     * @return \TYPO3\CMS\Extensionmanager\Domain\Model\Extension
     */
    public function getFirst()
    {
        return $this->extensions[0];
    }
}
