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
 * Converter which transforms from different input formats into DateTime objects.
 *
 * Source can be either a string or an array.
 * The date string is expected to be formatted according to DEFAULT_DATE_FORMAT
 * But the default date format can be overridden in the initialize*Action() method like this:
 * $this->arguments['<argumentName>']
 * ->getPropertyMappingConfiguration()
 * ->forProperty('<propertyName>') // this line can be skipped in order to specify the format for all properties
 * ->setTypeConverterOption('Tx_Extbase_Property_TypeConverter_DateTimeConverter', Tx_Extbase_Property_TypeConverter_DateTimeConverter::CONFIGURATION_DATE_FORMAT, '<dateFormat>');
 *
 * If the source is of type array, it is possible to override the format in the source:
 * array(
 * 'date' => '<dateString>',
 * 'dateFormat' => '<dateFormat>'
 * );
 *
 * By using an array as source you can also override time and timezone of the created DateTime object:
 * array(
 * 'date' => '<dateString>',
 * 'hour' => '<hour>', // integer
 * 'minute' => '<minute>', // integer
 * 'seconds' => '<seconds>', // integer
 * 'timezone' => '<timezone>', // string, see http://www.php.net/manual/timezones.php
 * );
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class DateTimeConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var string
	 */
	const CONFIGURATION_DATE_FORMAT = 'dateFormat';
	/**
	 * The default date format is "YYYY-MM-DDT##:##:##+##:##", for example "2005-08-15T15:52:01+00:00"
	 * according to the W3C standard @see http://www.w3.org/TR/NOTE-datetime.html
	 *
	 * @var string
	 */
	const DEFAULT_DATE_FORMAT = \DateTime::W3C;
	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('string', 'array');

	/**
	 * @var string
	 */
	protected $targetType = 'DateTime';

	/**
	 * @var integer
	 */
	protected $priority = 1;

	/**
	 * Empty strings can't be converted
	 *
	 * @param string $source
	 * @param string $targetType
	 * @return boolean
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function canConvertFrom($source, $targetType) {
		if ($targetType !== 'DateTime') {
			return FALSE;
		}
		if (is_array($source)) {
			return TRUE;
		}
		return is_string($source);
	}

	/**
	 * Converts $source to a DateTime using the configured dateFormat
	 *
	 * @param string $source the string to be converted to a DateTime object
	 * @param string $targetType must be "DateTime
	 * @param array $convertedChildProperties not used currently
	 * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
	 * @return DateTime
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		$dateFormat = $this->getDefaultDateFormat($configuration);
		if (is_string($source)) {
			$dateAsString = $source;
		} else {
			if (!isset($source['date']) || !is_string($source['date'])) {
				throw new \TYPO3\CMS\Extbase\Property\Exception\TypeConverterException('Could not convert the given source into a DateTime object because it was not an array with a valid date as a string', 1308003914);
			}
			$dateAsString = $source['date'];
			if (isset($source['dateFormat']) && strlen($source['dateFormat']) > 0) {
				$dateFormat = $source['dateFormat'];
			}
		}
		if ($dateAsString === '') {
			return NULL;
		}
		$date = \DateTime::createFromFormat($dateFormat, $dateAsString);
		if ($date === FALSE) {
			return new \TYPO3\CMS\Extbase\Error\Error(((('The string"' . $dateAsString) . '" could not be converted to DateTime with format "') . $dateFormat) . '"', 1307719788);
		}
		if (is_array($source)) {
			$this->overrideTimeIfSpecified($date, $source);
			$this->overrideTimezoneIfSpecified($date, $source);
		}
		return $date;
	}

	/**
	 * Determines the default date format to use for the conversion.
	 * If no format is specified in the mapping configuration DEFAULT_DATE_FORMAT is used.
	 *
	 * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
	 * @return string
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function getDefaultDateFormat(\TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = NULL) {
		if ($configuration === NULL) {
			return self::DEFAULT_DATE_FORMAT;
		}
		$dateFormat = $configuration->getConfigurationValue('TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\DateTimeConverter', self::CONFIGURATION_DATE_FORMAT);
		if ($dateFormat === NULL) {
			return self::DEFAULT_DATE_FORMAT;
		} elseif ($dateFormat !== NULL && !is_string($dateFormat)) {
			throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException(('CONFIGURATION_DATE_FORMAT must be of type string, "' . (is_object($dateFormat) ? get_class($dateFormat) : gettype($dateFormat))) . '" given', 1307719569);
		}
		return $dateFormat;
	}

	/**
	 * Overrides hour, minute & second of the given date with the values in the $source array
	 *
	 * @param DateTime $date
	 * @param array $source
	 * @return void
	 */
	protected function overrideTimeIfSpecified(\DateTime $date, array $source) {
		if ((!isset($source['hour']) && !isset($source['minute'])) && !isset($source['second'])) {
			return;
		}
		$hour = isset($source['hour']) ? (int) $source['hour'] : 0;
		$minute = isset($source['minute']) ? (int) $source['minute'] : 0;
		$second = isset($source['second']) ? (int) $source['second'] : 0;
		$date->setTime($hour, $minute, $second);
	}

	/**
	 * Overrides timezone of the given date with $source['timezone']
	 *
	 * @param DateTime $date
	 * @param array $source
	 * @return void
	 */
	protected function overrideTimezoneIfSpecified(\DateTime $date, array $source) {
		if (!isset($source['timezone']) || strlen($source['timezone']) === 0) {
			return;
		}
		try {
			$timezone = new \DateTimeZone($source['timezone']);
		} catch (\Exception $e) {
			throw new \TYPO3\CMS\Extbase\Property\Exception\TypeConverterException(('The specified timezone "' . $source['timezone']) . '" is invalid', 1308240974);
		}
		$date->setTimezone($timezone);
	}

}


?>