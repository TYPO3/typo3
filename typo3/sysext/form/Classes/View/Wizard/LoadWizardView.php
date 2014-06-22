<?php
namespace TYPO3\CMS\Form\View\Wizard;

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

/**
 * The form wizard load view
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class LoadWizardView extends \TYPO3\CMS\Form\View\Wizard\AbstractWizardView {

	/**
	 * The main render method
	 *
	 * Gathers all content and echos it to the screen
	 *
	 * @return void
	 */
	public function render() {
		$jsonObject = $this->repository->getRecordAsJson();
		$this->headerOutput($jsonObject);
	}

	/**
	 * Construct the reponse header
	 *
	 * @param mixed $jsonObject JSON string, FALSE if not succeeded
	 * @return void
	 */
	protected function headerOutput($jsonObject) {
		if (!$jsonObject) {
			header('HTTP/1.1 500 Internal Server Error');
			$jsonArray = array('message' => 'Failed to save the form');
		} else {
			$jsonArray = array('configuration' => $jsonObject);
		}
		$json = json_encode($jsonArray);
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		header('Content-Length: ' . strlen($json));
		header('Content-Type: application/json; charset=utf-8');
		header('Content-Transfer-Encoding: 8bit');
		echo $json;
		die;
	}

}
