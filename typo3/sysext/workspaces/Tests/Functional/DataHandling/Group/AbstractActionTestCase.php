<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Group;

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
 * Functional test for the DataHandler
 */
abstract class AbstractActionTestCase extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\Group\AbstractActionTestCase
{
    const VALUE_WorkspaceId = 1;

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/Group/DataSet/';

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = [
        'fluid',
        'version',
        'workspaces',
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->backendUser->workspace = self::VALUE_WorkspaceId;
    }
}
