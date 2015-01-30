<?php
namespace TYPO3\CMS\Form\Validation;

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
 * Date rule
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class DateValidator extends \TYPO3\CMS\Form\Validation\AbstractValidator {

	/**
	 * Constant for localisation
	 *
	 * @var string
	 */
	const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_date';

	/**
	 * strftime format for date
	 *
	 * @var string
	 */
	protected $format;

	/**
	 * Constructor
	 *
	 * @param array $arguments Typoscript configuration
	 */
	public function __construct($arguments) {
		$this->setFormat($arguments['format']);
		parent::__construct($arguments);
	}

	/**
	 * Returns TRUE if submitted value validates according to rule
	 *
	 * @return boolean
	 * @see \TYPO3\CMS\Form\Validation\ValidatorInterface::isValid()
	 */
	public function isValid() {
		if ($this->requestHandler->has($this->fieldName)) {
			$value = $this->requestHandler->getByMethod($this->fieldName);
			if (function_exists('strptime')) {
				$parsedDate = strptime($value, $this->format);
				$parsedDateYear = $parsedDate['tm_year'] + 1900;
				$parsedDateMonth = $parsedDate['tm_mon'] + 1;
				$parsedDateDay = $parsedDate['tm_mday'];
				return checkdate($parsedDateMonth, $parsedDateDay, $parsedDateYear);
			} else {
				// %a => D : An abbreviated textual representation of the day (conversion works only for english)
				// %A => l : A full textual representation of the day (conversion works only for english)
				// %d => d : Day of the month, 2 digits with leading zeros
				// %e => j : Day of the month, 2 digits without leading zeros
				// %j => z : Day of the year, 3 digits with leading zeros
				// %b => M : Abbreviated month name, based on the locale (conversion works only for english)
				// %B => F : Full month name, based on the locale (conversion works only for english)
				// %h => M : Abbreviated month name, based on the locale (an alias of %b) (conversion works only for english)
				// %m => m : Two digit representation of the month
				// %y => y : Two digit representation of the year
				// %Y => Y : Four digit representation for the year
				$dateTimeFormat = str_replace(
					array('%a', '%A', '%d', '%e', '%j', '%b', '%B', '%h', '%m', '%y', '%Y'),
					array('D', 'l', 'd', 'j', 'z', 'M', 'F', 'M', 'm', 'y', 'Y'),
					$this->format
				);
				$dateTimeObject = date_create_from_format($dateTimeFormat, $value);
				if ($dateTimeObject === FALSE) {
					return FALSE;
				}

				return $value === $dateTimeObject->format($dateTimeFormat);
			}
		}
		return TRUE;
	}

	/**
	 * Set the format of the date
	 *
	 * @param string $format strftime format
	 * @return Rule object
	 */
	public function setFormat($format) {
		if ($format === NULL) {
			$this->format = '%e-%m-%Y';
		} else {
			$this->format = (string) $format;
		}
		return $this;
	}

	/**
	 * Substitute makers in the message text
	 * Overrides the abstract
	 *
	 * @param string $message Message text with markers
	 * @return string Message text with substituted markers
	 */
	protected function substituteValues($message) {
		$humanReadableDateFormat = $this->humanReadableDateFormat($this->format);
		$message = str_replace('%format', $humanReadableDateFormat, $message);
		return $message;
	}

	/**
	 * Converts strftime date format to human readable format
	 * according to local language.
	 *
	 * Example for default language: %e-%m-%Y becomes d-mm-yyyy
	 *
	 * @param string $format strftime format
	 * @return string Human readable format
	 */
	protected function humanReadableDateFormat($format) {
		$label = get_class($this) . '.strftime.';
		$pairs = array(
			'%A' => $this->localizationHandler->getLocalLanguageLabel($label . 'A'),
			'%a' => $this->localizationHandler->getLocalLanguageLabel($label . 'a'),
			'%d' => $this->localizationHandler->getLocalLanguageLabel($label . 'd'),
			'%e' => $this->localizationHandler->getLocalLanguageLabel($label . 'e'),
			'%B' => $this->localizationHandler->getLocalLanguageLabel($label . 'B'),
			'%b' => $this->localizationHandler->getLocalLanguageLabel($label . 'b'),
			'%m' => $this->localizationHandler->getLocalLanguageLabel($label . 'm'),
			'%Y' => $this->localizationHandler->getLocalLanguageLabel($label . 'Y'),
			'%y' => $this->localizationHandler->getLocalLanguageLabel($label . 'y'),
			'%H' => $this->localizationHandler->getLocalLanguageLabel($label . 'H'),
			'%I' => $this->localizationHandler->getLocalLanguageLabel($label . 'I'),
			'%M' => $this->localizationHandler->getLocalLanguageLabel($label . 'M'),
			'%S' => $this->localizationHandler->getLocalLanguageLabel($label . 'S')
		);
		$humanReadableFormat = str_replace(array_keys($pairs), array_values($pairs), $format);
		return $humanReadableFormat;
	}

}
