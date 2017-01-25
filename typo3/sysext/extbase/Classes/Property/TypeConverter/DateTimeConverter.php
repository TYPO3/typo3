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
 * Source can be either a string or an array. The date string is expected to be formatted
 * according to DEFAULT_DATE_FORMAT.
 *
 * But the default date format can be overridden in the initialize*Action() method like this::
 *
 *  $this->arguments['<argumentName>']
 *    ->getPropertyMappingConfiguration()
 *    ->forProperty('<propertyName>') // this line can be skipped in order to specify the format for all properties
 *    ->setTypeConverterOption(\TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter::class, \TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter::CONFIGURATION_DATE_FORMAT, '<dateFormat>');
 *
 * If the source is of type array, it is possible to override the format in the source::
 *
 *  array(
 *   'date' => '<dateString>',
 *   'dateFormat' => '<dateFormat>'
 *  );
 *
 * By using an array as source you can also override time and timezone of the created DateTime object::
 *
 *  array(
 *   'date' => '<dateString>',
 *   'hour' => '<hour>', // integer
 *   'minute' => '<minute>', // integer
 *   'seconds' => '<seconds>', // integer
 *   'timezone' => '<timezone>', // string, see http://www.php.net/manual/timezones.php
 *  );
 *
 * As an alternative to providing the date as string, you might supply day, month and year as array items each::
 *
 *  array(
 *   'day' => '<day>', // integer
 *   'month' => '<month>', // integer
 *   'year' => '<year>', // integer
 *  );
 *
 * @api
 */
class DateTimeConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter
{
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
    protected $sourceTypes = ['string', 'integer', 'array'];

    /**
     * @var string
     */
    protected $targetType = 'DateTime';

    /**
     * @var int
     */
    protected $priority = 1;

    /**
     * If conversion is possible.
     *
     * @param string $source
     * @param string $targetType
     * @return bool
     */
    public function canConvertFrom($source, $targetType)
    {
        if (!is_callable([$targetType, 'createFromFormat'])) {
            return false;
        }
        if (is_array($source)) {
            return true;
        }
        if (is_int($source)) {
            return true;
        }
        return is_string($source);
    }

    /**
     * Converts $source to a \DateTime using the configured dateFormat
     *
     * @param string|int|array $source the string to be converted to a \DateTime object
     * @param string $targetType must be "DateTime"
     * @param array $convertedChildProperties not used currently
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return \DateTime
     * @throws \TYPO3\CMS\Extbase\Property\Exception\TypeConverterException
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        $dateFormat = $this->getDefaultDateFormat($configuration);
        if (is_string($source)) {
            $dateAsString = $source;
        } elseif (is_int($source)) {
            $dateAsString = strval($source);
        } else {
            if (isset($source['date']) && is_string($source['date'])) {
                $dateAsString = $source['date'];
            } elseif (isset($source['date']) && is_int($source['date'])) {
                $dateAsString = strval($source['date']);
            } elseif ($this->isDatePartKeysProvided($source)) {
                if ($source['day'] < 1 || $source['month'] < 1 || $source['year'] < 1) {
                    return new \TYPO3\CMS\Extbase\Error\Error('Could not convert the given date parts into a DateTime object because one or more parts were 0.', 1333032779);
                }
                $dateAsString = sprintf('%d-%d-%d', $source['year'], $source['month'], $source['day']);
            } else {
                throw new \TYPO3\CMS\Extbase\Property\Exception\TypeConverterException('Could not convert the given source into a DateTime object because it was not an array with a valid date as a string', 1308003914);
            }
            if (isset($source['dateFormat']) && $source['dateFormat'] !== '') {
                $dateFormat = $source['dateFormat'];
            }
        }
        if ($dateAsString === '') {
            return null;
        }
        if (ctype_digit($dateAsString) && $configuration === null && (!is_array($source) || !isset($source['dateFormat']))) {
            $dateFormat = 'U';
        }
        if (is_array($source) && isset($source['timezone']) && (string)$source['timezone'] !== '') {
            try {
                $timezone = new \DateTimeZone($source['timezone']);
            } catch (\Exception $e) {
                throw new \TYPO3\CMS\Extbase\Property\Exception\TypeConverterException('The specified timezone "' . $source['timezone'] . '" is invalid.', 1308240974);
            }
            $date = $targetType::createFromFormat($dateFormat, $dateAsString, $timezone);
        } else {
            $date = $targetType::createFromFormat($dateFormat, $dateAsString);
        }
        if ($date === false) {
            return new \TYPO3\CMS\Extbase\Validation\Error('The date "%s" was not recognized (for format "%s").', 1307719788, [$dateAsString, $dateFormat]);
        }
        if (is_array($source)) {
            $this->overrideTimeIfSpecified($date, $source);
        }
        return $date;
    }

    /**
     * Returns whether date information (day, month, year) are present as keys in $source.
     *
     * @param array $source
     * @return bool
     */
    protected function isDatePartKeysProvided(array $source)
    {
        return isset($source['day']) && ctype_digit($source['day'])
            && isset($source['month']) && ctype_digit($source['month'])
            && isset($source['year']) && ctype_digit($source['year']);
    }

    /**
     * Determines the default date format to use for the conversion.
     * If no format is specified in the mapping configuration DEFAULT_DATE_FORMAT is used.
     *
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return string
     * @throws \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException
     */
    protected function getDefaultDateFormat(\TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        if ($configuration === null) {
            return self::DEFAULT_DATE_FORMAT;
        }
        $dateFormat = $configuration->getConfigurationValue(\TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter::class, self::CONFIGURATION_DATE_FORMAT);
        if ($dateFormat === null) {
            return self::DEFAULT_DATE_FORMAT;
        } elseif ($dateFormat !== null && !is_string($dateFormat)) {
            throw new \TYPO3\CMS\Extbase\Property\Exception\InvalidPropertyMappingConfigurationException('CONFIGURATION_DATE_FORMAT must be of type string, "' . (is_object($dateFormat) ? get_class($dateFormat) : gettype($dateFormat)) . '" given', 1307719569);
        }
        return $dateFormat;
    }

    /**
     * Overrides hour, minute & second of the given date with the values in the $source array
     *
     * @param \DateTime $date
     * @param array $source
     * @return void
     */
    protected function overrideTimeIfSpecified(\DateTime $date, array $source)
    {
        if (!isset($source['hour']) && !isset($source['minute']) && !isset($source['second'])) {
            return;
        }
        $hour = isset($source['hour']) ? (int)$source['hour'] : 0;
        $minute = isset($source['minute']) ? (int)$source['minute'] : 0;
        $second = isset($source['second']) ? (int)$source['second'] : 0;
        $date->setTime($hour, $minute, $second);
    }
}
