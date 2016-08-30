<?php
namespace TYPO3\CMS\Extbase\Property\TypeConverter;

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
 * Converter which transforms simple types to a string.
 *
 * @api
 */
class StringConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = ['string', 'integer'];

    /**
     * @var string
     */
    protected $targetType = 'string';

    /**
     * @var int
     */
    protected $priority = 1;

    /**
     * Actually convert from $source to $targetType, taking into account the fully
     * built $convertedChildProperties and $configuration.
     *
     * @param string $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return string
     * @api
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        return (string)$source;
    }
}
