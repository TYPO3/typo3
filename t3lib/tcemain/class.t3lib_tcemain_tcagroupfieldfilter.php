<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2012 Ingmar Schlecht
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Filter class for use within TCA "group" fields.
 * This can be configured in the TCA of "group" fields to filter out relations based on the foreign record's values
 */

class t3lib_tcemain_tcaGroupFieldFilter {


	/**
	 * User function to be used by TCA fields as filter for "group" fields.
	 *
	 * This can be used to limit the possible values in DB relation "group" fields to only allow relations to foreign
	 * records matching a set of configured criteria. For now, this function only supports the "equals" criteria,
	 * which asserts that a field value equals a specific value, but others could be added later on.
	 *
	 * @param $parameters
	 * @param $tceMainObject
	 * @return array
	 */
	public function filterByFieldValues($parameters, $tceMainObject) {
		$fieldValues = $parameters['values'];
		$tcaFieldConfig = $parameters['tcaFieldConfig'];
		$cleanFieldValues = array();
		$foreign_table = $tcaFieldConfig['allowed'];

		foreach ($fieldValues as $fieldValue) {

			$parts = t3lib_div::revExplode('_', $fieldValue, 2);
			$fieldValueNumeric = $parts[1];

			$foreign_row = t3lib_BEfunc::getRecord($foreign_table, $fieldValueNumeric);

			$itemAllowed = true;

			foreach ($parameters['criteria'] as $criterion) {
				list($criterionField, $criterionType, $criterionValue) = $criterion;
				switch ($criterionType) {
					case 'equals':
						if(strcmp($foreign_row[$criterionField],$criterionValue)) {
							$itemAllowed = false;
						}
						break;
					// TODO: Add other criterion types such as "isset", "greater than" or "less than"
					// TODO/Note: Consider if it would be better to implement this whole thing via a WHERE clause to allow any type of comparison
				}
			}

			if($itemAllowed) {
				$cleanFieldValues[] = $fieldValue;
			}

		}

		return $cleanFieldValues;
	}

}

?>