<?php
namespace TYPO3\CMS\Extbase\Tests\Fixture;

/*                                                                        *
* This script belongs to the Extbase framework.                          *
*                                                                        *
* It is free software; you can redistribute it and/or modify it under    *
* the terms of the GNU Lesser General Public License as published by the *
* Free Software Foundation, either version 3 of the License, or (at your *
* option) any later version.                                             *
*                                                                        *
* This script is distributed in the hope that it will be useful, but     *
* WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
* TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
* General Public License for more details.                               *
*                                                                        *
* You should have received a copy of the GNU Lesser General Public       *
* License along with the script.                                         *
* If not, see http://www.gnu.org/licenses/lgpl.html                      *
*                                                                        *
* The TYPO3 project - inspiring people to share!                         *
*                                                                        */
/**
* A dummy class which extends ConfigurationManager class and access old property $concreteConfigurationManager for backward compatibility tests.
*
* @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
*/
class ClassExtendingConfigurationManager extends \TYPO3\CMS\Extbase\Configuration\ConfigurationManager {

	/**
	 * @return \TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager
	 */
	public function getConcreteConfigurationManager() {
		return $this->concreteConfigurationManager;
	}

}

?>
