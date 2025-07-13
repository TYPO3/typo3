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

namespace TYPO3\CMS\Core\Country;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Country\Event\BeforeCountriesEvaluatedEvent;

/**
 * A class providing information about all countries.
 *
 * Country data is generated from "Build/Scripts/updateIsoDatabase.php" (which in turn stems from https://github.com/sokil/php-isocodes-db-i18n)
 */
#[AsAlias('country.provider', public: true)]
#[Autoconfigure(public: true)]
class CountryProvider
{
    // $rawData generated from "Build/Scripts/updateIsoDatabase.php", do not change this directly !!!
    private array $rawData = [
        'AD' => [
            'alpha_3' => 'AND',
            'flag' => '🇦🇩',
            'name' => 'Andorra',
            'numeric' => '020',
            'official_name' => 'Principality of Andorra',
        ],
        'AE' => [
            'alpha_3' => 'ARE',
            'flag' => '🇦🇪',
            'name' => 'United Arab Emirates',
            'numeric' => '784',
        ],
        'AF' => [
            'alpha_3' => 'AFG',
            'flag' => '🇦🇫',
            'name' => 'Afghanistan',
            'numeric' => '004',
            'official_name' => 'Islamic Republic of Afghanistan',
        ],
        'AG' => [
            'alpha_3' => 'ATG',
            'flag' => '🇦🇬',
            'name' => 'Antigua and Barbuda',
            'numeric' => '028',
        ],
        'AI' => [
            'alpha_3' => 'AIA',
            'flag' => '🇦🇮',
            'name' => 'Anguilla',
            'numeric' => '660',
        ],
        'AL' => [
            'alpha_3' => 'ALB',
            'flag' => '🇦🇱',
            'name' => 'Albania',
            'numeric' => '008',
            'official_name' => 'Republic of Albania',
        ],
        'AM' => [
            'alpha_3' => 'ARM',
            'flag' => '🇦🇲',
            'name' => 'Armenia',
            'numeric' => '051',
            'official_name' => 'Republic of Armenia',
        ],
        'AO' => [
            'alpha_3' => 'AGO',
            'flag' => '🇦🇴',
            'name' => 'Angola',
            'numeric' => '024',
            'official_name' => 'Republic of Angola',
        ],
        'AQ' => [
            'alpha_3' => 'ATA',
            'flag' => '🇦🇶',
            'name' => 'Antarctica',
            'numeric' => '010',
        ],
        'AR' => [
            'alpha_3' => 'ARG',
            'flag' => '🇦🇷',
            'name' => 'Argentina',
            'numeric' => '032',
            'official_name' => 'Argentine Republic',
        ],
        'AS' => [
            'alpha_3' => 'ASM',
            'flag' => '🇦🇸',
            'name' => 'American Samoa',
            'numeric' => '016',
        ],
        'AT' => [
            'alpha_3' => 'AUT',
            'flag' => '🇦🇹',
            'name' => 'Austria',
            'numeric' => '040',
            'official_name' => 'Republic of Austria',
        ],
        'AU' => [
            'alpha_3' => 'AUS',
            'flag' => '🇦🇺',
            'name' => 'Australia',
            'numeric' => '036',
        ],
        'AW' => [
            'alpha_3' => 'ABW',
            'flag' => '🇦🇼',
            'name' => 'Aruba',
            'numeric' => '533',
        ],
        'AX' => [
            'alpha_3' => 'ALA',
            'flag' => '🇦🇽',
            'name' => 'Åland Islands',
            'numeric' => '248',
        ],
        'AZ' => [
            'alpha_3' => 'AZE',
            'flag' => '🇦🇿',
            'name' => 'Azerbaijan',
            'numeric' => '031',
            'official_name' => 'Republic of Azerbaijan',
        ],
        'BA' => [
            'alpha_3' => 'BIH',
            'flag' => '🇧🇦',
            'name' => 'Bosnia and Herzegovina',
            'numeric' => '070',
            'official_name' => 'Republic of Bosnia and Herzegovina',
        ],
        'BB' => [
            'alpha_3' => 'BRB',
            'flag' => '🇧🇧',
            'name' => 'Barbados',
            'numeric' => '052',
        ],
        'BD' => [
            'alpha_3' => 'BGD',
            'flag' => '🇧🇩',
            'name' => 'Bangladesh',
            'numeric' => '050',
            'official_name' => 'People\'s Republic of Bangladesh',
        ],
        'BE' => [
            'alpha_3' => 'BEL',
            'flag' => '🇧🇪',
            'name' => 'Belgium',
            'numeric' => '056',
            'official_name' => 'Kingdom of Belgium',
        ],
        'BF' => [
            'alpha_3' => 'BFA',
            'flag' => '🇧🇫',
            'name' => 'Burkina Faso',
            'numeric' => '854',
        ],
        'BG' => [
            'alpha_3' => 'BGR',
            'flag' => '🇧🇬',
            'name' => 'Bulgaria',
            'numeric' => '100',
            'official_name' => 'Republic of Bulgaria',
        ],
        'BH' => [
            'alpha_3' => 'BHR',
            'flag' => '🇧🇭',
            'name' => 'Bahrain',
            'numeric' => '048',
            'official_name' => 'Kingdom of Bahrain',
        ],
        'BI' => [
            'alpha_3' => 'BDI',
            'flag' => '🇧🇮',
            'name' => 'Burundi',
            'numeric' => '108',
            'official_name' => 'Republic of Burundi',
        ],
        'BJ' => [
            'alpha_3' => 'BEN',
            'flag' => '🇧🇯',
            'name' => 'Benin',
            'numeric' => '204',
            'official_name' => 'Republic of Benin',
        ],
        'BL' => [
            'alpha_3' => 'BLM',
            'flag' => '🇧🇱',
            'name' => 'Saint Barthélemy',
            'numeric' => '652',
        ],
        'BM' => [
            'alpha_3' => 'BMU',
            'flag' => '🇧🇲',
            'name' => 'Bermuda',
            'numeric' => '060',
        ],
        'BN' => [
            'alpha_3' => 'BRN',
            'flag' => '🇧🇳',
            'name' => 'Brunei Darussalam',
            'numeric' => '096',
        ],
        'BO' => [
            'alpha_3' => 'BOL',
            'common_name' => 'Bolivia',
            'flag' => '🇧🇴',
            'name' => 'Bolivia, Plurinational State of',
            'numeric' => '068',
            'official_name' => 'Plurinational State of Bolivia',
        ],
        'BQ' => [
            'alpha_3' => 'BES',
            'flag' => '🇧🇶',
            'name' => 'Bonaire, Sint Eustatius and Saba',
            'numeric' => '535',
            'official_name' => 'Bonaire, Sint Eustatius and Saba',
        ],
        'BR' => [
            'alpha_3' => 'BRA',
            'flag' => '🇧🇷',
            'name' => 'Brazil',
            'numeric' => '076',
            'official_name' => 'Federative Republic of Brazil',
        ],
        'BS' => [
            'alpha_3' => 'BHS',
            'flag' => '🇧🇸',
            'name' => 'Bahamas',
            'numeric' => '044',
            'official_name' => 'Commonwealth of the Bahamas',
        ],
        'BT' => [
            'alpha_3' => 'BTN',
            'flag' => '🇧🇹',
            'name' => 'Bhutan',
            'numeric' => '064',
            'official_name' => 'Kingdom of Bhutan',
        ],
        'BV' => [
            'alpha_3' => 'BVT',
            'flag' => '🇧🇻',
            'name' => 'Bouvet Island',
            'numeric' => '074',
        ],
        'BW' => [
            'alpha_3' => 'BWA',
            'flag' => '🇧🇼',
            'name' => 'Botswana',
            'numeric' => '072',
            'official_name' => 'Republic of Botswana',
        ],
        'BY' => [
            'alpha_3' => 'BLR',
            'flag' => '🇧🇾',
            'name' => 'Belarus',
            'numeric' => '112',
            'official_name' => 'Republic of Belarus',
        ],
        'BZ' => [
            'alpha_3' => 'BLZ',
            'flag' => '🇧🇿',
            'name' => 'Belize',
            'numeric' => '084',
        ],
        'CA' => [
            'alpha_3' => 'CAN',
            'flag' => '🇨🇦',
            'name' => 'Canada',
            'numeric' => '124',
        ],
        'CC' => [
            'alpha_3' => 'CCK',
            'flag' => '🇨🇨',
            'name' => 'Cocos (Keeling) Islands',
            'numeric' => '166',
        ],
        'CD' => [
            'alpha_3' => 'COD',
            'flag' => '🇨🇩',
            'name' => 'Congo, The Democratic Republic of the',
            'numeric' => '180',
        ],
        'CF' => [
            'alpha_3' => 'CAF',
            'flag' => '🇨🇫',
            'name' => 'Central African Republic',
            'numeric' => '140',
        ],
        'CG' => [
            'alpha_3' => 'COG',
            'flag' => '🇨🇬',
            'name' => 'Congo',
            'numeric' => '178',
            'official_name' => 'Republic of the Congo',
        ],
        'CH' => [
            'alpha_3' => 'CHE',
            'flag' => '🇨🇭',
            'name' => 'Switzerland',
            'numeric' => '756',
            'official_name' => 'Swiss Confederation',
        ],
        'CI' => [
            'alpha_3' => 'CIV',
            'flag' => '🇨🇮',
            'name' => 'Côte d\'Ivoire',
            'numeric' => '384',
            'official_name' => 'Republic of Côte d\'Ivoire',
        ],
        'CK' => [
            'alpha_3' => 'COK',
            'flag' => '🇨🇰',
            'name' => 'Cook Islands',
            'numeric' => '184',
        ],
        'CL' => [
            'alpha_3' => 'CHL',
            'flag' => '🇨🇱',
            'name' => 'Chile',
            'numeric' => '152',
            'official_name' => 'Republic of Chile',
        ],
        'CM' => [
            'alpha_3' => 'CMR',
            'flag' => '🇨🇲',
            'name' => 'Cameroon',
            'numeric' => '120',
            'official_name' => 'Republic of Cameroon',
        ],
        'CN' => [
            'alpha_3' => 'CHN',
            'flag' => '🇨🇳',
            'name' => 'China',
            'numeric' => '156',
            'official_name' => 'People\'s Republic of China',
        ],
        'CO' => [
            'alpha_3' => 'COL',
            'flag' => '🇨🇴',
            'name' => 'Colombia',
            'numeric' => '170',
            'official_name' => 'Republic of Colombia',
        ],
        'CR' => [
            'alpha_3' => 'CRI',
            'flag' => '🇨🇷',
            'name' => 'Costa Rica',
            'numeric' => '188',
            'official_name' => 'Republic of Costa Rica',
        ],
        'CU' => [
            'alpha_3' => 'CUB',
            'flag' => '🇨🇺',
            'name' => 'Cuba',
            'numeric' => '192',
            'official_name' => 'Republic of Cuba',
        ],
        'CV' => [
            'alpha_3' => 'CPV',
            'flag' => '🇨🇻',
            'name' => 'Cabo Verde',
            'numeric' => '132',
            'official_name' => 'Republic of Cabo Verde',
        ],
        'CW' => [
            'alpha_3' => 'CUW',
            'flag' => '🇨🇼',
            'name' => 'Curaçao',
            'numeric' => '531',
            'official_name' => 'Curaçao',
        ],
        'CX' => [
            'alpha_3' => 'CXR',
            'flag' => '🇨🇽',
            'name' => 'Christmas Island',
            'numeric' => '162',
        ],
        'CY' => [
            'alpha_3' => 'CYP',
            'flag' => '🇨🇾',
            'name' => 'Cyprus',
            'numeric' => '196',
            'official_name' => 'Republic of Cyprus',
        ],
        'CZ' => [
            'alpha_3' => 'CZE',
            'flag' => '🇨🇿',
            'name' => 'Czechia',
            'numeric' => '203',
            'official_name' => 'Czech Republic',
        ],
        'DE' => [
            'alpha_3' => 'DEU',
            'flag' => '🇩🇪',
            'name' => 'Germany',
            'numeric' => '276',
            'official_name' => 'Federal Republic of Germany',
        ],
        'DJ' => [
            'alpha_3' => 'DJI',
            'flag' => '🇩🇯',
            'name' => 'Djibouti',
            'numeric' => '262',
            'official_name' => 'Republic of Djibouti',
        ],
        'DK' => [
            'alpha_3' => 'DNK',
            'flag' => '🇩🇰',
            'name' => 'Denmark',
            'numeric' => '208',
            'official_name' => 'Kingdom of Denmark',
        ],
        'DM' => [
            'alpha_3' => 'DMA',
            'flag' => '🇩🇲',
            'name' => 'Dominica',
            'numeric' => '212',
            'official_name' => 'Commonwealth of Dominica',
        ],
        'DO' => [
            'alpha_3' => 'DOM',
            'flag' => '🇩🇴',
            'name' => 'Dominican Republic',
            'numeric' => '214',
        ],
        'DZ' => [
            'alpha_3' => 'DZA',
            'flag' => '🇩🇿',
            'name' => 'Algeria',
            'numeric' => '012',
            'official_name' => 'People\'s Democratic Republic of Algeria',
        ],
        'EC' => [
            'alpha_3' => 'ECU',
            'flag' => '🇪🇨',
            'name' => 'Ecuador',
            'numeric' => '218',
            'official_name' => 'Republic of Ecuador',
        ],
        'EE' => [
            'alpha_3' => 'EST',
            'flag' => '🇪🇪',
            'name' => 'Estonia',
            'numeric' => '233',
            'official_name' => 'Republic of Estonia',
        ],
        'EG' => [
            'alpha_3' => 'EGY',
            'flag' => '🇪🇬',
            'name' => 'Egypt',
            'numeric' => '818',
            'official_name' => 'Arab Republic of Egypt',
        ],
        'EH' => [
            'alpha_3' => 'ESH',
            'flag' => '🇪🇭',
            'name' => 'Western Sahara',
            'numeric' => '732',
        ],
        'ER' => [
            'alpha_3' => 'ERI',
            'flag' => '🇪🇷',
            'name' => 'Eritrea',
            'numeric' => '232',
            'official_name' => 'the State of Eritrea',
        ],
        'ES' => [
            'alpha_3' => 'ESP',
            'flag' => '🇪🇸',
            'name' => 'Spain',
            'numeric' => '724',
            'official_name' => 'Kingdom of Spain',
        ],
        'ET' => [
            'alpha_3' => 'ETH',
            'flag' => '🇪🇹',
            'name' => 'Ethiopia',
            'numeric' => '231',
            'official_name' => 'Federal Democratic Republic of Ethiopia',
        ],
        'FI' => [
            'alpha_3' => 'FIN',
            'flag' => '🇫🇮',
            'name' => 'Finland',
            'numeric' => '246',
            'official_name' => 'Republic of Finland',
        ],
        'FJ' => [
            'alpha_3' => 'FJI',
            'flag' => '🇫🇯',
            'name' => 'Fiji',
            'numeric' => '242',
            'official_name' => 'Republic of Fiji',
        ],
        'FK' => [
            'alpha_3' => 'FLK',
            'flag' => '🇫🇰',
            'name' => 'Falkland Islands (Malvinas)',
            'numeric' => '238',
        ],
        'FM' => [
            'alpha_3' => 'FSM',
            'flag' => '🇫🇲',
            'name' => 'Micronesia, Federated States of',
            'numeric' => '583',
            'official_name' => 'Federated States of Micronesia',
        ],
        'FO' => [
            'alpha_3' => 'FRO',
            'flag' => '🇫🇴',
            'name' => 'Faroe Islands',
            'numeric' => '234',
        ],
        'FR' => [
            'alpha_3' => 'FRA',
            'flag' => '🇫🇷',
            'name' => 'France',
            'numeric' => '250',
            'official_name' => 'French Republic',
        ],
        'GA' => [
            'alpha_3' => 'GAB',
            'flag' => '🇬🇦',
            'name' => 'Gabon',
            'numeric' => '266',
            'official_name' => 'Gabonese Republic',
        ],
        'GB' => [
            'alpha_3' => 'GBR',
            'flag' => '🇬🇧',
            'name' => 'United Kingdom',
            'numeric' => '826',
            'official_name' => 'United Kingdom of Great Britain and Northern Ireland',
        ],
        'GD' => [
            'alpha_3' => 'GRD',
            'flag' => '🇬🇩',
            'name' => 'Grenada',
            'numeric' => '308',
        ],
        'GE' => [
            'alpha_3' => 'GEO',
            'flag' => '🇬🇪',
            'name' => 'Georgia',
            'numeric' => '268',
        ],
        'GF' => [
            'alpha_3' => 'GUF',
            'flag' => '🇬🇫',
            'name' => 'French Guiana',
            'numeric' => '254',
        ],
        'GG' => [
            'alpha_3' => 'GGY',
            'flag' => '🇬🇬',
            'name' => 'Guernsey',
            'numeric' => '831',
        ],
        'GH' => [
            'alpha_3' => 'GHA',
            'flag' => '🇬🇭',
            'name' => 'Ghana',
            'numeric' => '288',
            'official_name' => 'Republic of Ghana',
        ],
        'GI' => [
            'alpha_3' => 'GIB',
            'flag' => '🇬🇮',
            'name' => 'Gibraltar',
            'numeric' => '292',
        ],
        'GL' => [
            'alpha_3' => 'GRL',
            'flag' => '🇬🇱',
            'name' => 'Greenland',
            'numeric' => '304',
        ],
        'GM' => [
            'alpha_3' => 'GMB',
            'flag' => '🇬🇲',
            'name' => 'Gambia',
            'numeric' => '270',
            'official_name' => 'Republic of the Gambia',
        ],
        'GN' => [
            'alpha_3' => 'GIN',
            'flag' => '🇬🇳',
            'name' => 'Guinea',
            'numeric' => '324',
            'official_name' => 'Republic of Guinea',
        ],
        'GP' => [
            'alpha_3' => 'GLP',
            'flag' => '🇬🇵',
            'name' => 'Guadeloupe',
            'numeric' => '312',
        ],
        'GQ' => [
            'alpha_3' => 'GNQ',
            'flag' => '🇬🇶',
            'name' => 'Equatorial Guinea',
            'numeric' => '226',
            'official_name' => 'Republic of Equatorial Guinea',
        ],
        'GR' => [
            'alpha_3' => 'GRC',
            'flag' => '🇬🇷',
            'name' => 'Greece',
            'numeric' => '300',
            'official_name' => 'Hellenic Republic',
        ],
        'GS' => [
            'alpha_3' => 'SGS',
            'flag' => '🇬🇸',
            'name' => 'South Georgia and the South Sandwich Islands',
            'numeric' => '239',
        ],
        'GT' => [
            'alpha_3' => 'GTM',
            'flag' => '🇬🇹',
            'name' => 'Guatemala',
            'numeric' => '320',
            'official_name' => 'Republic of Guatemala',
        ],
        'GU' => [
            'alpha_3' => 'GUM',
            'flag' => '🇬🇺',
            'name' => 'Guam',
            'numeric' => '316',
        ],
        'GW' => [
            'alpha_3' => 'GNB',
            'flag' => '🇬🇼',
            'name' => 'Guinea-Bissau',
            'numeric' => '624',
            'official_name' => 'Republic of Guinea-Bissau',
        ],
        'GY' => [
            'alpha_3' => 'GUY',
            'flag' => '🇬🇾',
            'name' => 'Guyana',
            'numeric' => '328',
            'official_name' => 'Republic of Guyana',
        ],
        'HK' => [
            'alpha_3' => 'HKG',
            'flag' => '🇭🇰',
            'name' => 'Hong Kong',
            'numeric' => '344',
            'official_name' => 'Hong Kong Special Administrative Region of China',
        ],
        'HM' => [
            'alpha_3' => 'HMD',
            'flag' => '🇭🇲',
            'name' => 'Heard Island and McDonald Islands',
            'numeric' => '334',
        ],
        'HN' => [
            'alpha_3' => 'HND',
            'flag' => '🇭🇳',
            'name' => 'Honduras',
            'numeric' => '340',
            'official_name' => 'Republic of Honduras',
        ],
        'HR' => [
            'alpha_3' => 'HRV',
            'flag' => '🇭🇷',
            'name' => 'Croatia',
            'numeric' => '191',
            'official_name' => 'Republic of Croatia',
        ],
        'HT' => [
            'alpha_3' => 'HTI',
            'flag' => '🇭🇹',
            'name' => 'Haiti',
            'numeric' => '332',
            'official_name' => 'Republic of Haiti',
        ],
        'HU' => [
            'alpha_3' => 'HUN',
            'flag' => '🇭🇺',
            'name' => 'Hungary',
            'numeric' => '348',
            'official_name' => 'Hungary',
        ],
        'ID' => [
            'alpha_3' => 'IDN',
            'flag' => '🇮🇩',
            'name' => 'Indonesia',
            'numeric' => '360',
            'official_name' => 'Republic of Indonesia',
        ],
        'IE' => [
            'alpha_3' => 'IRL',
            'flag' => '🇮🇪',
            'name' => 'Ireland',
            'numeric' => '372',
        ],
        'IL' => [
            'alpha_3' => 'ISR',
            'flag' => '🇮🇱',
            'name' => 'Israel',
            'numeric' => '376',
            'official_name' => 'State of Israel',
        ],
        'IM' => [
            'alpha_3' => 'IMN',
            'flag' => '🇮🇲',
            'name' => 'Isle of Man',
            'numeric' => '833',
        ],
        'IN' => [
            'alpha_3' => 'IND',
            'flag' => '🇮🇳',
            'name' => 'India',
            'numeric' => '356',
            'official_name' => 'Republic of India',
        ],
        'IO' => [
            'alpha_3' => 'IOT',
            'flag' => '🇮🇴',
            'name' => 'British Indian Ocean Territory',
            'numeric' => '086',
        ],
        'IQ' => [
            'alpha_3' => 'IRQ',
            'flag' => '🇮🇶',
            'name' => 'Iraq',
            'numeric' => '368',
            'official_name' => 'Republic of Iraq',
        ],
        'IR' => [
            'alpha_3' => 'IRN',
            'common_name' => 'Iran',
            'flag' => '🇮🇷',
            'name' => 'Iran, Islamic Republic of',
            'numeric' => '364',
            'official_name' => 'Islamic Republic of Iran',
        ],
        'IS' => [
            'alpha_3' => 'ISL',
            'flag' => '🇮🇸',
            'name' => 'Iceland',
            'numeric' => '352',
            'official_name' => 'Republic of Iceland',
        ],
        'IT' => [
            'alpha_3' => 'ITA',
            'flag' => '🇮🇹',
            'name' => 'Italy',
            'numeric' => '380',
            'official_name' => 'Italian Republic',
        ],
        'JE' => [
            'alpha_3' => 'JEY',
            'flag' => '🇯🇪',
            'name' => 'Jersey',
            'numeric' => '832',
        ],
        'JM' => [
            'alpha_3' => 'JAM',
            'flag' => '🇯🇲',
            'name' => 'Jamaica',
            'numeric' => '388',
        ],
        'JO' => [
            'alpha_3' => 'JOR',
            'flag' => '🇯🇴',
            'name' => 'Jordan',
            'numeric' => '400',
            'official_name' => 'Hashemite Kingdom of Jordan',
        ],
        'JP' => [
            'alpha_3' => 'JPN',
            'flag' => '🇯🇵',
            'name' => 'Japan',
            'numeric' => '392',
        ],
        'KE' => [
            'alpha_3' => 'KEN',
            'flag' => '🇰🇪',
            'name' => 'Kenya',
            'numeric' => '404',
            'official_name' => 'Republic of Kenya',
        ],
        'KG' => [
            'alpha_3' => 'KGZ',
            'flag' => '🇰🇬',
            'name' => 'Kyrgyzstan',
            'numeric' => '417',
            'official_name' => 'Kyrgyz Republic',
        ],
        'KH' => [
            'alpha_3' => 'KHM',
            'flag' => '🇰🇭',
            'name' => 'Cambodia',
            'numeric' => '116',
            'official_name' => 'Kingdom of Cambodia',
        ],
        'KI' => [
            'alpha_3' => 'KIR',
            'flag' => '🇰🇮',
            'name' => 'Kiribati',
            'numeric' => '296',
            'official_name' => 'Republic of Kiribati',
        ],
        'KM' => [
            'alpha_3' => 'COM',
            'flag' => '🇰🇲',
            'name' => 'Comoros',
            'numeric' => '174',
            'official_name' => 'Union of the Comoros',
        ],
        'KN' => [
            'alpha_3' => 'KNA',
            'flag' => '🇰🇳',
            'name' => 'Saint Kitts and Nevis',
            'numeric' => '659',
        ],
        'KP' => [
            'alpha_3' => 'PRK',
            'common_name' => 'North Korea',
            'flag' => '🇰🇵',
            'name' => 'Korea, Democratic People\'s Republic of',
            'numeric' => '408',
            'official_name' => 'Democratic People\'s Republic of Korea',
        ],
        'KR' => [
            'alpha_3' => 'KOR',
            'common_name' => 'South Korea',
            'flag' => '🇰🇷',
            'name' => 'Korea, Republic of',
            'numeric' => '410',
        ],
        'KW' => [
            'alpha_3' => 'KWT',
            'flag' => '🇰🇼',
            'name' => 'Kuwait',
            'numeric' => '414',
            'official_name' => 'State of Kuwait',
        ],
        'KY' => [
            'alpha_3' => 'CYM',
            'flag' => '🇰🇾',
            'name' => 'Cayman Islands',
            'numeric' => '136',
        ],
        'KZ' => [
            'alpha_3' => 'KAZ',
            'flag' => '🇰🇿',
            'name' => 'Kazakhstan',
            'numeric' => '398',
            'official_name' => 'Republic of Kazakhstan',
        ],
        'LA' => [
            'alpha_3' => 'LAO',
            'common_name' => 'Laos',
            'flag' => '🇱🇦',
            'name' => 'Lao People\'s Democratic Republic',
            'numeric' => '418',
        ],
        'LB' => [
            'alpha_3' => 'LBN',
            'flag' => '🇱🇧',
            'name' => 'Lebanon',
            'numeric' => '422',
            'official_name' => 'Lebanese Republic',
        ],
        'LC' => [
            'alpha_3' => 'LCA',
            'flag' => '🇱🇨',
            'name' => 'Saint Lucia',
            'numeric' => '662',
        ],
        'LI' => [
            'alpha_3' => 'LIE',
            'flag' => '🇱🇮',
            'name' => 'Liechtenstein',
            'numeric' => '438',
            'official_name' => 'Principality of Liechtenstein',
        ],
        'LK' => [
            'alpha_3' => 'LKA',
            'flag' => '🇱🇰',
            'name' => 'Sri Lanka',
            'numeric' => '144',
            'official_name' => 'Democratic Socialist Republic of Sri Lanka',
        ],
        'LR' => [
            'alpha_3' => 'LBR',
            'flag' => '🇱🇷',
            'name' => 'Liberia',
            'numeric' => '430',
            'official_name' => 'Republic of Liberia',
        ],
        'LS' => [
            'alpha_3' => 'LSO',
            'flag' => '🇱🇸',
            'name' => 'Lesotho',
            'numeric' => '426',
            'official_name' => 'Kingdom of Lesotho',
        ],
        'LT' => [
            'alpha_3' => 'LTU',
            'flag' => '🇱🇹',
            'name' => 'Lithuania',
            'numeric' => '440',
            'official_name' => 'Republic of Lithuania',
        ],
        'LU' => [
            'alpha_3' => 'LUX',
            'flag' => '🇱🇺',
            'name' => 'Luxembourg',
            'numeric' => '442',
            'official_name' => 'Grand Duchy of Luxembourg',
        ],
        'LV' => [
            'alpha_3' => 'LVA',
            'flag' => '🇱🇻',
            'name' => 'Latvia',
            'numeric' => '428',
            'official_name' => 'Republic of Latvia',
        ],
        'LY' => [
            'alpha_3' => 'LBY',
            'flag' => '🇱🇾',
            'name' => 'Libya',
            'numeric' => '434',
            'official_name' => 'Libya',
        ],
        'MA' => [
            'alpha_3' => 'MAR',
            'flag' => '🇲🇦',
            'name' => 'Morocco',
            'numeric' => '504',
            'official_name' => 'Kingdom of Morocco',
        ],
        'MC' => [
            'alpha_3' => 'MCO',
            'flag' => '🇲🇨',
            'name' => 'Monaco',
            'numeric' => '492',
            'official_name' => 'Principality of Monaco',
        ],
        'MD' => [
            'alpha_3' => 'MDA',
            'common_name' => 'Moldova',
            'flag' => '🇲🇩',
            'name' => 'Moldova, Republic of',
            'numeric' => '498',
            'official_name' => 'Republic of Moldova',
        ],
        'ME' => [
            'alpha_3' => 'MNE',
            'flag' => '🇲🇪',
            'name' => 'Montenegro',
            'numeric' => '499',
            'official_name' => 'Montenegro',
        ],
        'MF' => [
            'alpha_3' => 'MAF',
            'flag' => '🇲🇫',
            'name' => 'Saint Martin (French part)',
            'numeric' => '663',
        ],
        'MG' => [
            'alpha_3' => 'MDG',
            'flag' => '🇲🇬',
            'name' => 'Madagascar',
            'numeric' => '450',
            'official_name' => 'Republic of Madagascar',
        ],
        'MH' => [
            'alpha_3' => 'MHL',
            'flag' => '🇲🇭',
            'name' => 'Marshall Islands',
            'numeric' => '584',
            'official_name' => 'Republic of the Marshall Islands',
        ],
        'MK' => [
            'alpha_3' => 'MKD',
            'flag' => '🇲🇰',
            'name' => 'North Macedonia',
            'numeric' => '807',
            'official_name' => 'Republic of North Macedonia',
        ],
        'ML' => [
            'alpha_3' => 'MLI',
            'flag' => '🇲🇱',
            'name' => 'Mali',
            'numeric' => '466',
            'official_name' => 'Republic of Mali',
        ],
        'MM' => [
            'alpha_3' => 'MMR',
            'flag' => '🇲🇲',
            'name' => 'Myanmar',
            'numeric' => '104',
            'official_name' => 'Republic of Myanmar',
        ],
        'MN' => [
            'alpha_3' => 'MNG',
            'flag' => '🇲🇳',
            'name' => 'Mongolia',
            'numeric' => '496',
        ],
        'MO' => [
            'alpha_3' => 'MAC',
            'flag' => '🇲🇴',
            'name' => 'Macao',
            'numeric' => '446',
            'official_name' => 'Macao Special Administrative Region of China',
        ],
        'MP' => [
            'alpha_3' => 'MNP',
            'flag' => '🇲🇵',
            'name' => 'Northern Mariana Islands',
            'numeric' => '580',
            'official_name' => 'Commonwealth of the Northern Mariana Islands',
        ],
        'MQ' => [
            'alpha_3' => 'MTQ',
            'flag' => '🇲🇶',
            'name' => 'Martinique',
            'numeric' => '474',
        ],
        'MR' => [
            'alpha_3' => 'MRT',
            'flag' => '🇲🇷',
            'name' => 'Mauritania',
            'numeric' => '478',
            'official_name' => 'Islamic Republic of Mauritania',
        ],
        'MS' => [
            'alpha_3' => 'MSR',
            'flag' => '🇲🇸',
            'name' => 'Montserrat',
            'numeric' => '500',
        ],
        'MT' => [
            'alpha_3' => 'MLT',
            'flag' => '🇲🇹',
            'name' => 'Malta',
            'numeric' => '470',
            'official_name' => 'Republic of Malta',
        ],
        'MU' => [
            'alpha_3' => 'MUS',
            'flag' => '🇲🇺',
            'name' => 'Mauritius',
            'numeric' => '480',
            'official_name' => 'Republic of Mauritius',
        ],
        'MV' => [
            'alpha_3' => 'MDV',
            'flag' => '🇲🇻',
            'name' => 'Maldives',
            'numeric' => '462',
            'official_name' => 'Republic of Maldives',
        ],
        'MW' => [
            'alpha_3' => 'MWI',
            'flag' => '🇲🇼',
            'name' => 'Malawi',
            'numeric' => '454',
            'official_name' => 'Republic of Malawi',
        ],
        'MX' => [
            'alpha_3' => 'MEX',
            'flag' => '🇲🇽',
            'name' => 'Mexico',
            'numeric' => '484',
            'official_name' => 'United Mexican States',
        ],
        'MY' => [
            'alpha_3' => 'MYS',
            'flag' => '🇲🇾',
            'name' => 'Malaysia',
            'numeric' => '458',
        ],
        'MZ' => [
            'alpha_3' => 'MOZ',
            'flag' => '🇲🇿',
            'name' => 'Mozambique',
            'numeric' => '508',
            'official_name' => 'Republic of Mozambique',
        ],
        'NA' => [
            'alpha_3' => 'NAM',
            'flag' => '🇳🇦',
            'name' => 'Namibia',
            'numeric' => '516',
            'official_name' => 'Republic of Namibia',
        ],
        'NC' => [
            'alpha_3' => 'NCL',
            'flag' => '🇳🇨',
            'name' => 'New Caledonia',
            'numeric' => '540',
        ],
        'NE' => [
            'alpha_3' => 'NER',
            'flag' => '🇳🇪',
            'name' => 'Niger',
            'numeric' => '562',
            'official_name' => 'Republic of the Niger',
        ],
        'NF' => [
            'alpha_3' => 'NFK',
            'flag' => '🇳🇫',
            'name' => 'Norfolk Island',
            'numeric' => '574',
        ],
        'NG' => [
            'alpha_3' => 'NGA',
            'flag' => '🇳🇬',
            'name' => 'Nigeria',
            'numeric' => '566',
            'official_name' => 'Federal Republic of Nigeria',
        ],
        'NI' => [
            'alpha_3' => 'NIC',
            'flag' => '🇳🇮',
            'name' => 'Nicaragua',
            'numeric' => '558',
            'official_name' => 'Republic of Nicaragua',
        ],
        'NL' => [
            'alpha_3' => 'NLD',
            'flag' => '🇳🇱',
            'name' => 'Netherlands',
            'numeric' => '528',
            'official_name' => 'Kingdom of the Netherlands',
        ],
        'NO' => [
            'alpha_3' => 'NOR',
            'flag' => '🇳🇴',
            'name' => 'Norway',
            'numeric' => '578',
            'official_name' => 'Kingdom of Norway',
        ],
        'NP' => [
            'alpha_3' => 'NPL',
            'flag' => '🇳🇵',
            'name' => 'Nepal',
            'numeric' => '524',
            'official_name' => 'Federal Democratic Republic of Nepal',
        ],
        'NR' => [
            'alpha_3' => 'NRU',
            'flag' => '🇳🇷',
            'name' => 'Nauru',
            'numeric' => '520',
            'official_name' => 'Republic of Nauru',
        ],
        'NU' => [
            'alpha_3' => 'NIU',
            'flag' => '🇳🇺',
            'name' => 'Niue',
            'numeric' => '570',
            'official_name' => 'Niue',
        ],
        'NZ' => [
            'alpha_3' => 'NZL',
            'flag' => '🇳🇿',
            'name' => 'New Zealand',
            'numeric' => '554',
        ],
        'OM' => [
            'alpha_3' => 'OMN',
            'flag' => '🇴🇲',
            'name' => 'Oman',
            'numeric' => '512',
            'official_name' => 'Sultanate of Oman',
        ],
        'PA' => [
            'alpha_3' => 'PAN',
            'flag' => '🇵🇦',
            'name' => 'Panama',
            'numeric' => '591',
            'official_name' => 'Republic of Panama',
        ],
        'PE' => [
            'alpha_3' => 'PER',
            'flag' => '🇵🇪',
            'name' => 'Peru',
            'numeric' => '604',
            'official_name' => 'Republic of Peru',
        ],
        'PF' => [
            'alpha_3' => 'PYF',
            'flag' => '🇵🇫',
            'name' => 'French Polynesia',
            'numeric' => '258',
        ],
        'PG' => [
            'alpha_3' => 'PNG',
            'flag' => '🇵🇬',
            'name' => 'Papua New Guinea',
            'numeric' => '598',
            'official_name' => 'Independent State of Papua New Guinea',
        ],
        'PH' => [
            'alpha_3' => 'PHL',
            'flag' => '🇵🇭',
            'name' => 'Philippines',
            'numeric' => '608',
            'official_name' => 'Republic of the Philippines',
        ],
        'PK' => [
            'alpha_3' => 'PAK',
            'flag' => '🇵🇰',
            'name' => 'Pakistan',
            'numeric' => '586',
            'official_name' => 'Islamic Republic of Pakistan',
        ],
        'PL' => [
            'alpha_3' => 'POL',
            'flag' => '🇵🇱',
            'name' => 'Poland',
            'numeric' => '616',
            'official_name' => 'Republic of Poland',
        ],
        'PM' => [
            'alpha_3' => 'SPM',
            'flag' => '🇵🇲',
            'name' => 'Saint Pierre and Miquelon',
            'numeric' => '666',
        ],
        'PN' => [
            'alpha_3' => 'PCN',
            'flag' => '🇵🇳',
            'name' => 'Pitcairn',
            'numeric' => '612',
        ],
        'PR' => [
            'alpha_3' => 'PRI',
            'flag' => '🇵🇷',
            'name' => 'Puerto Rico',
            'numeric' => '630',
        ],
        'PS' => [
            'alpha_3' => 'PSE',
            'flag' => '🇵🇸',
            'name' => 'Palestine, State of',
            'numeric' => '275',
            'official_name' => 'the State of Palestine',
        ],
        'PT' => [
            'alpha_3' => 'PRT',
            'flag' => '🇵🇹',
            'name' => 'Portugal',
            'numeric' => '620',
            'official_name' => 'Portuguese Republic',
        ],
        'PW' => [
            'alpha_3' => 'PLW',
            'flag' => '🇵🇼',
            'name' => 'Palau',
            'numeric' => '585',
            'official_name' => 'Republic of Palau',
        ],
        'PY' => [
            'alpha_3' => 'PRY',
            'flag' => '🇵🇾',
            'name' => 'Paraguay',
            'numeric' => '600',
            'official_name' => 'Republic of Paraguay',
        ],
        'QA' => [
            'alpha_3' => 'QAT',
            'flag' => '🇶🇦',
            'name' => 'Qatar',
            'numeric' => '634',
            'official_name' => 'State of Qatar',
        ],
        'RE' => [
            'alpha_3' => 'REU',
            'flag' => '🇷🇪',
            'name' => 'Réunion',
            'numeric' => '638',
        ],
        'RO' => [
            'alpha_3' => 'ROU',
            'flag' => '🇷🇴',
            'name' => 'Romania',
            'numeric' => '642',
        ],
        'RS' => [
            'alpha_3' => 'SRB',
            'flag' => '🇷🇸',
            'name' => 'Serbia',
            'numeric' => '688',
            'official_name' => 'Republic of Serbia',
        ],
        'RU' => [
            'alpha_3' => 'RUS',
            'flag' => '🇷🇺',
            'name' => 'Russian Federation',
            'numeric' => '643',
        ],
        'RW' => [
            'alpha_3' => 'RWA',
            'flag' => '🇷🇼',
            'name' => 'Rwanda',
            'numeric' => '646',
            'official_name' => 'Rwandese Republic',
        ],
        'SA' => [
            'alpha_3' => 'SAU',
            'flag' => '🇸🇦',
            'name' => 'Saudi Arabia',
            'numeric' => '682',
            'official_name' => 'Kingdom of Saudi Arabia',
        ],
        'SB' => [
            'alpha_3' => 'SLB',
            'flag' => '🇸🇧',
            'name' => 'Solomon Islands',
            'numeric' => '090',
        ],
        'SC' => [
            'alpha_3' => 'SYC',
            'flag' => '🇸🇨',
            'name' => 'Seychelles',
            'numeric' => '690',
            'official_name' => 'Republic of Seychelles',
        ],
        'SD' => [
            'alpha_3' => 'SDN',
            'flag' => '🇸🇩',
            'name' => 'Sudan',
            'numeric' => '729',
            'official_name' => 'Republic of the Sudan',
        ],
        'SE' => [
            'alpha_3' => 'SWE',
            'flag' => '🇸🇪',
            'name' => 'Sweden',
            'numeric' => '752',
            'official_name' => 'Kingdom of Sweden',
        ],
        'SG' => [
            'alpha_3' => 'SGP',
            'flag' => '🇸🇬',
            'name' => 'Singapore',
            'numeric' => '702',
            'official_name' => 'Republic of Singapore',
        ],
        'SH' => [
            'alpha_3' => 'SHN',
            'flag' => '🇸🇭',
            'name' => 'Saint Helena, Ascension and Tristan da Cunha',
            'numeric' => '654',
        ],
        'SI' => [
            'alpha_3' => 'SVN',
            'flag' => '🇸🇮',
            'name' => 'Slovenia',
            'numeric' => '705',
            'official_name' => 'Republic of Slovenia',
        ],
        'SJ' => [
            'alpha_3' => 'SJM',
            'flag' => '🇸🇯',
            'name' => 'Svalbard and Jan Mayen',
            'numeric' => '744',
        ],
        'SK' => [
            'alpha_3' => 'SVK',
            'flag' => '🇸🇰',
            'name' => 'Slovakia',
            'numeric' => '703',
            'official_name' => 'Slovak Republic',
        ],
        'SL' => [
            'alpha_3' => 'SLE',
            'flag' => '🇸🇱',
            'name' => 'Sierra Leone',
            'numeric' => '694',
            'official_name' => 'Republic of Sierra Leone',
        ],
        'SM' => [
            'alpha_3' => 'SMR',
            'flag' => '🇸🇲',
            'name' => 'San Marino',
            'numeric' => '674',
            'official_name' => 'Republic of San Marino',
        ],
        'SN' => [
            'alpha_3' => 'SEN',
            'flag' => '🇸🇳',
            'name' => 'Senegal',
            'numeric' => '686',
            'official_name' => 'Republic of Senegal',
        ],
        'SO' => [
            'alpha_3' => 'SOM',
            'flag' => '🇸🇴',
            'name' => 'Somalia',
            'numeric' => '706',
            'official_name' => 'Federal Republic of Somalia',
        ],
        'SR' => [
            'alpha_3' => 'SUR',
            'flag' => '🇸🇷',
            'name' => 'Suriname',
            'numeric' => '740',
            'official_name' => 'Republic of Suriname',
        ],
        'SS' => [
            'alpha_3' => 'SSD',
            'flag' => '🇸🇸',
            'name' => 'South Sudan',
            'numeric' => '728',
            'official_name' => 'Republic of South Sudan',
        ],
        'ST' => [
            'alpha_3' => 'STP',
            'flag' => '🇸🇹',
            'name' => 'Sao Tome and Principe',
            'numeric' => '678',
            'official_name' => 'Democratic Republic of Sao Tome and Principe',
        ],
        'SV' => [
            'alpha_3' => 'SLV',
            'flag' => '🇸🇻',
            'name' => 'El Salvador',
            'numeric' => '222',
            'official_name' => 'Republic of El Salvador',
        ],
        'SX' => [
            'alpha_3' => 'SXM',
            'flag' => '🇸🇽',
            'name' => 'Sint Maarten (Dutch part)',
            'numeric' => '534',
            'official_name' => 'Sint Maarten (Dutch part)',
        ],
        'SY' => [
            'alpha_3' => 'SYR',
            'common_name' => 'Syria',
            'flag' => '🇸🇾',
            'name' => 'Syrian Arab Republic',
            'numeric' => '760',
        ],
        'SZ' => [
            'alpha_3' => 'SWZ',
            'flag' => '🇸🇿',
            'name' => 'Eswatini',
            'numeric' => '748',
            'official_name' => 'Kingdom of Eswatini',
        ],
        'TC' => [
            'alpha_3' => 'TCA',
            'flag' => '🇹🇨',
            'name' => 'Turks and Caicos Islands',
            'numeric' => '796',
        ],
        'TD' => [
            'alpha_3' => 'TCD',
            'flag' => '🇹🇩',
            'name' => 'Chad',
            'numeric' => '148',
            'official_name' => 'Republic of Chad',
        ],
        'TF' => [
            'alpha_3' => 'ATF',
            'flag' => '🇹🇫',
            'name' => 'French Southern Territories',
            'numeric' => '260',
        ],
        'TG' => [
            'alpha_3' => 'TGO',
            'flag' => '🇹🇬',
            'name' => 'Togo',
            'numeric' => '768',
            'official_name' => 'Togolese Republic',
        ],
        'TH' => [
            'alpha_3' => 'THA',
            'flag' => '🇹🇭',
            'name' => 'Thailand',
            'numeric' => '764',
            'official_name' => 'Kingdom of Thailand',
        ],
        'TJ' => [
            'alpha_3' => 'TJK',
            'flag' => '🇹🇯',
            'name' => 'Tajikistan',
            'numeric' => '762',
            'official_name' => 'Republic of Tajikistan',
        ],
        'TK' => [
            'alpha_3' => 'TKL',
            'flag' => '🇹🇰',
            'name' => 'Tokelau',
            'numeric' => '772',
        ],
        'TL' => [
            'alpha_3' => 'TLS',
            'flag' => '🇹🇱',
            'name' => 'Timor-Leste',
            'numeric' => '626',
            'official_name' => 'Democratic Republic of Timor-Leste',
        ],
        'TM' => [
            'alpha_3' => 'TKM',
            'flag' => '🇹🇲',
            'name' => 'Turkmenistan',
            'numeric' => '795',
        ],
        'TN' => [
            'alpha_3' => 'TUN',
            'flag' => '🇹🇳',
            'name' => 'Tunisia',
            'numeric' => '788',
            'official_name' => 'Republic of Tunisia',
        ],
        'TO' => [
            'alpha_3' => 'TON',
            'flag' => '🇹🇴',
            'name' => 'Tonga',
            'numeric' => '776',
            'official_name' => 'Kingdom of Tonga',
        ],
        'TR' => [
            'alpha_3' => 'TUR',
            'flag' => '🇹🇷',
            'name' => 'Türkiye',
            'numeric' => '792',
            'official_name' => 'Republic of Türkiye',
        ],
        'TT' => [
            'alpha_3' => 'TTO',
            'flag' => '🇹🇹',
            'name' => 'Trinidad and Tobago',
            'numeric' => '780',
            'official_name' => 'Republic of Trinidad and Tobago',
        ],
        'TV' => [
            'alpha_3' => 'TUV',
            'flag' => '🇹🇻',
            'name' => 'Tuvalu',
            'numeric' => '798',
        ],
        'TW' => [
            'alpha_3' => 'TWN',
            'common_name' => 'Taiwan',
            'flag' => '🇹🇼',
            'name' => 'Taiwan, Province of China',
            'numeric' => '158',
            'official_name' => 'Taiwan, Province of China',
        ],
        'TZ' => [
            'alpha_3' => 'TZA',
            'common_name' => 'Tanzania',
            'flag' => '🇹🇿',
            'name' => 'Tanzania, United Republic of',
            'numeric' => '834',
            'official_name' => 'United Republic of Tanzania',
        ],
        'UA' => [
            'alpha_3' => 'UKR',
            'flag' => '🇺🇦',
            'name' => 'Ukraine',
            'numeric' => '804',
        ],
        'UG' => [
            'alpha_3' => 'UGA',
            'flag' => '🇺🇬',
            'name' => 'Uganda',
            'numeric' => '800',
            'official_name' => 'Republic of Uganda',
        ],
        'UM' => [
            'alpha_3' => 'UMI',
            'flag' => '🇺🇲',
            'name' => 'United States Minor Outlying Islands',
            'numeric' => '581',
        ],
        'US' => [
            'alpha_3' => 'USA',
            'flag' => '🇺🇸',
            'name' => 'United States',
            'numeric' => '840',
            'official_name' => 'United States of America',
        ],
        'UY' => [
            'alpha_3' => 'URY',
            'flag' => '🇺🇾',
            'name' => 'Uruguay',
            'numeric' => '858',
            'official_name' => 'Eastern Republic of Uruguay',
        ],
        'UZ' => [
            'alpha_3' => 'UZB',
            'flag' => '🇺🇿',
            'name' => 'Uzbekistan',
            'numeric' => '860',
            'official_name' => 'Republic of Uzbekistan',
        ],
        'VA' => [
            'alpha_3' => 'VAT',
            'flag' => '🇻🇦',
            'name' => 'Holy See (Vatican City State)',
            'numeric' => '336',
        ],
        'VC' => [
            'alpha_3' => 'VCT',
            'flag' => '🇻🇨',
            'name' => 'Saint Vincent and the Grenadines',
            'numeric' => '670',
        ],
        'VE' => [
            'alpha_3' => 'VEN',
            'common_name' => 'Venezuela',
            'flag' => '🇻🇪',
            'name' => 'Venezuela, Bolivarian Republic of',
            'numeric' => '862',
            'official_name' => 'Bolivarian Republic of Venezuela',
        ],
        'VG' => [
            'alpha_3' => 'VGB',
            'flag' => '🇻🇬',
            'name' => 'Virgin Islands, British',
            'numeric' => '092',
            'official_name' => 'British Virgin Islands',
        ],
        'VI' => [
            'alpha_3' => 'VIR',
            'flag' => '🇻🇮',
            'name' => 'Virgin Islands, U.S.',
            'numeric' => '850',
            'official_name' => 'Virgin Islands of the United States',
        ],
        'VN' => [
            'alpha_3' => 'VNM',
            'common_name' => 'Vietnam',
            'flag' => '🇻🇳',
            'name' => 'Viet Nam',
            'numeric' => '704',
            'official_name' => 'Socialist Republic of Viet Nam',
        ],
        'VU' => [
            'alpha_3' => 'VUT',
            'flag' => '🇻🇺',
            'name' => 'Vanuatu',
            'numeric' => '548',
            'official_name' => 'Republic of Vanuatu',
        ],
        'WF' => [
            'alpha_3' => 'WLF',
            'flag' => '🇼🇫',
            'name' => 'Wallis and Futuna',
            'numeric' => '876',
        ],
        'WS' => [
            'alpha_3' => 'WSM',
            'flag' => '🇼🇸',
            'name' => 'Samoa',
            'numeric' => '882',
            'official_name' => 'Independent State of Samoa',
        ],
        'YE' => [
            'alpha_3' => 'YEM',
            'flag' => '🇾🇪',
            'name' => 'Yemen',
            'numeric' => '887',
            'official_name' => 'Republic of Yemen',
        ],
        'YT' => [
            'alpha_3' => 'MYT',
            'flag' => '🇾🇹',
            'name' => 'Mayotte',
            'numeric' => '175',
        ],
        'ZA' => [
            'alpha_3' => 'ZAF',
            'flag' => '🇿🇦',
            'name' => 'South Africa',
            'numeric' => '710',
            'official_name' => 'Republic of South Africa',
        ],
        'ZM' => [
            'alpha_3' => 'ZMB',
            'flag' => '🇿🇲',
            'name' => 'Zambia',
            'numeric' => '894',
            'official_name' => 'Republic of Zambia',
        ],
        'ZW' => [
            'alpha_3' => 'ZWE',
            'flag' => '🇿🇼',
            'name' => 'Zimbabwe',
            'numeric' => '716',
            'official_name' => 'Republic of Zimbabwe',
        ],
    ];

    /**
     * @var Country[]
     */
    private array $countries = [];

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        foreach ($this->rawData as $alpha2Code => $countryData) {
            $this->countries[$alpha2Code] = new Country(
                $alpha2Code,
                $countryData['alpha_3'],
                $countryData['name'],
                $countryData['numeric'],
                $countryData['flag'],
                $countryData['official_name'] ?? null,
            );
        }
        $event = new BeforeCountriesEvaluatedEvent($this->countries);
        $eventDispatcher->dispatch($event);
        $this->countries = $event->getCountries();
    }

    /**
     * @return Country[]
     */
    public function getAll(): array
    {
        return $this->countries;
    }

    /**
     * Searches for a matching country with fallback; uses Alpha2 ISO-Code by
     * default, and if that does not match, compare the input $isoCode
     * as an Alpha3 ISO-Code.
     */
    public function getByIsoCode(string $isoCode): ?Country
    {
        $isoCode = strtoupper($isoCode);
        if (isset($this->countries[$isoCode])) {
            return $this->countries[$isoCode];
        }
        foreach ($this->countries as $country) {
            if ($country->getAlpha3IsoCode() === $isoCode) {
                return $country;
            }
        }
        return null;
    }

    public function getByAlpha2IsoCode(string $isoCode): ?Country
    {
        $isoCode = strtoupper($isoCode);
        return $this->countries[$isoCode] ?? null;
    }

    public function getByAlpha3IsoCode(string $isoCode): ?Country
    {
        $isoCode = strtoupper($isoCode);
        foreach ($this->countries as $country) {
            if ($country->getAlpha3IsoCode() === $isoCode) {
                return $country;
            }
        }
        return null;
    }

    public function getByEnglishName(string $name): ?Country
    {
        foreach ($this->countries as $country) {
            if ($country->getName() === $name) {
                return $country;
            }
        }
        return null;
    }

    /**
     * @return array<string, Country>
     */
    public function getFiltered(CountryFilter $filter): array
    {
        if (empty($filter->getOnlyCountries()) && empty($filter->getExcludeCountries())) {
            return $this->countries;
        }

        if (!empty($filter->getExcludeCountries())) {
            $possibleCountries = [];
            foreach ($this->countries as $country) {
                if (!in_array($country->getAlpha2IsoCode(), $filter->getExcludeCountries(), true)
                    && !in_array($country->getAlpha3IsoCode(), $filter->getExcludeCountries(), true)) {
                    $possibleCountries[$country->getAlpha2IsoCode()] = $country;
                }
            }
        } else {
            $possibleCountries = $this->countries;
        }

        if (empty($filter->getOnlyCountries())) {
            return $possibleCountries;
        }

        $countries = [];
        foreach ($filter->getOnlyCountries() as $countryCode) {
            $country = $this->getByIsoCode($countryCode);
            if ($country !== null && isset($possibleCountries[$country->getAlpha2IsoCode()])) {
                $countries[$country->getAlpha2IsoCode()] = $country;
            }
        }

        return $countries;
    }
}
