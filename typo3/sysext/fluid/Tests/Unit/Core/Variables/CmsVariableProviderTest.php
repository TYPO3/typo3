<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\Variables;

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
use TYPO3\CMS\Fluid\Core\Variables\CmsVariableProvider;

/**
 * Test case
 */
class CmsVariableProviderTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function getByPathDelegatesToObjectAccess()
    {
        $instance = new CmsVariableProvider();
        $instance->setSource(array('foo' => 'bar'));
        $this->assertEquals('bar', $instance->getByPath('foo'));
    }
}
