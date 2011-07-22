<?php
declare(encoding = 'utf-8');

/***************************************************************
*  Copyright notice
*
*  (c) 2008 Patrick Broens (patrick@patrickbroens.nl)
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
 * Date rule
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_system_validate_date extends tx_form_system_validate_abstract {

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
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct($arguments) {
		$this->setFormat($arguments['format']);

		parent::__construct($arguments);
	}

	/**
	 * Returns TRUE if submitted value validates according to rule
	 *
	 * @return boolean
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 * @see typo3/sysext/form/interfaces/tx_form_system_validate_interface#isValid()
	 */
	public function isValid() {
		if($this->requestHandler->has($this->fieldName)) {
			$value = $this->requestHandler->getByMethod($this->fieldName);

			$parsedDate = strptime($value, $this->format);

			$parsedDateYear = $parsedDate['tm_year'] + 1900;
			$parsedDateMonth = $parsedDate['tm_mon'] + 1;
			$parsedDateDay = $parsedDate['tm_mday'];

			return checkdate($parsedDateMonth, $parsedDateDay, $parsedDateYear);
		}
		return TRUE;
	}

	/**
	 * Set the format of the date
	 *
	 * @param string $format strftime format
	 * @return Rule object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function setFormat($format) {
		if($format === NULL) {
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
	 * @author Patrick Broens <patrick@patrickbroens.nl>
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
	 * @author Patrick Broens <patrick@patrickbroens.nl>
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
			'%S' => $this->localizationHandler->getLocalLanguageLabel($label . 'S'),
		);

		$humanReadableFormat = str_replace(array_keys($pairs), array_values($pairs), $format);
		return $humanReadableFormat;
	}
}

/**
 * Replacement for strptime
 *
 * The function strptime does not exist on PHP < 5.1.0RC1 and Windows
 * This function tries to do the same
 *
 * @param string $value Date input from user
 * @param string $format strftime format
 * @return array strptime formatted date
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @see http://php.net/manual/en/function.strptime.php
 */
if(!function_exists('strptime')) {
	function strptime($value, $format) {
		static $expand = array(
			'%D' => '%m/%d/%y',
			'%T' => '%H:%M:%S',
		);

		static $mapConversionSpecifiers = array(
			'%S'=>'tm_sec',
			'%M'=>'tm_min',
			'%H'=>'tm_hour',
			'%d'=>'tm_mday',
			'%e'=>'tm_mday',
			'%m'=>'tm_mon',
			'%Y'=>'tm_year',
			'%y'=>'tm_year',
			'%W'=>'tm_wday',
			'%D'=>'tm_yday',
			'%u'=>'unparsed',
		);

		static $names = array(
			'Jan' => 1,
			'Feb' => 2,
			'Mar' => 3,
			'Apr' => 4,
			'May' => 5,
			'Jun' => 6,
			'Jul' => 7,
			'Aug' => 8,
			'Sep' => 9,
			'Oct' => 10,
			'Nov' => 11,
			'Dec' => 12,
			'Sun' => 0,
			'Mon' => 1,
			'Tue' => 2,
			'Wed' => 3,
			'Thu' => 4,
			'Fri' => 5,
			'Sat' => 6,
		);

		$format = str_replace(array_keys($expand), array_values($expand), $format);
		$preg = preg_replace('/(%\w)/', '(\w+)', preg_quote($format));
		preg_match_all('/(%\w)/', $format, $positions);
		$positions = $positions[1];

		if(preg_match("#$preg#", "$value", $extracted)) {
			foreach($positions as $position => $conversionSpecifier) {
				$extractedNumber = $extracted[$position + 1];
				if($parameter = $mapConversionSpecifiers[$conversionSpecifier]) {
					$strptimeValue[$parameter] = ($extractedNumber > 0) ? (int) $extractedNumber : $extractedNumber;
				} else {
					$strptimeValue['unparsed'] .= $extractedNumber . ' ';
				}
			}

			$strptimeValue['tm_wday'] = $names[substr($strptimeValue['tm_wday'], 0, 3)];

			if($strptimeValue['tm_year'] >= 1900) {
				$strptimeValue['tm_year'] -= 1900;
			} elseif($strptimeValue['tm_year'] > 0) {
				$strptimeValue['tm_year'] += 100;
			}

			if ($strptimeValue['tm_mon']) {
				$strptimeValue['tm_mon'] -= 1;
			} else {
				$strptimeValue['tm_mon'] = $names[substr($strptimeValue['tm_mon'], 0, 3)] - 1;
			}
		}
		return isset($strptimeValue) ? $strptimeValue : FALSE;
	}
}
?>