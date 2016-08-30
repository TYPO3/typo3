<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\ActionHandler;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Action handler test
 */
class ActionHandlerTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    protected $coreExtensionsToLoad = ['version', 'workspaces'];

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->setUpBackendUserFromFixture(1);
        \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();
    }

    /**
     * @test
     */
    public function sendToSpecificStageExecuteIgnoresDoublePublishes()
    {
        $actionHandler = new \TYPO3\CMS\Workspaces\ExtDirect\ActionHandler();

        $this->importDataSet(__DIR__ . '/Fixtures/pages.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/sys_workspace.xml');
        $this->importDataSet(__DIR__ . '/Fixtures/tt_content.xml');

        // Prepare parameter
        $parameter = new \stdClass();
        $parameter->additional = '';
        $parameter->receipients = [];
        $parameter->comments = '';

        // Send to LIVE
        $parameter->affects = new \stdClass();
        $parameter->affects->nextStage = -20;
        $parameter->affects->elements = [];

        // First and only affected element
        $elementOne = new \stdClass();
        $elementOne->table = 'tt_content';
        $elementOne->uid = 2;
        $elementOne->t3ver_oid = 1;
        $parameter->affects->elements[] = $elementOne;

        $recordBeforePublish = BackendUtility::getRecord('tt_content', 2);
        $this->assertEquals($recordBeforePublish['header'], 'Workspace version of original content');

        // First publish
        $result = $actionHandler->sendToSpecificStageExecute($parameter);
        $this->assertTrue($result['success']);
        $recordAfterFirstPublish = BackendUtility::getRecord('tt_content', 2);

        $this->assertEquals($recordAfterFirstPublish['t3ver_wsid'], 0);
        $this->assertEquals($recordAfterFirstPublish['header'], 'Original content');

        // Second publish
        $result = $actionHandler->sendToSpecificStageExecute($parameter);
        $this->assertTrue($result['success']);
        $recordAfterSecondPublish = BackendUtility::getRecord('tt_content', 2);

        // In case of an error, this will again be "Workspace version of original content"
        $this->assertEquals($recordAfterSecondPublish['t3ver_wsid'], 0);
        $this->assertEquals($recordAfterSecondPublish['header'], 'Original content');
    }
}
