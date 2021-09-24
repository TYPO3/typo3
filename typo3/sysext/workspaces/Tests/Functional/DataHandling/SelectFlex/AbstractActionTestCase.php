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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\SelectFlex;

abstract class AbstractActionTestCase extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\SelectFlex\AbstractActionTestCase
{
    const VALUE_WorkspaceId = 1;

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['workspaces'];

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/SelectFlex/DataSet/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setWorkspaceId(self::VALUE_WorkspaceId);
    }
}
