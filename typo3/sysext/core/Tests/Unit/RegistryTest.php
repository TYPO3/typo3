<?php
namespace TYPO3\CMS\Core\Tests\Unit;

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

use TYPO3\CMS\Core\Registry;

/**
 * Test case
 */
class RegistryTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function getThrowsExceptionForInvalidNamespacesUsingNoNamespace()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1249755131);
        (new Registry())->get('', 'someKey');
    }

    /**
     * @test
     */
    public function getThrowsExceptionForInvalidNamespacesUsingTooShortNamespace()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1249755131);
        (new Registry())->get('t', 'someKey');
    }

    /**
     * @test
     */
    public function setThrowsAnExceptionOnEmptyNamespace()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1249755131);
        (new Registry())->set('', 'someKey', 'someValue');
    }

    /**
     * @test
     */
    public function setThrowsAnExceptionOnWrongNamespace()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1249755131);
        (new Registry())->set('t', 'someKey', 'someValue');
    }

    /**
     * @test
     */
    public function removeThrowsAnExceptionOnWrongNamespace()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1249755131);
        (new Registry())->remove('t', 'someKey');
    }

    /**
     * @test
     */
    public function removeAllByNamespaceThrowsAnExceptionOnWrongNamespace()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1249755131);
        (new Registry())->removeAllByNamespace('');
    }
}
