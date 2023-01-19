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

namespace TYPO3\CMS\Core\Localization;

/**
 * A class providing information about all official ISO 639-2 languages.
 *
 * Data is generated from "Build/Scripts/updateIsoDatabase.php" (which in turn stems from https://github.com/sokil/php-isocodes-db-i18n)
 */
class OfficialLanguages
{
    protected const LABEL_FILE = 'EXT:core/Resources/Private/Language/Iso/languages.xlf';

    // $rawData generated from "Build/Scripts/updateIsoDatabase.php", do not change this directly !!!
    private array $rawData = [
        'aa' => [
            'name' => 'Afar',
            'alias' => 'aar',
        ],
        'ab' => [
            'name' => 'Abkhazian',
            'alias' => 'abk',
        ],
        'ace' => [
            'name' => 'Achinese',
        ],
        'ach' => [
            'name' => 'Acoli',
        ],
        'ada' => [
            'name' => 'Adangme',
        ],
        'ady' => [
            'name' => 'Adyghe; Adygei',
        ],
        'ae' => [
            'name' => 'Avestan',
            'alias' => 'ave',
        ],
        'af' => [
            'name' => 'Afrikaans',
            'alias' => 'afr',
        ],
        'afa' => [
            'name' => 'Afro-Asiatic languages',
        ],
        'afh' => [
            'name' => 'Afrihili',
        ],
        'ain' => [
            'name' => 'Ainu',
        ],
        'ak' => [
            'name' => 'Akan',
            'alias' => 'aka',
        ],
        'akk' => [
            'name' => 'Akkadian',
        ],
        'ale' => [
            'name' => 'Aleut',
        ],
        'alg' => [
            'name' => 'Algonquian languages',
        ],
        'alt' => [
            'name' => 'Southern Altai',
        ],
        'am' => [
            'name' => 'Amharic',
            'alias' => 'amh',
        ],
        'an' => [
            'name' => 'Aragonese',
            'alias' => 'arg',
        ],
        'ang' => [
            'name' => 'English, Old (ca. 450-1100)',
        ],
        'anp' => [
            'name' => 'Angika',
        ],
        'apa' => [
            'name' => 'Apache languages',
        ],
        'ar' => [
            'name' => 'Arabic',
            'alias' => 'ara',
        ],
        'arc' => [
            'name' => 'Official Aramaic (700-300 BCE); Imperial Aramaic (700-300 BCE)',
        ],
        'arn' => [
            'name' => 'Mapudungun; Mapuche',
        ],
        'arp' => [
            'name' => 'Arapaho',
        ],
        'art' => [
            'name' => 'Artificial languages',
        ],
        'arw' => [
            'name' => 'Arawak',
        ],
        'as' => [
            'name' => 'Assamese',
            'alias' => 'asm',
        ],
        'ast' => [
            'name' => 'Asturian; Bable; Leonese; Asturleonese',
        ],
        'ath' => [
            'name' => 'Athapascan languages',
        ],
        'aus' => [
            'name' => 'Australian languages',
        ],
        'av' => [
            'name' => 'Avaric',
            'alias' => 'ava',
        ],
        'awa' => [
            'name' => 'Awadhi',
        ],
        'ay' => [
            'name' => 'Aymara',
            'alias' => 'aym',
        ],
        'az' => [
            'name' => 'Azerbaijani',
            'alias' => 'aze',
        ],
        'ba' => [
            'name' => 'Bashkir',
            'alias' => 'bak',
        ],
        'bad' => [
            'name' => 'Banda languages',
        ],
        'bai' => [
            'name' => 'Bamileke languages',
        ],
        'bal' => [
            'name' => 'Baluchi',
        ],
        'ban' => [
            'name' => 'Balinese',
        ],
        'bas' => [
            'name' => 'Basa',
        ],
        'bat' => [
            'name' => 'Baltic languages',
        ],
        'be' => [
            'name' => 'Belarusian',
            'alias' => 'bel',
        ],
        'bej' => [
            'name' => 'Beja; Bedawiyet',
        ],
        'bem' => [
            'name' => 'Bemba',
        ],
        'ber' => [
            'name' => 'Berber languages',
        ],
        'bg' => [
            'name' => 'Bulgarian',
            'alias' => 'bul',
        ],
        'bh' => [
            'name' => 'Bihari languages',
            'alias' => 'bih',
        ],
        'bho' => [
            'name' => 'Bhojpuri',
        ],
        'bi' => [
            'name' => 'Bislama',
            'alias' => 'bis',
        ],
        'bik' => [
            'name' => 'Bikol',
        ],
        'bin' => [
            'name' => 'Bini; Edo',
        ],
        'bla' => [
            'name' => 'Siksika',
        ],
        'bm' => [
            'name' => 'Bambara',
            'alias' => 'bam',
        ],
        'bn' => [
            'name' => 'Bengali',
            'alias' => 'ben',
        ],
        'bnt' => [
            'name' => 'Bantu (Other)',
        ],
        'bo' => [
            'name' => 'Tibetan',
            'alias' => 'bod',
        ],
        'br' => [
            'name' => 'Breton',
            'alias' => 'bre',
        ],
        'bra' => [
            'name' => 'Braj',
        ],
        'bs' => [
            'name' => 'Bosnian',
            'alias' => 'bos',
        ],
        'btk' => [
            'name' => 'Batak languages',
        ],
        'bua' => [
            'name' => 'Buriat',
        ],
        'bug' => [
            'name' => 'Buginese',
        ],
        'byn' => [
            'name' => 'Blin; Bilin',
        ],
        'ca' => [
            'name' => 'Catalan; Valencian',
            'alias' => 'cat',
        ],
        'cad' => [
            'name' => 'Caddo',
        ],
        'cai' => [
            'name' => 'Central American Indian languages',
        ],
        'car' => [
            'name' => 'Galibi Carib',
        ],
        'cau' => [
            'name' => 'Caucasian languages',
        ],
        'ce' => [
            'name' => 'Chechen',
            'alias' => 'che',
        ],
        'ceb' => [
            'name' => 'Cebuano',
        ],
        'cel' => [
            'name' => 'Celtic languages',
        ],
        'ch' => [
            'name' => 'Chamorro',
            'alias' => 'cha',
        ],
        'chb' => [
            'name' => 'Chibcha',
        ],
        'chg' => [
            'name' => 'Chagatai',
        ],
        'chk' => [
            'name' => 'Chuukese',
        ],
        'chm' => [
            'name' => 'Mari',
        ],
        'chn' => [
            'name' => 'Chinook jargon',
        ],
        'cho' => [
            'name' => 'Choctaw',
        ],
        'chp' => [
            'name' => 'Chipewyan; Dene Suline',
        ],
        'chr' => [
            'name' => 'Cherokee',
        ],
        'chy' => [
            'name' => 'Cheyenne',
        ],
        'cmc' => [
            'name' => 'Chamic languages',
        ],
        'cnr' => [
            'name' => 'Montenegrin',
        ],
        'co' => [
            'name' => 'Corsican',
            'alias' => 'cos',
        ],
        'cop' => [
            'name' => 'Coptic',
        ],
        'cpe' => [
            'name' => 'Creoles and pidgins, English based',
        ],
        'cpf' => [
            'name' => 'Creoles and pidgins, French-based',
        ],
        'cpp' => [
            'name' => 'Creoles and pidgins, Portuguese-based',
        ],
        'cr' => [
            'name' => 'Cree',
            'alias' => 'cre',
        ],
        'crh' => [
            'name' => 'Crimean Tatar; Crimean Turkish',
        ],
        'crp' => [
            'name' => 'Creoles and pidgins',
        ],
        'cs' => [
            'name' => 'Czech',
            'alias' => 'ces',
        ],
        'csb' => [
            'name' => 'Kashubian',
        ],
        'cu' => [
            'name' => 'Church Slavic; Old Slavonic; Church Slavonic; Old Bulgarian; Old Church Slavonic',
            'alias' => 'chu',
        ],
        'cus' => [
            'name' => 'Cushitic languages',
        ],
        'cv' => [
            'name' => 'Chuvash',
            'alias' => 'chv',
        ],
        'cy' => [
            'name' => 'Welsh',
            'alias' => 'cym',
        ],
        'da' => [
            'name' => 'Danish',
            'alias' => 'dan',
        ],
        'dak' => [
            'name' => 'Dakota',
        ],
        'dar' => [
            'name' => 'Dargwa',
        ],
        'day' => [
            'name' => 'Land Dayak languages',
        ],
        'de' => [
            'name' => 'German',
            'alias' => 'deu',
        ],
        'del' => [
            'name' => 'Delaware',
        ],
        'den' => [
            'name' => 'Slave (Athapascan)',
        ],
        'dgr' => [
            'name' => 'Dogrib',
        ],
        'din' => [
            'name' => 'Dinka',
        ],
        'doi' => [
            'name' => 'Dogri',
        ],
        'dra' => [
            'name' => 'Dravidian languages',
        ],
        'dsb' => [
            'name' => 'Lower Sorbian',
        ],
        'dua' => [
            'name' => 'Duala',
        ],
        'dum' => [
            'name' => 'Dutch, Middle (ca. 1050-1350)',
        ],
        'dv' => [
            'name' => 'Divehi; Dhivehi; Maldivian',
            'alias' => 'div',
        ],
        'dyu' => [
            'name' => 'Dyula',
        ],
        'dz' => [
            'name' => 'Dzongkha',
            'alias' => 'dzo',
        ],
        'ee' => [
            'name' => 'Ewe',
            'alias' => 'ewe',
        ],
        'efi' => [
            'name' => 'Efik',
        ],
        'egy' => [
            'name' => 'Egyptian (Ancient)',
        ],
        'eka' => [
            'name' => 'Ekajuk',
        ],
        'el' => [
            'name' => 'Greek, Modern (1453-)',
            'alias' => 'ell',
        ],
        'elx' => [
            'name' => 'Elamite',
        ],
        'en' => [
            'name' => 'English',
            'alias' => 'eng',
        ],
        'enm' => [
            'name' => 'English, Middle (1100-1500)',
        ],
        'eo' => [
            'name' => 'Esperanto',
            'alias' => 'epo',
        ],
        'es' => [
            'name' => 'Spanish; Castilian',
            'alias' => 'spa',
        ],
        'et' => [
            'name' => 'Estonian',
            'alias' => 'est',
        ],
        'eu' => [
            'name' => 'Basque',
            'alias' => 'eus',
        ],
        'ewo' => [
            'name' => 'Ewondo',
        ],
        'fa' => [
            'name' => 'Persian',
            'alias' => 'fas',
        ],
        'fan' => [
            'name' => 'Fang',
        ],
        'fat' => [
            'name' => 'Fanti',
        ],
        'ff' => [
            'name' => 'Fulah',
            'alias' => 'ful',
        ],
        'fi' => [
            'name' => 'Finnish',
            'alias' => 'fin',
        ],
        'fil' => [
            'name' => 'Filipino; Pilipino',
        ],
        'fiu' => [
            'name' => 'Finno-Ugrian languages',
        ],
        'fj' => [
            'name' => 'Fijian',
            'alias' => 'fij',
        ],
        'fo' => [
            'name' => 'Faroese',
            'alias' => 'fao',
        ],
        'fon' => [
            'name' => 'Fon',
        ],
        'fr' => [
            'name' => 'French',
            'alias' => 'fra',
        ],
        'frm' => [
            'name' => 'French, Middle (ca. 1400-1600)',
        ],
        'fro' => [
            'name' => 'French, Old (842-ca. 1400)',
        ],
        'frr' => [
            'name' => 'Northern Frisian',
        ],
        'frs' => [
            'name' => 'Eastern Frisian',
        ],
        'fur' => [
            'name' => 'Friulian',
        ],
        'fy' => [
            'name' => 'Western Frisian',
            'alias' => 'fry',
        ],
        'ga' => [
            'name' => 'Irish',
            'alias' => 'gle',
        ],
        'gaa' => [
            'name' => 'Ga',
        ],
        'gay' => [
            'name' => 'Gayo',
        ],
        'gba' => [
            'name' => 'Gbaya',
        ],
        'gd' => [
            'name' => 'Gaelic; Scottish Gaelic',
            'alias' => 'gla',
        ],
        'gem' => [
            'name' => 'Germanic languages',
        ],
        'gez' => [
            'name' => 'Geez',
        ],
        'gil' => [
            'name' => 'Gilbertese',
        ],
        'gl' => [
            'name' => 'Galician',
            'alias' => 'glg',
        ],
        'gmh' => [
            'name' => 'German, Middle High (ca. 1050-1500)',
        ],
        'gn' => [
            'name' => 'Guarani',
            'alias' => 'grn',
        ],
        'goh' => [
            'name' => 'German, Old High (ca. 750-1050)',
        ],
        'gon' => [
            'name' => 'Gondi',
        ],
        'gor' => [
            'name' => 'Gorontalo',
        ],
        'got' => [
            'name' => 'Gothic',
        ],
        'grb' => [
            'name' => 'Grebo',
        ],
        'grc' => [
            'name' => 'Greek, Ancient (to 1453)',
        ],
        'gsw' => [
            'name' => 'Swiss German; Alemannic; Alsatian',
        ],
        'gu' => [
            'name' => 'Gujarati',
            'alias' => 'guj',
        ],
        'gv' => [
            'name' => 'Manx',
            'alias' => 'glv',
        ],
        'gwi' => [
            'name' => 'Gwich\'in',
        ],
        'ha' => [
            'name' => 'Hausa',
            'alias' => 'hau',
        ],
        'hai' => [
            'name' => 'Haida',
        ],
        'haw' => [
            'name' => 'Hawaiian',
        ],
        'he' => [
            'name' => 'Hebrew',
            'alias' => 'heb',
        ],
        'hi' => [
            'name' => 'Hindi',
            'alias' => 'hin',
        ],
        'hil' => [
            'name' => 'Hiligaynon',
        ],
        'him' => [
            'name' => 'Himachali languages; Western Pahari languages',
        ],
        'hit' => [
            'name' => 'Hittite',
        ],
        'hmn' => [
            'name' => 'Hmong; Mong',
        ],
        'ho' => [
            'name' => 'Hiri Motu',
            'alias' => 'hmo',
        ],
        'hr' => [
            'name' => 'Croatian',
            'alias' => 'hrv',
        ],
        'hsb' => [
            'name' => 'Upper Sorbian',
        ],
        'ht' => [
            'name' => 'Haitian; Haitian Creole',
            'alias' => 'hat',
        ],
        'hu' => [
            'name' => 'Hungarian',
            'alias' => 'hun',
        ],
        'hup' => [
            'name' => 'Hupa',
        ],
        'hy' => [
            'name' => 'Armenian',
            'alias' => 'hye',
        ],
        'hz' => [
            'name' => 'Herero',
            'alias' => 'her',
        ],
        'ia' => [
            'name' => 'Interlingua (International Auxiliary Language Association)',
            'alias' => 'ina',
        ],
        'iba' => [
            'name' => 'Iban',
        ],
        'id' => [
            'name' => 'Indonesian',
            'alias' => 'ind',
        ],
        'ie' => [
            'name' => 'Interlingue; Occidental',
            'alias' => 'ile',
        ],
        'ig' => [
            'name' => 'Igbo',
            'alias' => 'ibo',
        ],
        'ii' => [
            'name' => 'Sichuan Yi; Nuosu',
            'alias' => 'iii',
        ],
        'ijo' => [
            'name' => 'Ijo languages',
        ],
        'ik' => [
            'name' => 'Inupiaq',
            'alias' => 'ipk',
        ],
        'ilo' => [
            'name' => 'Iloko',
        ],
        'inc' => [
            'name' => 'Indic languages',
        ],
        'ine' => [
            'name' => 'Indo-European languages',
        ],
        'inh' => [
            'name' => 'Ingush',
        ],
        'io' => [
            'name' => 'Ido',
            'alias' => 'ido',
        ],
        'ira' => [
            'name' => 'Iranian languages',
        ],
        'iro' => [
            'name' => 'Iroquoian languages',
        ],
        'is' => [
            'name' => 'Icelandic',
            'alias' => 'isl',
        ],
        'it' => [
            'name' => 'Italian',
            'alias' => 'ita',
        ],
        'iu' => [
            'name' => 'Inuktitut',
            'alias' => 'iku',
        ],
        'ja' => [
            'name' => 'Japanese',
            'alias' => 'jpn',
        ],
        'jbo' => [
            'name' => 'Lojban',
        ],
        'jpr' => [
            'name' => 'Judeo-Persian',
        ],
        'jrb' => [
            'name' => 'Judeo-Arabic',
        ],
        'jv' => [
            'name' => 'Javanese',
            'alias' => 'jav',
        ],
        'ka' => [
            'name' => 'Georgian',
            'alias' => 'kat',
        ],
        'kaa' => [
            'name' => 'Kara-Kalpak',
        ],
        'kab' => [
            'name' => 'Kabyle',
        ],
        'kac' => [
            'name' => 'Kachin; Jingpho',
        ],
        'kam' => [
            'name' => 'Kamba',
        ],
        'kar' => [
            'name' => 'Karen languages',
        ],
        'kaw' => [
            'name' => 'Kawi',
        ],
        'kbd' => [
            'name' => 'Kabardian',
        ],
        'kg' => [
            'name' => 'Kongo',
            'alias' => 'kon',
        ],
        'kha' => [
            'name' => 'Khasi',
        ],
        'khi' => [
            'name' => 'Khoisan languages',
        ],
        'kho' => [
            'name' => 'Khotanese; Sakan',
        ],
        'ki' => [
            'name' => 'Kikuyu; Gikuyu',
            'alias' => 'kik',
        ],
        'kj' => [
            'name' => 'Kuanyama; Kwanyama',
            'alias' => 'kua',
        ],
        'kk' => [
            'name' => 'Kazakh',
            'alias' => 'kaz',
        ],
        'kl' => [
            'name' => 'Kalaallisut; Greenlandic',
            'alias' => 'kal',
        ],
        'km' => [
            'name' => 'Central Khmer',
            'alias' => 'khm',
        ],
        'kmb' => [
            'name' => 'Kimbundu',
        ],
        'kn' => [
            'name' => 'Kannada',
            'alias' => 'kan',
        ],
        'ko' => [
            'name' => 'Korean',
            'alias' => 'kor',
        ],
        'kok' => [
            'name' => 'Konkani',
        ],
        'kos' => [
            'name' => 'Kosraean',
        ],
        'kpe' => [
            'name' => 'Kpelle',
        ],
        'kr' => [
            'name' => 'Kanuri',
            'alias' => 'kau',
        ],
        'krc' => [
            'name' => 'Karachay-Balkar',
        ],
        'krl' => [
            'name' => 'Karelian',
        ],
        'kro' => [
            'name' => 'Kru languages',
        ],
        'kru' => [
            'name' => 'Kurukh',
        ],
        'ks' => [
            'name' => 'Kashmiri',
            'alias' => 'kas',
        ],
        'ku' => [
            'name' => 'Kurdish',
            'alias' => 'kur',
        ],
        'kum' => [
            'name' => 'Kumyk',
        ],
        'kut' => [
            'name' => 'Kutenai',
        ],
        'kv' => [
            'name' => 'Komi',
            'alias' => 'kom',
        ],
        'kw' => [
            'name' => 'Cornish',
            'alias' => 'cor',
        ],
        'ky' => [
            'name' => 'Kirghiz; Kyrgyz',
            'alias' => 'kir',
        ],
        'la' => [
            'name' => 'Latin',
            'alias' => 'lat',
        ],
        'lad' => [
            'name' => 'Ladino',
        ],
        'lah' => [
            'name' => 'Lahnda',
        ],
        'lam' => [
            'name' => 'Lamba',
        ],
        'lb' => [
            'name' => 'Luxembourgish; Letzeburgesch',
            'alias' => 'ltz',
        ],
        'lez' => [
            'name' => 'Lezghian',
        ],
        'lg' => [
            'name' => 'Ganda',
            'alias' => 'lug',
        ],
        'li' => [
            'name' => 'Limburgan; Limburger; Limburgish',
            'alias' => 'lim',
        ],
        'ln' => [
            'name' => 'Lingala',
            'alias' => 'lin',
        ],
        'lo' => [
            'name' => 'Lao',
            'alias' => 'lao',
        ],
        'lol' => [
            'name' => 'Mongo',
        ],
        'loz' => [
            'name' => 'Lozi',
        ],
        'lt' => [
            'name' => 'Lithuanian',
            'alias' => 'lit',
        ],
        'lu' => [
            'name' => 'Luba-Katanga',
            'alias' => 'lub',
        ],
        'lua' => [
            'name' => 'Luba-Lulua',
        ],
        'lui' => [
            'name' => 'Luiseno',
        ],
        'lun' => [
            'name' => 'Lunda',
        ],
        'luo' => [
            'name' => 'Luo (Kenya and Tanzania)',
        ],
        'lus' => [
            'name' => 'Lushai',
        ],
        'lv' => [
            'name' => 'Latvian',
            'alias' => 'lav',
        ],
        'mad' => [
            'name' => 'Madurese',
        ],
        'mag' => [
            'name' => 'Magahi',
        ],
        'mai' => [
            'name' => 'Maithili',
        ],
        'mak' => [
            'name' => 'Makasar',
        ],
        'man' => [
            'name' => 'Mandingo',
        ],
        'map' => [
            'name' => 'Austronesian languages',
        ],
        'mas' => [
            'name' => 'Masai',
        ],
        'mdf' => [
            'name' => 'Moksha',
        ],
        'mdr' => [
            'name' => 'Mandar',
        ],
        'men' => [
            'name' => 'Mende',
        ],
        'mg' => [
            'name' => 'Malagasy',
            'alias' => 'mlg',
        ],
        'mga' => [
            'name' => 'Irish, Middle (900-1200)',
        ],
        'mh' => [
            'name' => 'Marshallese',
            'alias' => 'mah',
        ],
        'mi' => [
            'name' => 'Maori',
            'alias' => 'mri',
        ],
        'mic' => [
            'name' => 'Mi\'kmaq; Micmac',
        ],
        'min' => [
            'name' => 'Minangkabau',
        ],
        'mis' => [
            'name' => 'Uncoded languages',
        ],
        'mk' => [
            'name' => 'Macedonian',
            'alias' => 'mkd',
        ],
        'mkh' => [
            'name' => 'Mon-Khmer languages',
        ],
        'ml' => [
            'name' => 'Malayalam',
            'alias' => 'mal',
        ],
        'mn' => [
            'name' => 'Mongolian',
            'alias' => 'mon',
        ],
        'mnc' => [
            'name' => 'Manchu',
        ],
        'mni' => [
            'name' => 'Manipuri',
        ],
        'mno' => [
            'name' => 'Manobo languages',
        ],
        'moh' => [
            'name' => 'Mohawk',
        ],
        'mos' => [
            'name' => 'Mossi',
        ],
        'mr' => [
            'name' => 'Marathi',
            'alias' => 'mar',
        ],
        'ms' => [
            'name' => 'Malay',
            'alias' => 'msa',
        ],
        'mt' => [
            'name' => 'Maltese',
            'alias' => 'mlt',
        ],
        'mul' => [
            'name' => 'Multiple languages',
        ],
        'mun' => [
            'name' => 'Munda languages',
        ],
        'mus' => [
            'name' => 'Creek',
        ],
        'mwl' => [
            'name' => 'Mirandese',
        ],
        'mwr' => [
            'name' => 'Marwari',
        ],
        'my' => [
            'name' => 'Burmese',
            'alias' => 'mya',
        ],
        'myn' => [
            'name' => 'Mayan languages',
        ],
        'myv' => [
            'name' => 'Erzya',
        ],
        'na' => [
            'name' => 'Nauru',
            'alias' => 'nau',
        ],
        'nah' => [
            'name' => 'Nahuatl languages',
        ],
        'nai' => [
            'name' => 'North American Indian languages',
        ],
        'nap' => [
            'name' => 'Neapolitan',
        ],
        'nb' => [
            'name' => 'Bokmål, Norwegian; Norwegian Bokmål',
            'alias' => 'nob',
        ],
        'nd' => [
            'name' => 'Ndebele, North; North Ndebele',
            'alias' => 'nde',
        ],
        'nds' => [
            'name' => 'Low German; Low Saxon; German, Low; Saxon, Low',
        ],
        'ne' => [
            'name' => 'Nepali',
            'alias' => 'nep',
        ],
        'new' => [
            'name' => 'Nepal Bhasa; Newari',
        ],
        'ng' => [
            'name' => 'Ndonga',
            'alias' => 'ndo',
        ],
        'nia' => [
            'name' => 'Nias',
        ],
        'nic' => [
            'name' => 'Niger-Kordofanian languages',
        ],
        'niu' => [
            'name' => 'Niuean',
        ],
        'nl' => [
            'name' => 'Dutch; Flemish',
            'alias' => 'nld',
        ],
        'nn' => [
            'name' => 'Norwegian Nynorsk; Nynorsk, Norwegian',
            'alias' => 'nno',
        ],
        'no' => [
            'name' => 'Norwegian',
            'alias' => 'nor',
        ],
        'nog' => [
            'name' => 'Nogai',
        ],
        'non' => [
            'name' => 'Norse, Old',
        ],
        'nqo' => [
            'name' => 'N\'Ko',
        ],
        'nr' => [
            'name' => 'Ndebele, South; South Ndebele',
            'alias' => 'nbl',
        ],
        'nso' => [
            'name' => 'Pedi; Sepedi; Northern Sotho',
        ],
        'nub' => [
            'name' => 'Nubian languages',
        ],
        'nv' => [
            'name' => 'Navajo; Navaho',
            'alias' => 'nav',
        ],
        'nwc' => [
            'name' => 'Classical Newari; Old Newari; Classical Nepal Bhasa',
        ],
        'ny' => [
            'name' => 'Chichewa; Chewa; Nyanja',
            'alias' => 'nya',
        ],
        'nym' => [
            'name' => 'Nyamwezi',
        ],
        'nyn' => [
            'name' => 'Nyankole',
        ],
        'nyo' => [
            'name' => 'Nyoro',
        ],
        'nzi' => [
            'name' => 'Nzima',
        ],
        'oc' => [
            'name' => 'Occitan (post 1500); Provençal',
            'alias' => 'oci',
        ],
        'oj' => [
            'name' => 'Ojibwa',
            'alias' => 'oji',
        ],
        'om' => [
            'name' => 'Oromo',
            'alias' => 'orm',
        ],
        'or' => [
            'name' => 'Oriya',
            'alias' => 'ori',
        ],
        'os' => [
            'name' => 'Ossetian; Ossetic',
            'alias' => 'oss',
        ],
        'osa' => [
            'name' => 'Osage',
        ],
        'ota' => [
            'name' => 'Turkish, Ottoman (1500-1928)',
        ],
        'oto' => [
            'name' => 'Otomian languages',
        ],
        'pa' => [
            'name' => 'Panjabi; Punjabi',
            'alias' => 'pan',
        ],
        'paa' => [
            'name' => 'Papuan languages',
        ],
        'pag' => [
            'name' => 'Pangasinan',
        ],
        'pal' => [
            'name' => 'Pahlavi',
        ],
        'pam' => [
            'name' => 'Pampanga; Kapampangan',
        ],
        'pap' => [
            'name' => 'Papiamento',
        ],
        'pau' => [
            'name' => 'Palauan',
        ],
        'peo' => [
            'name' => 'Persian, Old (ca. 600-400 B.C.)',
        ],
        'phi' => [
            'name' => 'Philippine languages',
        ],
        'phn' => [
            'name' => 'Phoenician',
        ],
        'pi' => [
            'name' => 'Pali',
            'alias' => 'pli',
        ],
        'pl' => [
            'name' => 'Polish',
            'alias' => 'pol',
        ],
        'pon' => [
            'name' => 'Pohnpeian',
        ],
        'pra' => [
            'name' => 'Prakrit languages',
        ],
        'pro' => [
            'name' => 'Provençal, Old (to 1500)',
        ],
        'ps' => [
            'name' => 'Pushto; Pashto',
            'alias' => 'pus',
        ],
        'pt' => [
            'name' => 'Portuguese',
            'alias' => 'por',
        ],
        'qaa-qtz' => [
            'name' => 'Reserved for local use',
        ],
        'qu' => [
            'name' => 'Quechua',
            'alias' => 'que',
        ],
        'raj' => [
            'name' => 'Rajasthani',
        ],
        'rap' => [
            'name' => 'Rapanui',
        ],
        'rar' => [
            'name' => 'Rarotongan; Cook Islands Maori',
        ],
        'rm' => [
            'name' => 'Romansh',
            'alias' => 'roh',
        ],
        'rn' => [
            'name' => 'Rundi',
            'alias' => 'run',
        ],
        'ro' => [
            'name' => 'Romanian; Moldavian; Moldovan',
            'alias' => 'ron',
        ],
        'roa' => [
            'name' => 'Romance languages',
        ],
        'rom' => [
            'name' => 'Romany',
        ],
        'ru' => [
            'name' => 'Russian',
            'alias' => 'rus',
        ],
        'rup' => [
            'name' => 'Aromanian; Arumanian; Macedo-Romanian',
        ],
        'rw' => [
            'name' => 'Kinyarwanda',
            'alias' => 'kin',
        ],
        'sa' => [
            'name' => 'Sanskrit',
            'alias' => 'san',
        ],
        'sad' => [
            'name' => 'Sandawe',
        ],
        'sah' => [
            'name' => 'Yakut',
        ],
        'sai' => [
            'name' => 'South American Indian (Other)',
        ],
        'sal' => [
            'name' => 'Salishan languages',
        ],
        'sam' => [
            'name' => 'Samaritan Aramaic',
        ],
        'sas' => [
            'name' => 'Sasak',
        ],
        'sat' => [
            'name' => 'Santali',
        ],
        'sc' => [
            'name' => 'Sardinian',
            'alias' => 'srd',
        ],
        'scn' => [
            'name' => 'Sicilian',
        ],
        'sco' => [
            'name' => 'Scots',
        ],
        'sd' => [
            'name' => 'Sindhi',
            'alias' => 'snd',
        ],
        'se' => [
            'name' => 'Northern Sami',
            'alias' => 'sme',
        ],
        'sel' => [
            'name' => 'Selkup',
        ],
        'sem' => [
            'name' => 'Semitic languages',
        ],
        'sg' => [
            'name' => 'Sango',
            'alias' => 'sag',
        ],
        'sga' => [
            'name' => 'Irish, Old (to 900)',
        ],
        'sgn' => [
            'name' => 'Sign Languages',
        ],
        'shn' => [
            'name' => 'Shan',
        ],
        'si' => [
            'name' => 'Sinhala; Sinhalese',
            'alias' => 'sin',
        ],
        'sid' => [
            'name' => 'Sidamo',
        ],
        'sio' => [
            'name' => 'Siouan languages',
        ],
        'sit' => [
            'name' => 'Sino-Tibetan languages',
        ],
        'sk' => [
            'name' => 'Slovak',
            'alias' => 'slk',
        ],
        'sl' => [
            'name' => 'Slovenian',
            'alias' => 'slv',
        ],
        'sla' => [
            'name' => 'Slavic languages',
        ],
        'sm' => [
            'name' => 'Samoan',
            'alias' => 'smo',
        ],
        'sma' => [
            'name' => 'Southern Sami',
        ],
        'smi' => [
            'name' => 'Sami languages',
        ],
        'smj' => [
            'name' => 'Lule Sami',
        ],
        'smn' => [
            'name' => 'Inari Sami',
        ],
        'sms' => [
            'name' => 'Skolt Sami',
        ],
        'sn' => [
            'name' => 'Shona',
            'alias' => 'sna',
        ],
        'snk' => [
            'name' => 'Soninke',
        ],
        'so' => [
            'name' => 'Somali',
            'alias' => 'som',
        ],
        'sog' => [
            'name' => 'Sogdian',
        ],
        'son' => [
            'name' => 'Songhai languages',
        ],
        'sq' => [
            'name' => 'Albanian',
            'alias' => 'sqi',
        ],
        'sr' => [
            'name' => 'Serbian',
            'alias' => 'srp',
        ],
        'srn' => [
            'name' => 'Sranan Tongo',
        ],
        'srr' => [
            'name' => 'Serer',
        ],
        'ss' => [
            'name' => 'Swati',
            'alias' => 'ssw',
        ],
        'ssa' => [
            'name' => 'Nilo-Saharan languages',
        ],
        'st' => [
            'name' => 'Sotho, Southern',
            'alias' => 'sot',
        ],
        'su' => [
            'name' => 'Sundanese',
            'alias' => 'sun',
        ],
        'suk' => [
            'name' => 'Sukuma',
        ],
        'sus' => [
            'name' => 'Susu',
        ],
        'sux' => [
            'name' => 'Sumerian',
        ],
        'sv' => [
            'name' => 'Swedish',
            'alias' => 'swe',
        ],
        'sw' => [
            'name' => 'Swahili',
            'alias' => 'swa',
        ],
        'syc' => [
            'name' => 'Classical Syriac',
        ],
        'syr' => [
            'name' => 'Syriac',
        ],
        'ta' => [
            'name' => 'Tamil',
            'alias' => 'tam',
        ],
        'tai' => [
            'name' => 'Tai languages',
        ],
        'te' => [
            'name' => 'Telugu',
            'alias' => 'tel',
        ],
        'tem' => [
            'name' => 'Timne',
        ],
        'ter' => [
            'name' => 'Tereno',
        ],
        'tet' => [
            'name' => 'Tetum',
        ],
        'tg' => [
            'name' => 'Tajik',
            'alias' => 'tgk',
        ],
        'th' => [
            'name' => 'Thai',
            'alias' => 'tha',
        ],
        'ti' => [
            'name' => 'Tigrinya',
            'alias' => 'tir',
        ],
        'tig' => [
            'name' => 'Tigre',
        ],
        'tiv' => [
            'name' => 'Tiv',
        ],
        'tk' => [
            'name' => 'Turkmen',
            'alias' => 'tuk',
        ],
        'tkl' => [
            'name' => 'Tokelau',
        ],
        'tl' => [
            'name' => 'Tagalog',
            'alias' => 'tgl',
        ],
        'tlh' => [
            'name' => 'Klingon; tlhIngan-Hol',
        ],
        'tli' => [
            'name' => 'Tlingit',
        ],
        'tmh' => [
            'name' => 'Tamashek',
        ],
        'tn' => [
            'name' => 'Tswana',
            'alias' => 'tsn',
        ],
        'to' => [
            'name' => 'Tonga (Tonga Islands)',
            'alias' => 'ton',
        ],
        'tog' => [
            'name' => 'Tonga (Nyasa)',
        ],
        'tpi' => [
            'name' => 'Tok Pisin',
        ],
        'tr' => [
            'name' => 'Turkish',
            'alias' => 'tur',
        ],
        'ts' => [
            'name' => 'Tsonga',
            'alias' => 'tso',
        ],
        'tsi' => [
            'name' => 'Tsimshian',
        ],
        'tt' => [
            'name' => 'Tatar',
            'alias' => 'tat',
        ],
        'tum' => [
            'name' => 'Tumbuka',
        ],
        'tup' => [
            'name' => 'Tupi languages',
        ],
        'tut' => [
            'name' => 'Altaic languages',
        ],
        'tvl' => [
            'name' => 'Tuvalu',
        ],
        'tw' => [
            'name' => 'Twi',
            'alias' => 'twi',
        ],
        'ty' => [
            'name' => 'Tahitian',
            'alias' => 'tah',
        ],
        'tyv' => [
            'name' => 'Tuvinian',
        ],
        'udm' => [
            'name' => 'Udmurt',
        ],
        'ug' => [
            'name' => 'Uighur; Uyghur',
            'alias' => 'uig',
        ],
        'uga' => [
            'name' => 'Ugaritic',
        ],
        'uk' => [
            'name' => 'Ukrainian',
            'alias' => 'ukr',
        ],
        'umb' => [
            'name' => 'Umbundu',
        ],
        'und' => [
            'name' => 'Undetermined',
        ],
        'ur' => [
            'name' => 'Urdu',
            'alias' => 'urd',
        ],
        'uz' => [
            'name' => 'Uzbek',
            'alias' => 'uzb',
        ],
        'vai' => [
            'name' => 'Vai',
        ],
        've' => [
            'name' => 'Venda',
            'alias' => 'ven',
        ],
        'vi' => [
            'name' => 'Vietnamese',
            'alias' => 'vie',
        ],
        'vo' => [
            'name' => 'Volapük',
            'alias' => 'vol',
        ],
        'vot' => [
            'name' => 'Votic',
        ],
        'wa' => [
            'name' => 'Walloon',
            'alias' => 'wln',
        ],
        'wak' => [
            'name' => 'Wakashan languages',
        ],
        'wal' => [
            'name' => 'Walamo',
        ],
        'war' => [
            'name' => 'Waray',
        ],
        'was' => [
            'name' => 'Washo',
        ],
        'wen' => [
            'name' => 'Sorbian languages',
        ],
        'wo' => [
            'name' => 'Wolof',
            'alias' => 'wol',
        ],
        'xal' => [
            'name' => 'Kalmyk; Oirat',
        ],
        'xh' => [
            'name' => 'Xhosa',
            'alias' => 'xho',
        ],
        'yao' => [
            'name' => 'Yao',
        ],
        'yap' => [
            'name' => 'Yapese',
        ],
        'yi' => [
            'name' => 'Yiddish',
            'alias' => 'yid',
        ],
        'yo' => [
            'name' => 'Yoruba',
            'alias' => 'yor',
        ],
        'ypk' => [
            'name' => 'Yupik languages',
        ],
        'za' => [
            'name' => 'Zhuang; Chuang',
            'alias' => 'zha',
        ],
        'zap' => [
            'name' => 'Zapotec',
        ],
        'zbl' => [
            'name' => 'Blissymbols; Blissymbolics; Bliss',
        ],
        'zen' => [
            'name' => 'Zenaga',
        ],
        'zgh' => [
            'name' => 'Standard Moroccan Tamazight',
        ],
        'zh' => [
            'name' => 'Chinese',
            'alias' => 'zho',
        ],
        'znd' => [
            'name' => 'Zande languages',
        ],
        'zu' => [
            'name' => 'Zulu',
            'alias' => 'zul',
        ],
        'zun' => [
            'name' => 'Zuni',
        ],
        'zxx' => [
            'name' => 'No linguistic content; Not applicable',
        ],
        'zza' => [
            'name' => 'Zaza; Dimili; Dimli; Kirdki; Kirmanjki; Zazaki',
        ],
    ];

    public function isValidLanguageKey(string $languageKey): bool
    {
        $languageKey = strtolower($languageKey);
        if (!isset($this->rawData[$languageKey])) {
            foreach ($this->rawData as $details) {
                if (isset($details['alias']) && $details['alias'] === $languageKey) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    public function getLabelIdentifier(string $languageKey): string
    {
        return 'LLL:' . self::LABEL_FILE . ':' . $languageKey;
    }
}
