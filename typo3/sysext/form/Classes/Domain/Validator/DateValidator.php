<?php
namespace TYPO3\CMS\Form\Domain\Validator;

/*
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

class DateValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'element' => ['', 'The name of the element', 'string', true],
        'errorMessage' => ['', 'The error message', 'array', true],
        'format' => ['', 'The maximum value', 'string', true],
    ];

    /**
     * Constant for localisation
     *
     * @var string
     */
    const LOCALISATION_OBJECT_NAME = 'tx_form_system_validate_date';

    /**
     * Check if $value is valid. If it is not valid, needs to add an error
     * to result.
     *
     * @param mixed $value
     * @return void
     */
    public function isValid($value)
    {
        if (
            $this->options['format'] === null
            || $this->options['format'] === ''
        ) {
            $this->options['format'] = '%e-%m-%Y';
        }

        if (function_exists('strptime')) {
            $parsedDate = strptime($value, $this->options['format']);
            $parsedDateYear = $parsedDate['tm_year'] + 1900;
            $parsedDateMonth = $parsedDate['tm_mon'] + 1;
            $parsedDateDay = $parsedDate['tm_mday'];
            if (!checkdate($parsedDateMonth, $parsedDateDay, $parsedDateYear)) {
                $this->addError(
                    $this->renderMessage(
                        $this->options['errorMessage'][0],
                        $this->options['errorMessage'][1],
                        'error'
                    ),
                    1442001386
                );
                return;
            }
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
                ['%a', '%A', '%d', '%e', '%j', '%b', '%B', '%h', '%m', '%y', '%Y'],
                ['D', 'l', 'd', 'j', 'z', 'M', 'F', 'M', 'm', 'y', 'Y'],
                $this->options['format']
            );
            $dateTimeObject = date_create_from_format($dateTimeFormat, $value);
            if ($dateTimeObject === false) {
                $this->addError(
                    $this->renderMessage(
                        $this->options['errorMessage'][0],
                        $this->options['errorMessage'][1],
                        'error'
                    ),
                    1442001386
                );
                return;
            }

            if ($value !== $dateTimeObject->format($dateTimeFormat)) {
                $this->addError(
                    $this->renderMessage(
                        $this->options['errorMessage'][0],
                        $this->options['errorMessage'][1],
                        'error'
                    ),
                    1442001386
                );
            }
        }
    }

    /**
     * Substitute makers in the message text
     * Overrides the abstract
     *
     * @param string $message Message text with markers
     * @return string Message text with substituted markers
     */
    public function substituteMarkers($message)
    {
        $humanReadableDateFormat = $this->humanReadableDateFormat($this->options['format']);
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
    protected function humanReadableDateFormat($format)
    {
        $label = self::LOCALISATION_OBJECT_NAME . '.strftime.';
        $pairs = [
            '%A' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label . 'A', 'form'),
            '%a' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label . 'a', 'form'),
            '%d' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label . 'd', 'form'),
            '%e' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label . 'e', 'form'),
            '%B' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label . 'B', 'form'),
            '%b' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label . 'b', 'form'),
            '%m' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label . 'm', 'form'),
            '%Y' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label . 'Y', 'form'),
            '%y' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label . 'y', 'form'),
            '%H' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label . 'H', 'form'),
            '%I' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label . 'I', 'form'),
            '%M' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label . 'M', 'form'),
            '%S' => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label . 'S', 'form')
        ];
        $humanReadableFormat = str_replace(array_keys($pairs), array_values($pairs), $format);
        return $humanReadableFormat;
    }
}
