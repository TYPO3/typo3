<?php
namespace TYPO3\CMS\Styleguide\UserFunctions;

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
 * Class for a TCA wizard user function used in input_33
 */
class WizardInput33 {

	/**
	 * This method renders a wizard providing JavaScript +/- controls
	 * to increase or decrease an integer value in a field
	 *
	 * @param array $PA parameters of the field
	 * @param object $fObj calling object (TCEform)
	 * @return string HTML for the wizard
	 */
	public function render($PA, $fObj) {
		// Note that the information is passed by reference,
		// so it's possible to manipulate the field directly
		// Here we highlight the field with the color passed as parameter
		$backgroundColor = 'white';
		if (!empty($PA['params']['color'])) {
			$backgroundColor = $PA['params']['color'];
		}
		$PA['item'] = '<div style="background-color: ' . $backgroundColor . '; padding: 4px;">' . $PA['item'] . '</div>';

		// Assemble the wizard itself
		$output = '<div style="margin-top: 8px; margin-left: 4px;">';

		$commonJavascriptCalls = $PA['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] . $PA['fieldChangeFunc']['typo3form.fieldGet'] . ' return false;';
		// Create the + button
		$onClick = "document." . $PA['formName'] . "['" . $PA['itemName'] . "'].value++; " . $commonJavascriptCalls;
		$output .= '<a href="#" onclick="' . htmlspecialchars($onClick) . '" style="padding: 6px; border: 1px solid black; background-color: #999">+</a>';
		// Create the - button
		$onClick = "document." . $PA['formName'] . "['" . $PA['itemName'] . "'].value--; " . $commonJavascriptCalls;
		$output .= '<a href="#" onclick="' . htmlspecialchars($onClick) . '" style="padding: 6px; border: 1px solid black; background-color: #999">-</a>';
		$output .= '</div>';
		return $output;
	}



}