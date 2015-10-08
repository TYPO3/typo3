<?php
namespace TYPO3\CMS\Jumpurl\Tests\Unit;

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

use TYPO3\CMS\Jumpurl\JumpUrlProcessor;

/**
 * Testcase for handling jump URLs when given with a test parameter
 */
class JumpUrlProcessorMock extends JumpUrlProcessor
{
    /**
     * Makes the parent getParametersForSecureFile() method accessible.
     *
     * @param string $jumpUrl
     * @param array $configuration
     * @return array
     */
    public function getParametersForSecureFile($jumpUrl, array $configuration)
    {
        return parent::getParametersForSecureFile($jumpUrl, $configuration);
    }
}
