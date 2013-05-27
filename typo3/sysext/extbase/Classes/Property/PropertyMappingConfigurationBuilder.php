<?php

/*                                                                        *
 * This script belongs to the Extbase framework                           *
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
 * This builder creates the default configuration for Property Mapping, if no configuration has been passed to the Property Mapper.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Extbase_Property_PropertyMappingConfigurationBuilder implements t3lib_Singleton {

	/**
	 * Builds the default property mapping configuration.
	 *
	 * @param string $type the implementation class name of the PropertyMappingConfiguration to instanciate; must be a subclass of Tx_Extbase_Property_PropertyMappingConfiguration
	 * @return Tx_Extbase_Property_PropertyMappingConfiguration
	 */
	public function build($type = 'Tx_Extbase_Property_PropertyMappingConfiguration') {
		$configuration = new $type();

		$configuration->setTypeConverterOptions('Tx_Extbase_Property_TypeConverter_PersistentObjectConverter', array(
			Tx_Extbase_Property_TypeConverter_PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE,
			Tx_Extbase_Property_TypeConverter_PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED => TRUE
		));

		return $configuration;
	}
}
?>