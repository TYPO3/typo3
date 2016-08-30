<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProvider\UserTsConfig;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class UserTsConfigTest extends UnitTestCase
{
    /**
     * @var UserTsConfig
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new UserTsConfig();
    }

    /**
     * @test
     */
    public function addDataSetsUserTypoScriptInResult()
    {
        $expected = ['foo'];
        $GLOBALS['BE_USER'] = new \stdClass();
        $GLOBALS['BE_USER']->userTS = $expected;
        $result = $this->subject->addData([]);
        $this->assertEquals($expected, $result['userTsConfig']);
    }
}
