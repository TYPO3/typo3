#!/usr/bin/env php
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

use Symfony\Component\Translation\MessageCatalogue;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Utility\ArrayUtility;

if (PHP_SAPI !== 'cli') {
    die('Script must be called from command line.' . chr(10));
}

require __DIR__ . '/../../vendor/autoload.php';

// Basic Information
// * ISO 639-1 -> languages in two letter code
// * ISO 639-2 -> languages in two/three letter code
// * ISO 3166-1 -> countries
// * ISO 3166-2 -> regions in countries (= states)

/**
 * This is a specific Xliff Dumper subclass, as TYPO3 has some specialities
 * when dealing with XLIFF ("original", "date" or "product-name", and "id" or special handling of english labels)
 */
class XliffDumper extends \Symfony\Component\Translation\Dumper\XliffFileDumper
{
    public function dump(MessageCatalogue $messages, array $options = [])
    {
        if ($messages->getLocale() === 'en') {
            $this->setRelativePathTemplate('%domain%.%extension%');
        } else {
            $this->setRelativePathTemplate('%locale%.%domain%.%extension%');
        }
        parent::dump($messages, $options);
    }

    public function formatCatalogue(MessageCatalogue $messages, string $domain, array $options = []): string
    {
        $isBaseLanguage = $messages->getLocale() === 'en';
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;

        $xliff = $dom->appendChild($dom->createElement('xliff'));
        $xliff->setAttribute('version', '1.2');
        $xliff->setAttribute('xmlns', 'urn:oasis:names:tc:xliff:document:1.2');

        $xliffFile = $xliff->appendChild($dom->createElement('file'));
        $xliffFile->setAttribute('source-language', 'en');
        if (!$isBaseLanguage) {
            $xliffFile->setAttribute('target-language', str_replace('_', '-', $messages->getLocale()));
        }
        $xliffFile->setAttribute('datatype', 'plaintext');
        $xliffFile->setAttribute('original', 'EXT:core/Resources/Private/Language/countries.xlf');
        $xliffFile->setAttribute('product-name', 'typo3/cms-core');

        $xliffBody = $xliffFile->appendChild($dom->createElement('body'));
        foreach ($messages->all($domain) as $source => $target) {
            $translation = $dom->createElement('trans-unit');

            $translation->setAttribute('id', $source);
            $translation->setAttribute('resname', $source);

            $s = $translation->appendChild($dom->createElement('source'));
            if ($isBaseLanguage) {
                $s->appendChild($dom->createTextNode($target));
            } else {
                $translation->setAttribute('approved', 'yes');
                $s->appendChild($dom->createTextNode($messages->getFallbackCatalogue()->get($source, $domain) ?: $source));
                // Does the target contain characters requiring a CDATA section?
                $text = preg_match('/[&<>]/', $target) === 1 ? $dom->createCDATASection($target) : $dom->createTextNode($target);

                $targetElement = $dom->createElement('target');
                $t = $translation->appendChild($targetElement);
                $t->appendChild($text);
            }
            $xliffBody->appendChild($translation);
        }
        return $dom->saveXML();
    }
}

// 0. Preparations
$devFlag = false;
$baseDirectory = __DIR__ . '/../../vendor/sokil/php-isocodes-db-i18n';
$targetDirectory = __DIR__ . '/../../typo3/sysext/core/Resources/Private/Database';
$targetXliffDirectory = __DIR__ . '/../../typo3/sysext/core/Resources/Private/Language/Iso';
$countryProviderFileLocation = __DIR__ . '/../../typo3/sysext/core/Classes/Country/CountryProvider.php';
@mkdir($targetDirectory, 0777, true);
@mkdir($targetXliffDirectory, 0777, true);

// 1. Get all supported TYPO3 languages
$typo3Locales = new Locales();
$languages = $typo3Locales->getLanguages();
unset($languages['default']);
$supportedLanguagesInTypo3 = array_keys($languages);

// 2. Countries
// Load all available countries in english
$countries = json_decode(file_get_contents($baseDirectory . '/databases/iso_3166-1.json'), true);

$countries = reset($countries);
$defaultCatalogue = new MessageCatalogue('en');
$xliffDumper = new XliffDumper();

$countriesByCountryCode = [];
foreach ($countries as $countryDetails) {
    $countryCode = $countryDetails['alpha_2'];
    unset($countryDetails['alpha_2']);
    $countriesByCountryCode[$countryCode] = $countryDetails;
    $defaultCatalogue->add([$countryCode . '.name' => $countryDetails['name']], 'countries');
    if (isset($countryDetails['official_name'])) {
        $defaultCatalogue->add([$countryCode . '.official_name' => $countryDetails['official_name']], 'countries');
    }
}
ksort($countriesByCountryCode, SORT_NATURAL);
$xliffDumper->dump($defaultCatalogue, ['path' => $targetXliffDirectory]);
$countryProviderFileContents = file_get_contents($countryProviderFileLocation);
$newCountryContents = ArrayUtility::arrayExport($countriesByCountryCode);
$newCountryContents = str_replace("\n", "\n    ", $newCountryContents);
$countryProviderFileContents = preg_replace('/private array \$rawData = [^;]*;/u', 'private array $rawData = ' . $newCountryContents . ';', $countryProviderFileContents);
file_put_contents($countryProviderFileLocation, $countryProviderFileContents);

// 2. Load labels that are translated for countries ("name" and "official name")
$loader = new \Symfony\Component\Translation\Loader\PoFileLoader();
foreach ($supportedLanguagesInTypo3 as $languageKey) {
    $translationFile = $baseDirectory . '/messages/' . $languageKey . '/LC_MESSAGES/3166-1.po';
    if (!file_exists($translationFile)) {
        continue;
    }
    $catalogue = $loader->load($translationFile, $languageKey);
    $cleanedCatalogue = new MessageCatalogue(str_replace('_', '-', $languageKey));
    $cleanedCatalogue->addFallbackCatalogue($defaultCatalogue);
    foreach ($countriesByCountryCode as $countryCode => $countryDetails) {
        $countryName = $countryDetails['name'];
        $translatedCountryName = $catalogue->get($countryName);
        if ($translatedCountryName) {
            $cleanedCatalogue->add([$countryCode . '.name' => $translatedCountryName], 'countries');
        }
        if (isset($countryDetails['official_name'])) {
            $countryName = $countryDetails['official_name'];
            $translatedCountryName = $catalogue->get($countryName);
            if ($translatedCountryName) {
                $cleanedCatalogue->add([$countryCode . '.official_name' => $translatedCountryName], 'countries');
            }
        }
    }
    $xliffDumper->dump($cleanedCatalogue, ['path' => $targetXliffDirectory]);
}

return;
// This part will be added later-on
// 3. Language Scripts
$scripts = json_decode(file_get_contents($baseDirectory . '/databases/iso_15924.json'), true);

$defaultCatalogue = new MessageCatalogue('en');
$scriptsByScriptCode = [];
$scripts = reset($scripts);
foreach ($scripts as $scriptDetails) {
    $scriptCode = $scriptDetails['alpha_4'];
    unset($scriptDetails['alpha_4']);
    $scriptsByScriptCode[$scriptCode] = $scriptDetails;
    $defaultCatalogue->add([$scriptCode . '.name' => $scriptDetails['name']], 'scripts');
}
ksort($scriptsByScriptCode, SORT_NATURAL);
$xliffDumper->dump($defaultCatalogue, ['path' => $targetXliffDirectory]);
file_put_contents($targetDirectory . '/iso_15924_scripts.json', json_encode($scriptsByScriptCode, $devFlag ? JSON_PRETTY_PRINT : 0));

// Translated scripts
foreach ($supportedLanguagesInTypo3 as $languageKey) {
    $translationFile = $baseDirectory . '/messages/' . $languageKey . '/LC_MESSAGES/15924.po';
    if (!file_exists($translationFile)) {
        continue;
    }
    $catalogue = $loader->load($translationFile, $languageKey);
    $cleanedCatalogue = new MessageCatalogue(str_replace('_', '-', $languageKey));
    $cleanedCatalogue->addFallbackCatalogue($defaultCatalogue);
    foreach ($scriptsByScriptCode as $scriptCode => $scriptDetails) {
        $translatedName = $catalogue->get($scriptDetails['name']);
        if ($translatedName) {
            $cleanedCatalogue->add([$scriptCode . '.name' => $translatedName], 'scripts');
        }
    }
    if ($cleanedCatalogue->all() !== []) {
        $xliffDumper->dump($cleanedCatalogue, ['path' => $targetXliffDirectory]);
    }
}
