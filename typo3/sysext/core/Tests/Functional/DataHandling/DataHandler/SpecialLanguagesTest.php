<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

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

use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;

/**
 * Testing behavior of TCA field configuration 'special' => 'languages'
 */
class SpecialLanguagesTest extends AbstractDataHandlerActionTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->backendUser->workspace = 0;
    }

    /**
     * @param string $value
     * @param string $expected
     *
     * @test
     * @dataProvider allowedLanguagesAreAssignedToBackendUserGroupDataProvider
     */
    public function allowedLanguagesAreAssignedToBackendUserGroup($value, $expected)
    {
        $this->actionService->createNewRecord('be_groups', 0, [
            'title' => 'Testing Group',
            'allowed_languages' => $value,
        ]);

        $row = $this->getDatabaseConnection()
            ->exec_SELECTgetSingleRow(
                'allowed_languages',
                'be_groups',
                'uid=1'
            );
        $this->assertEquals($expected, $row['allowed_languages']);
    }

    /**
     * @return array
     */
    public function allowedLanguagesAreAssignedToBackendUserGroupDataProvider()
    {
        return [
            'valid languages' => ['1,2', '1,2'],
            'default language' => ['0', '0'],
            'empty value' => ['', ''],
            'invalid integer' => ['not-an-integer', ''],
        ];
    }
}
