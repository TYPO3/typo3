<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\FlexformIrre;

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

class ActionTestCase extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase
{

    /**
     * @var array
     */
    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/core/Tests/Functional/DataHandling/FlexformIrre/Fixtures/fileadmin' => 'fileadmin/fixture',
    ];

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/FlexformIrre/DataSet/';

    protected function setUp()
    {
        $this->testExtensionsToLoad[] = 'typo3/sysext/version/';
        $this->testExtensionsToLoad[] = 'typo3/sysext/workspaces/';

        parent::setUp();
        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');
    }

    /**
     * @test
     */
    public function newVersionOfFileRelationInFlexformFieldIsCreatedOnSave()
    {
        $this->backendUser->workspace = 1;
        $GLOBALS['BE_USER']->workspace = 1;
        $this->getActionService()->modifyRecords(1, [
            //'sys_file_reference' => ['uid' => 10, 'hidden' => 0],
            'tt_content' => ['uid' => 100, 'header' => 'Content #1 (WS)']
        ]);

        // there should be one relation in the live WS and one in the draft WS pointing to the file field.
        $this->assertEquals(2, $this->getDatabaseConnection()->exec_SELECTcountRows('uid', 'sys_file_reference', 'uid_local = 20'));
    }
}
