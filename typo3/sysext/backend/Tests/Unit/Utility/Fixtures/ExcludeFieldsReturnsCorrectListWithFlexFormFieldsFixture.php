<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Utility\Fixtures;

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
 * Disable getRecordWSOL and getRecordTitle dependency by returning stable results
 */
class ExcludeFieldsReturnsCorrectListWithFlexFormFieldsFixture extends \TYPO3\CMS\Backend\Utility\BackendUtility {

	/**
	 * @param string $table
	 * @return array
	 */
	static public function getRegisteredFlexForms($table) {
		static $called = 0;
		++$called;
		if ($called === 1) {
			return array();
		}
		if ($called === 2) {
			if ($table !== 'tx_foo') {
				throw new Exception('Expected tx_foo as argument on call 2', 1399638572);
			}
			$parsedFlexForm = array(
				'abarfoo' => array(
					'dummy' => array(
						'title' => 'dummy',
						'ds' => array(
							'sheets' => array(
								'sGeneral' => array(
									'ROOT' => array(
										'type' => 'array',
										'el' => array(
											'xmlTitle' => array(
												'TCEforms' => array(
													'exclude' => 1,
													'label' => 'The Title:',
													'config' => array(
														'type' => 'input',
														'size' => 48,
													),
												),
											),
										),
									),
								),
							),
						),
					),
				),
			);
			return $parsedFlexForm;
		}
		if ($called === 3) {
			return array();
		}
	}
}