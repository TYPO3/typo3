<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Fluid\ViewHelpers\Format;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * ViewHelper to format an object implementing `\DateTimeInterface` into human-readable output.
 *
 * ```
 *   <f:format.date format="Y-m-d H:i">{dateObject}</f:format.date>
 *   <f:format.date format="Y" base="{dateObject}">-1 year</f:format.date>
 *   <f:format.date pattern="dd. MMMM yyyy" locale="de-DE">{dateObject}</f:format.date>
 * ```
 *
 * @see https://www.php.net/manual/datetime.format.php
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-format-date
 * @see \DateTimeInterface
 */
final class DateViewHelper extends AbstractViewHelper
{
    /**
     * Needed as child node's output can return a DateTime object which can't be escaped
     *
     * @var bool
     */
    protected $escapeChildren = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('date', 'mixed', 'Either an object implementing DateTimeInterface or a string that is accepted by DateTime constructor');
        $this->registerArgument('format', 'string', 'Format String which is taken to format the Date/Time', false, '');
        $this->registerArgument('pattern', 'string', 'Format date based on unicode ICO format pattern given see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax. If both "pattern" and "format" arguments are given, pattern will be used.');
        $this->registerArgument('locale', 'string', 'A locale format such as "nl-NL" to format the date in a specific locale, if none given, uses the current locale of the current request. Only works when pattern argument is given');
        $this->registerArgument('base', 'mixed', 'A base time (an object implementing DateTimeInterface or a string) used if $date is a relative date specification. Defaults to current time.');
    }

    /**
     * @throws Exception
     */
    public function render(): string
    {
        $format = $this->arguments['format'] ?? '';
        $pattern = $this->arguments['pattern'] ?? null;
        $base = $this->arguments['base'] ?? GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
        if (is_string($base)) {
            $base = trim($base);
        }
        if ($format === '') {
            $format = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?: 'Y-m-d';
        }
        $date = $this->renderChildren();
        if ($date === null) {
            return '';
        }
        if (is_string($date)) {
            $date = trim($date);
        }
        if ($date === '') {
            $date = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp', 'now');
        }
        if (!$date instanceof \DateTimeInterface) {
            $base = $base instanceof \DateTimeInterface
                ? (int)$base->format('U')
                : (int)strtotime((MathUtility::canBeInterpretedAsInteger($base) ? '@' : '') . $base);
            $dateTimestamp = strtotime((MathUtility::canBeInterpretedAsInteger($date) ? '@' : '') . $date, $base);
            if ($dateTimestamp === false) {
                throw new Exception('"' . $date . '" could not be converted to a timestamp. Probably due to a parsing error.', 1241722579);
            }
            $date = (new \DateTime())->setTimestamp($dateTimestamp);
        }
        if ($pattern !== null) {
            $locale = $this->arguments['locale'] ?? self::resolveLocale($this->renderingContext);
            return (new DateFormatter())->format($date, $pattern, $locale);
        }
        if (str_contains($format, '%')) {
            // @todo: deprecate this syntax in TYPO3 v13.
            $locale = $this->arguments['locale'] ?? self::resolveLocale($this->renderingContext);
            return (new DateFormatter())->strftime($format, $date, $locale);
        }
        return $date->format($format);
    }

    /**
     * Explicitly set argument name to be used as content.
     */
    public function getContentArgumentName(): string
    {
        return 'date';
    }

    private static function resolveLocale(RenderingContextInterface $renderingContext): Locale
    {
        $request = null;
        if ($renderingContext->hasAttribute(ServerRequestInterface::class)) {
            $request = $renderingContext->getAttribute(ServerRequestInterface::class);
        } elseif (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface) {
            // @todo: deprecate
            $request = $GLOBALS['TYPO3_REQUEST'];
        }
        if ($request && ApplicationType::fromRequest($request)->isFrontend()) {
            // Frontend application
            $siteLanguage = $request->getAttribute('language');

            // Get values from site language
            if ($siteLanguage !== null) {
                return $siteLanguage->getLocale();
            }
        } elseif (($GLOBALS['BE_USER'] ?? null) instanceof BackendUserAuthentication
            && !empty($GLOBALS['BE_USER']->user['lang'])) {
            return new Locale($GLOBALS['BE_USER']->user['lang']);
        }
        return new Locale();
    }
}
