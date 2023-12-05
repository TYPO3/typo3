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

namespace TYPO3\CMS\Core\Site;

/**
 * Provides site language presets
 * @internal
 */
class SiteLanguagePresets
{
    protected array $presets = [
        'af-ZA' => [
            'title' => 'Afrikaans',
            'navigationTitle' => 'Afrikaans',
            'locale' => 'af_ZA',
            'base' => '/af/',
            'flag' => 'af',
        ],
        'ar-SA' => [
            'title' => 'Arabic',
            'navigationTitle' => 'العربية',
            'locale' => 'ar_SA',
            'base' => '/ar/',
            'flag' => 'sa',
        ],
        'bs-BA' => [
            'title' => 'Bosnian',
            'navigationTitle' => 'Bosanski',
            'locale' => 'bs_BA',
            'base' => '/ba/',
            'flag' => 'ba',
        ],
        'bg-BG' => [
            'title' => 'Bulgarian',
            'navigationTitle' => 'Български',
            'locale' => 'bg_BG',
            'base' => '/bg/',
            'flag' => 'bg',
        ],
        'ca-ES' => [
            'title' => 'Catalan',
            'navigationTitle' => 'Català',
            'locale' => 'ca_ES',
            'base' => '/ca/',
            'flag' => 'catalonia',
        ],
        'zh-CN' => [
            'title' => 'Chinese (Simplified)',
            'navigationTitle' => '汉语',
            'locale' => 'zh_CN',
            'base' => '/cn/',
            'flag' => 'cn',
        ],
        'cs-CZ' => [
            'title' => 'Czech',
            'navigationTitle' => 'Čeština',
            'locale' => 'cs_CZ',
            'base' => '/cz/',
            'flag' => 'cz',
        ],
        'cy-GB' => [
            'title' => 'Welsh',
            'navigationTitle' => 'Cymraeg',
            'locale' => 'cy_GB',
            'base' => '/cy/',
            'flag' => 'cy',
        ],
        'da-DK' => [
            'title' => 'Danish',
            'navigationTitle' => 'Dansk',
            'locale' => 'da_DK',
            'base' => '/da/',
            'flag' => 'dk',
        ],
        'de-DE' => [
            'title' => 'German',
            'navigationTitle' => 'Deutsch',
            'locale' => 'de_DE',
            'base' => '/de/',
            'flag' => 'de',
        ],
        'el-GR' => [
            'title' => 'Greek',
            'navigationTitle' => 'Ελληνικά',
            'locale' => 'el_GR',
            'base' => '/gr/',
            'flag' => 'gr',
        ],
        'en-US' => [
            'title' => 'English',
            'navigationTitle' => 'English',
            'locale' => 'en_US',
            'base' => '/en/',
            'flag' => 'en-us-gb',
        ],
        'eo-XX' => [
            'title' => 'Esperanto',
            'navigationTitle' => 'Esperanto',
            'locale' => 'eo_XX',
            'base' => '/eo/',
            'flag' => 'eo',
        ],
        'es-ES' => [
            'title' => 'Spanish',
            'navigationTitle' => 'Español',
            'locale' => 'es_ES',
            'base' => '/es/',
            'flag' => 'es',
        ],
        'et-EE' => [
            'title' => 'Estonian',
            'navigationTitle' => 'Eesti',
            'locale' => 'et_EE',
            'base' => '/et/',
            'flag' => 'ee',
        ],
        'eu-ES' => [
            'title' => 'Basque',
            'navigationTitle' => 'Euskara',
            'locale' => 'eu_ES',
            'base' => '/eu/',
            'flag' => 'eu',
        ],
        'fa-IR' => [
            'title' => 'Persian',
            'navigationTitle' => 'فارسی',
            'locale' => 'fa_IR',
            'base' => '/fa/',
            'flag' => 'ir',
        ],
        'fi-FI' => [
            'title' => 'Finnish',
            'navigationTitle' => 'Suomi',
            'locale' => 'fi_FI',
            'base' => '/fi/',
            'flag' => 'fi',
        ],
        'fo-FO' => [
            'title' => 'Faeroese',
            'navigationTitle' => 'Føroyskt',
            'locale' => 'fo_FO',
            'base' => '/fo/',
            'flag' => 'fo',
        ],
        'fr-FR' => [
            'title' => 'French',
            'navigationTitle' => 'Français',
            'locale' => 'fr_FR',
            'base' => '/fr/',
            'flag' => 'fr',
        ],
        'fr-CA' => [
            'title' => 'Canadian French',
            'navigationTitle' => 'Français canadien',
            'locale' => 'fr_CA',
            'base' => '/qc/',
            'flag' => 'qc',
        ],
        'gl-ES' => [
            'title' => 'Galician',
            'navigationTitle' => 'Galego',
            'locale' => 'gl_ES',
            'base' => '/ga/',
            'flag' => 'gl',
        ],
        'kl-DK' => [
            'title' => 'Greenlandic',
            'navigationTitle' => 'Kalaallisut',
            'locale' => 'kl_DK',
            'base' => '/gl/',
            'flag' => 'kl',
        ],
        'he-IL' => [
            'title' => 'Hebrew',
            'navigationTitle' => 'עברית',
            'locale' => 'he_IL',
            'base' => '/he/',
            'flag' => 'il',
        ],
        'hi-IN' => [
            'title' => 'Hindi',
            'navigationTitle' => 'हिन्दी',
            'locale' => 'hi_IN',
            'base' => '/hi/',
            'flag' => 'in',
        ],
        'hr-HR' => [
            'title' => 'Croatian',
            'navigationTitle' => 'Hrvatski',
            'locale' => 'hr_HR',
            'base' => '/hr/',
            'flag' => 'hr',
        ],
        'hu-HU' => [
            'title' => 'Hungarian',
            'navigationTitle' => 'Magyar',
            'locale' => 'hu_HU',
            'base' => '/hu/',
            'flag' => 'hu',
        ],
        'is-IS' => [
            'title' => 'Icelandic',
            'navigationTitle' => 'Íslenska',
            'locale' => 'is_IS',
            'base' => '/is/',
            'flag' => 'is',
        ],
        'it-IT' => [
            'title' => 'Italian',
            'navigationTitle' => 'Italiano',
            'locale' => 'it_IT',
            'base' => '/it/',
            'flag' => 'it',
        ],
        'ja-JP' => [
            'title' => 'Japanese',
            'navigationTitle' => '日本語',
            'locale' => 'ja_JP',
            'base' => '/jp/',
            'flag' => 'jp',
        ],
        'ka-GE' => [
            'title' => 'Georgian',
            'navigationTitle' => 'ქართული',
            'locale' => 'ka_GE',
            'base' => '/ge/',
            'flag' => 'ge',
        ],
        'km-KH' => [
            'title' => 'Khmer',
            'navigationTitle' => 'ភាសាខ្មែរ',
            'locale' => 'km_KH',
            'base' => '/km/',
            'flag' => 'km',
        ],
        'ko-KR' => [
            'title' => 'Korean',
            'navigationTitle' => '한국말',
            'locale' => 'ko_KR',
            'base' => '/kr/',
            'flag' => 'kr',
        ],
        'lt-LT' => [
            'title' => 'Lithuanian',
            'navigationTitle' => 'Lietuvių',
            'locale' => 'lt_LT',
            'base' => '/lt/',
            'flag' => 'lt',
        ],
        'lv-LV' => [
            'title' => 'Latvian',
            'navigationTitle' => 'Latviešu',
            'locale' => 'lv_LV',
            'base' => '/lv/',
            'flag' => 'lv',
        ],
        'mi-NZ' => [
            'title' => 'Maori',
            'navigationTitle' => 'Māori',
            'locale' => 'mi_NZ',
            'base' => '/mi/',
            'flag' => 'mi',
        ],
        'ms-MY' => [
            'title' => 'Malay',
            'navigationTitle' => 'Bahasa Melayu',
            'locale' => 'ms_MY',
            'base' => '/ms/',
            'flag' => 'my',
        ],
        'nl-NL' => [
            'title' => 'Dutch',
            'navigationTitle' => 'Nederlands',
            'locale' => 'nl_NL',
            'base' => '/nl/',
            'flag' => 'nl',
        ],
        'no-NO' => [
            'title' => 'Norwegian',
            'navigationTitle' => 'Norsk',
            'locale' => 'no_NO',
            'base' => '/no/',
            'flag' => 'no',
        ],
        'pl-PL' => [
            'title' => 'Polish',
            'navigationTitle' => 'Polski',
            'locale' => 'pl_PL',
            'base' => '/pl/',
            'flag' => 'pl',
        ],
        'pt-PT' => [
            'title' => 'Portuguese',
            'navigationTitle' => 'Português',
            'locale' => 'pt_PT',
            'base' => '/pt/',
            'flag' => 'pt',
        ],
        'pt-BR' => [
            'title' => 'Brazilian Portuguese',
            'navigationTitle' => 'Português brasileiro',
            'locale' => 'pt_BR',
            'base' => '/br/',
            'flag' => 'br',
        ],
        'ro-RO' => [
            'title' => 'Romanian',
            'navigationTitle' => 'Română',
            'locale' => 'ro_RO',
            'base' => '/ro/',
            'flag' => 'ro',
        ],
        'ru-RU' => [
            'title' => 'Russian',
            'navigationTitle' => 'Русский',
            'locale' => 'ru_RU',
            'base' => '/ru/',
            'flag' => 'ru',
        ],
        'sl-SI' => [
            'title' => 'Slovenian',
            'navigationTitle' => 'Slovenščina',
            'locale' => 'sl_SI',
            'base' => '/si/',
            'flag' => 'si',
        ],
        'sk-SK' => [
            'title' => 'Slovak',
            'navigationTitle' => 'Slovenčina',
            'locale' => 'sk_SK',
            'base' => '/sk/',
            'flag' => 'sk',
        ],
        'sn_ZW' => [
            'title' => 'Shona (Bantu)',
            'navigationTitle' => 'chiShona',
            'locale' => 'sn_ZW',
            'base' => '/sn/',
            'flag' => 'zw',
        ],
        'sv-SE' => [
            'title' => 'Swedish',
            'navigationTitle' => 'Svenska',
            'locale' => 'sv_SE',
            'base' => '/se/',
            'flag' => 'se',
        ],
        'sq-AL' => [
            'title' => 'Albanian',
            'navigationTitle' => 'Gjuha shqipe',
            'locale' => 'sq_AL',
            'base' => '/sq/',
            'flag' => 'al',
        ],
        'sr-YO' => [
            'title' => 'Serbian',
            'navigationTitle' => 'Српски / Srpski',
            'locale' => 'sr_YO',
            'base' => '/sr/',
            'flag' => 'rs',
        ],
        'th-TH' => [
            'title' => 'Thai',
            'navigationTitle' => 'ภาษาไทย',
            'locale' => 'th_TH',
            'base' => '/th/',
            'flag' => 'th',
        ],
        'tr-TR' => [
            'title' => 'Turkish',
            'navigationTitle' => 'Türkçe',
            'locale' => 'tr_TR',
            'base' => '/tr/',
            'flag' => 'tr',
        ],
        'uk-UA' => [
            'title' => 'Ukrainian',
            'navigationTitle' => 'Українська',
            'locale' => 'uk_UA',
            'base' => '/ua/',
            'flag' => 'ua',
        ],
        'vi-VN' => [
            'title' => 'Vietnamese',
            'navigationTitle' => 'Tiếng Việt',
            'locale' => 'vi_VN',
            'base' => '/vn/',
            'flag' => 'vn',
        ],
        'zh-HK' => [
            'title' => 'Chinese (Traditional)',
            'navigationTitle' => '漢語',
            'locale' => 'zh_HK',
            'base' => '/hk/',
            'flag' => 'hk',
        ],
    ];

    public function getAll(): array
    {
        return $this->presets;
    }

    public function getPresetDetailsForLanguage(string $language): ?array
    {
        return $this->presets[$language] ?? null;
    }

    public function getAllForSelector(): array
    {
        $presetOptions = [];
        foreach ($this->presets as $language => $preset) {
            $presetOptions[$preset['title']] = [
                'value' => $language,
                'label' => $preset['title'],
            ];
        }
        ksort($presetOptions);
        return $presetOptions;
    }
}
