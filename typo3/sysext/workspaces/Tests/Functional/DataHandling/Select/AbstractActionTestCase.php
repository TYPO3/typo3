<?php
namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandling\Select;

/**
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

require_once __DIR__ . '/../../../../../core/Tests/Functional/DataHandling/Select/AbstractActionTestCase.php';

/**
 * Functional test for the DataHandler
 */
abstract class AbstractActionTestCase extends \TYPO3\CMS\Core\Tests\Functional\DataHandling\Select\AbstractActionTestCase {

	const VALUE_WorkspaceId = 1;

	/**
	 * @var string
	 */
	protected $scenarioDataSetDirectory = 'typo3/sysext/workspaces/Tests/Functional/DataHandling/Select/DataSet/';

	/**
	 * @var array
	 */
	protected $coreExtensionsToLoad = array(
		'fluid',
		'version',
		'workspaces',
	);

	public function setUp() {
		parent::setUp();
		$this->backendUser->workspace = self::VALUE_WorkspaceId;
	}

}
