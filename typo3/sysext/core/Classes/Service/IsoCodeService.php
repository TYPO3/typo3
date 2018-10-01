<?php
namespace TYPO3\CMS\Core\Service;

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

/**
 * Helper functionality for dealing with ISO codes.
 *
 * @internal This class is not part of the TYPO3 Core API.
 */
class IsoCodeService
{
    /**
     * Renders a select dropdown with ISO 639-1 codes.
     *
     * @param array $conf
     * @return array
     */
    public function renderIsoCodeSelectDropdown(array $conf = [])
    {
        $languageService = $this->getLanguageService();

        $isoCodes = $this->getIsoCodes();
        $languages = [];
        foreach ($isoCodes as $isoCode) {
            $languages[$isoCode] = $languageService->sL('LLL:EXT:core/Resources/Private/Language/db.xlf:sys_language.language_isocode.' . $isoCode);
        }
        // Sort languages by name
        asort($languages);

        $items = [];
        foreach ($languages as $isoCode => $name) {
            $items[] = [$name, $isoCode];
        }

        $conf['items'] = array_merge($conf['items'], $items);
        return $conf;
    }

    /**
     * Returns the list of ISO 639-1 codes.
     *
     * List taken from http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     *
     * @return array
     */
    protected function getIsoCodes()
    {
        $isoCodes = [
            'ab',
            'aa',
            'af',
            'ak',
            'sq',
            'am',
            'ar',
            'an',
            'hy',
            'as',
            'av',
            'ae',
            'ay',
            'az',
            'bm',
            'ba',
            'eu',
            'be',
            'bn',
            'bh',
            'bi',
            'bs',
            'br',
            'bg',
            'my',
            'ca',
            'ch',
            'ce',
            'ny',
            'zh',
            'cv',
            'kw',
            'co',
            'cr',
            'hr',
            'cs',
            'da',
            'dv',
            'nl',
            'dz',
            'en',
            'eo',
            'et',
            'ee',
            'fo',
            'fj',
            'fi',
            'fr',
            'ff',
            'gl',
            'ka',
            'de',
            'el',
            'gn',
            'gu',
            'ht',
            'ha',
            'he',
            'hz',
            'hi',
            'ho',
            'hu',
            'ia',
            'id',
            'ie',
            'ga',
            'ig',
            'ik',
            'io',
            'is',
            'it',
            'iu',
            'ja',
            'jv',
            'kl',
            'kn',
            'kr',
            'ks',
            'kk',
            'km',
            'ki',
            'rw',
            'ky',
            'kv',
            'kg',
            'ko',
            'ku',
            'kj',
            'la',
            'lb',
            'lg',
            'li',
            'ln',
            'lo',
            'lt',
            'lu',
            'lv',
            'gv',
            'mk',
            'mg',
            'ms',
            'ml',
            'mt',
            'mi',
            'mr',
            'mh',
            'mn',
            'na',
            'nv',
            'nd',
            'ne',
            'ng',
            'nb',
            'nn',
            'no',
            'ii',
            'nr',
            'oc',
            'oj',
            'cu',
            'om',
            'or',
            'os',
            'pa',
            'pi',
            'fa',
            'pl',
            'ps',
            'pt',
            'qu',
            'rm',
            'rn',
            'ro',
            'ru',
            'sa',
            'sc',
            'sd',
            'se',
            'sm',
            'sg',
            'sr',
            'gd',
            'sn',
            'si',
            'sk',
            'sl',
            'so',
            'st',
            'es',
            'su',
            'sw',
            'ss',
            'sv',
            'ta',
            'te',
            'tg',
            'th',
            'ti',
            'bo',
            'tk',
            'tl',
            'tn',
            'to',
            'tr',
            'ts',
            'tt',
            'tw',
            'ty',
            'ug',
            'uk',
            'ur',
            'uz',
            've',
            'vi',
            'vo',
            'wa',
            'cy',
            'wo',
            'fy',
            'xh',
            'yi',
            'yo',
            'za',
            'zu',
        ];
        return $isoCodes;
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
