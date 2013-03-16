<?php
namespace TYPO3\CMS\Form\View\Wizard;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Patrick Broens <patrick@patrickbroens.nl>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * The form wizard save view
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class SaveWizardView extends \TYPO3\CMS\Form\View\Wizard\AbstractWizardView {

	/**
	 * The main render method
	 *
	 * Gathers all content and echos it to the screen
	 *
	 * @return void
	 */
	public function render() {
		$success = FALSE;
		// Check if the referenced record is available
		$this->recordIsAvailable = $this->repository->hasRecord();
		if ($this->recordIsAvailable) {
			// Save the data
			$success = $this->repository->save();
		}
		$this->headerOutput($success);
	}

	/**
	 * Construct the reponse header
	 *
	 * @param string $json JSON string
	 * @return void
	 */
	protected function headerOutput($success) {
		if (!$success) {
			header('HTTP/1.1 500 Internal Server Error');
			$jsonArray = array('message' => 'Failed to save the form');
		} else {
			$jsonArray = array('message' => 'Changes saved successfully');
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

?>