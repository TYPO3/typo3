<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Service;

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

use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Service class handling language pack details
 * Used by 'manage language packs' module and 'language packs command'
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class LanguagePackService
{
    /**
     * @var Locales
     */
    protected $locales;

    /**
     * @var Registry
     */
    protected $registry;

    public function __construct()
    {
        $this->locales = GeneralUtility::makeInstance(Locales::class);
        $this->registry = GeneralUtility::makeInstance(Registry::class);
    }

    /**
     * Get list of available languages
     *
     * @return array iso=>name
     */
    public function getAvailableLanguages(): array
    {
        return $this->locales->getLanguages();
    }

    /**
     * List of languages active in this instance
     *
     * @return array
     */
    public function getActiveLanguages(): array
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lang']['availableLanguages'] ?? [];
    }

    /**
     * Create an array with language details: active or not, iso codes, last update, ...
     *
     * @return array
     */
    public function getLanguageDetails(): array
    {
        $availableLanguages = $this->getAvailableLanguages();
        $activeLanguages = $this->getActiveLanguages();
        $languages = [];
        foreach ($availableLanguages as $iso => $name) {
            if ($iso === 'default') {
                continue;
            }
            $lastUpdate = $this->registry->get('languagePacks', $iso);
            $languages[] = [
                'iso' => $iso,
                'name' => $name,
                'active' => in_array($iso, $activeLanguages, true),
                'lastUpdate' => $this->getFormattedDate($lastUpdate),
                'dependencies' => $this->locales->getLocaleDependencies($iso),
            ];
        }
        usort($languages, function ($a, $b) {
            // Sort languages by name
            if ($a['name'] === $b['name']) {
                return 0;
            }
            return $a['name'] < $b['name'] ? -1 : 1;
        });
        return $languages;
    }

    /**
     * Create a list of loaded extensions and their language packs details
     *
     * @return array
     */
    public function getExtensionLanguagePackDetails(): array
    {
        $activeLanguages = $this->getActiveLanguages();
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $activePackages = $packageManager->getActivePackages();
        $extensions = [];
        $activeExtensions = [];
        foreach ($activePackages as $package) {
            $path = $package->getPackagePath();
            $finder = new Finder();
            try {
                $files = $finder->files()->in($path . 'Resources/Private/Language/')->name('*.xlf');
                if ($files->count() === 0) {
                    // This extension has no .xlf files
                    continue;
                }
            } catch (\InvalidArgumentException $e) {
                // Dir does not exist
                continue;
            }
            $key = $package->getPackageKey();
            $activeExtensions[] = $key;
            $title = $package->getValueFromComposerManifest('description') ?? '';
            if (is_file($path . 'ext_emconf.php')) {
                $_EXTKEY = $key;
                $EM_CONF = [];
                include $path . 'ext_emconf.php';
                $title = $EM_CONF[$key]['title'] ?? $title;
            }
            $extension = [
                'key' => $key,
                'title' => $title,
                'icon' => PathUtility::stripPathSitePrefix(ExtensionManagementUtility::getExtensionIcon($path, true)),
            ];
            $extension['packs'] = [];
            foreach ($activeLanguages as $iso) {
                $isLanguagePackDownloaded = is_dir(Environment::getLabelsPath() . '/' . $iso . '/' . $key . '/');
                $lastUpdate = $this->registry->get('languagePacks', $iso . '-' . $key);
                $extension['packs'][] = [
                    'iso' => $iso,
                    'exists' => $isLanguagePackDownloaded,
                    'lastUpdate' => $this->getFormattedDate($lastUpdate),
                ];
            }
            $extensions[] = $extension;
        }
        usort($extensions, function ($a, $b) {
            // Sort extensions by key
            if ($a['key'] === $b['key']) {
                return 0;
            }
            return $a['key'] < $b['key'] ? -1 : 1;
        });
        return $extensions;
    }

    /**
     * Update main language pack download location if possible.
     * Store to registry to be used during language pack update
     *
     * @return string
     */
    public function updateMirrorBaseUrl(): string
    {
        $downloadBaseUrl = false;
        try {
            $xmlContent = GeneralUtility::getUrl('https://repositories.typo3.org/mirrors.xml.gz');
            $xmlContent = GeneralUtility::xml2array(@gzdecode($xmlContent));
            if (!empty($xmlContent['mirror']['host']) && !empty($xmlContent['mirror']['path'])) {
                $downloadBaseUrl = 'https://' . $xmlContent['mirror']['host'] . $xmlContent['mirror']['path'];
            }
        } catch (\Exception $e) {
            // Catch generic exception, fallback handled below
        }
        if (empty($downloadBaseUrl)) {
            // Hard coded fallback if something went wrong fetching & parsing mirror list
            $downloadBaseUrl = 'https://typo3.org/fileadmin/ter/';
        }
        $this->registry->set('languagePacks', 'baseUrl', $downloadBaseUrl);
        return $downloadBaseUrl;
    }

    /**
     * Download and unpack a single language pack of one extension.
     *
     * @param string $key Extension key
     * @param string $iso Language iso code
     * @return string One of 'update', 'new' or 'failed'
     * @throws \RuntimeException
     */
    public function languagePackDownload(string $key, string $iso): string
    {
        // Sanitize extension and iso code
        $availableLanguages = $this->getAvailableLanguages();
        $activeLanguages = $this->getActiveLanguages();
        if (!array_key_exists($iso, $availableLanguages) || !in_array($iso, $activeLanguages, true)) {
            throw new \RuntimeException('Language iso code ' . (string)$iso . ' not available or active', 1520117054);
        }
        $packageManager = GeneralUtility::makeInstance(PackageManager::class);
        $activePackages = $packageManager->getActivePackages();
        $packageActive = false;
        foreach ($activePackages as $package) {
            if ($package->getPackageKey() === $key) {
                $packageActive = true;
                break;
            }
        }
        if (!$packageActive) {
            throw new \RuntimeException('Extension ' . (string)$key . ' not loaded', 1520117245);
        }

        $languagePackBaseUrl = $this->registry->get('languagePacks', 'baseUrl');
        if (empty($languagePackBaseUrl)) {
            throw new \RuntimeException('Language pack baseUrl not found', 1520169691);
        }
        // Allow to modify the base url on the fly by calling a signal
        $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        $signalSlotDispatcher->dispatch(
            'TYPO3\\CMS\\Lang\\Service\\TranslationService',
            'postProcessMirrorUrl',
            [
                'extensionKey' => $key,
                'mirrorUrl' => &$languagePackBaseUrl,
            ]
        );

        $path = ExtensionManagementUtility::extPath($key);
        $majorVersion = explode('.', TYPO3_branch)[0];
        if (strpos($path, '/sysext/') !== false) {
            // This is a system extension and the package URL should be adapted to have different packs per core major version
            // https://typo3.org/fileadmin/ter/b/a/backend-l10n/backend-l10n-fr.v9.zip
            $packageUrl = $key[0] . '/' . $key[1] . '/' . $key . '-l10n/' . $key . '-l10n-' . $iso . '.v' . $majorVersion . '.zip';
        } else {
            // Typical non sysext path, Hungarian:
            // https://typo3.org/fileadmin/ter/a/n/anextension-l10n/anextension-l10n-hu.zip
            $packageUrl = $key[0] . '/' . $key[1] . '/' . $key . '-l10n/' . $key . '-l10n-' . $iso . '.zip';
        }

        $absoluteLanguagePath = Environment::getLabelsPath() . '/' . $iso . '/';
        $absoluteExtractionPath = $absoluteLanguagePath . $key . '/';
        $absolutePathToZipFile = Environment::getVarPath() . '/transient/' . $key . '-l10n-' . $iso . '.zip';

        $packExists = is_dir($absoluteExtractionPath);

        $packResult = $packExists ? 'update' : 'new';

        $operationResult = false;
        try {
            $languagePackContent = GeneralUtility::getUrl($languagePackBaseUrl . $packageUrl);
            if (!empty($languagePackContent)) {
                $operationResult = true;
                if ($packExists) {
                    $operationResult = GeneralUtility::rmdir($absoluteExtractionPath, true);
                }
                if ($operationResult) {
                    GeneralUtility::mkdir_deep(Environment::getVarPath() . '/transient/');
                    $operationResult = GeneralUtility::writeFileToTypo3tempDir($absolutePathToZipFile, $languagePackContent) === null;
                }
                $this->unzipTranslationFile($absolutePathToZipFile, $absoluteLanguagePath);
                if ($operationResult) {
                    $operationResult = unlink($absolutePathToZipFile);
                }
            }
        } catch (\Exception $e) {
            $operationResult = false;
        }
        if (!$operationResult) {
            $packResult = 'failed';
            $this->registry->set('languagePacks', $iso . '-' . $key, time());
        }
        return $packResult;
    }

    /**
     * Set 'last update' timestamp in registry for a series of iso codes.
     *
     * @param string[] $isos List of iso code timestamps to set
     * @throws \RuntimeException
     */
    public function setLastUpdatedIsoCode(array $isos)
    {
        $activeLanguages = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lang']['availableLanguages'] ?? [];
        $registry = GeneralUtility::makeInstance(Registry::class);
        foreach ($isos as $iso) {
            if (!in_array($iso, $activeLanguages, true)) {
                throw new \RuntimeException('Language iso code ' . (string)$iso . ' not available or active', 1520176318);
            }
            $registry->set('languagePacks', $iso, time());
        }
    }

    /**
     * Format a timestamp to a formatted date string
     *
     * @param int|null $timestamp
     * @return string|null
     */
    protected function getFormattedDate($timestamp)
    {
        if (is_int($timestamp)) {
            $date = new \DateTime('@' . $timestamp);
            $format = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
            $timestamp = $date->format($format);
        }
        return $timestamp;
    }

    /**
     * Unzip an language zip file
     *
     * @param string $file path to zip file
     * @param string $path path to extract to
     * @throws \RuntimeException
     */
    protected function unzipTranslationFile(string $file, string $path)
    {
        $zip = zip_open($file);
        if (is_resource($zip)) {
            if (!is_dir($path)) {
                GeneralUtility::mkdir_deep($path);
            }
            while (($zipEntry = zip_read($zip)) !== false) {
                $zipEntryName = zip_entry_name($zipEntry);
                if (strpos($zipEntryName, '/') !== false) {
                    $zipEntryPathSegments = explode('/', $zipEntryName);
                    $fileName = array_pop($zipEntryPathSegments);
                    // It is a folder, because the last segment is empty, let's create it
                    if (empty($fileName)) {
                        GeneralUtility::mkdir_deep($path . implode('/', $zipEntryPathSegments));
                    } else {
                        $absoluteTargetPath = GeneralUtility::getFileAbsFileName($path . implode('/', $zipEntryPathSegments) . '/' . $fileName);
                        if (trim($absoluteTargetPath) !== '') {
                            $return = GeneralUtility::writeFile(
                                $absoluteTargetPath,
                                zip_entry_read($zipEntry, zip_entry_filesize($zipEntry))
                            );
                            if ($return === false) {
                                throw new \RuntimeException('Could not write file ' . $zipEntryName, 1520170845);
                            }
                        } else {
                            throw new \RuntimeException('Could not write file ' . $zipEntryName, 1520170846);
                        }
                    }
                } else {
                    throw new \RuntimeException('Extension directory missing in zip file!', 1520170847);
                }
            }
        } else {
            throw new \RuntimeException('Unable to open zip file ' . $file, 1520170848);
        }
    }
}
