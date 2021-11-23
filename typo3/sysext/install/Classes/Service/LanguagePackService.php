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

namespace TYPO3\CMS\Install\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Service\Archive\ZipService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Install\Service\Event\ModifyLanguagePackRemoteBaseUrlEvent;

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

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    private const LANGUAGE_PACK_URL = 'https://localize.typo3.org/xliff/';

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        RequestFactory $requestFactory,
        LoggerInterface $logger
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->locales = GeneralUtility::makeInstance(Locales::class);
        $this->registry = GeneralUtility::makeInstance(Registry::class);
        $this->requestFactory = $requestFactory;
        $this->logger = $logger;
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
        $availableLanguages = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lang']['availableLanguages'] ?? [];
        return array_filter($availableLanguages);
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
        usort($languages, static function ($a, $b) {
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
            $title = $package->getValueFromComposerManifest('description') ?? '';
            if (is_file($path . 'ext_emconf.php')) {
                $_EXTKEY = $key;
                $EM_CONF = [];
                include $path . 'ext_emconf.php';
                $title = $EM_CONF[$key]['title'] ?? $title;

                $state = $EM_CONF[$key]['state'] ?? '';
                if ($state === 'excludeFromUpdates') {
                    continue;
                }
            }
            $extension = [
                'key' => $key,
                'title' => $title,
            ];
            if (!empty(ExtensionManagementUtility::getExtensionIcon($path, false))) {
                $extension['icon'] = PathUtility::getAbsoluteWebPath(ExtensionManagementUtility::getExtensionIcon($path, true));
            }
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
        usort($extensions, static function ($a, $b) {
            // Sort extensions by key
            if ($a['key'] === $b['key']) {
                return 0;
            }
            return $a['key'] < $b['key'] ? -1 : 1;
        });
        return $extensions;
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
        $package = $packageManager->getActivePackages()[$key] ?? null;
        if (!$package) {
            throw new \RuntimeException('Extension ' . (string)$key . ' not loaded', 1520117245);
        }

        $languagePackBaseUrl = self::LANGUAGE_PACK_URL;

        // Allow to modify the base url on the fly
        $event = $this->eventDispatcher->dispatch(new ModifyLanguagePackRemoteBaseUrlEvent(new Uri($languagePackBaseUrl), $key));
        $languagePackBaseUrl = $event->getBaseUrl();
        $majorVersion = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
        if ($package->getPackageMetaData()->isFrameworkType()) {
            // This is a system extension and the package URL should be adapted to have different packs per core major version
            // https://localize.typo3.org/xliff/b/a/backend-l10n/backend-l10n-fr.v9.zip
            $packageUrl = $key[0] . '/' . $key[1] . '/' . $key . '-l10n/' . $key . '-l10n-' . $iso . '.v' . $majorVersion . '.zip';
        } else {
            // Typical non sysext path, Hungarian:
            // https://localize.typo3.org/xliff/a/n/anextension-l10n/anextension-l10n-hu.zip
            $packageUrl = $key[0] . '/' . $key[1] . '/' . $key . '-l10n/' . $key . '-l10n-' . $iso . '.zip';
        }

        $absoluteLanguagePath = Environment::getLabelsPath() . '/' . $iso . '/';
        $absoluteExtractionPath = $absoluteLanguagePath . $key . '/';
        $absolutePathToZipFile = Environment::getVarPath() . '/transient/' . $key . '-l10n-' . $iso . '.zip';

        $packExists = is_dir($absoluteExtractionPath);

        $packResult = $packExists ? 'update' : 'new';

        $operationResult = false;

        $response = $this->requestFactory->request($languagePackBaseUrl . $packageUrl, 'GET', ['http_errors' => false]);
        if ($response->getStatusCode() === 200) {
            $languagePackContent = $response->getBody()->getContents();
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
        } else {
            $operationResult = false;

            $this->logger->warning('Requesting {request} was not successful, got status code {status} ({reason})', [
                'request' => $languagePackBaseUrl . $packageUrl,
                'status' => $response->getStatusCode(),
                'reason' => $response->getReasonPhrase(),
            ]);
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
        foreach ($isos as $iso) {
            if (!in_array($iso, $activeLanguages, true)) {
                throw new \RuntimeException('Language iso code ' . (string)$iso . ' not available or active', 1520176318);
            }
            $this->registry->set('languagePacks', $iso, time());
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
     * Unzip a language zip file
     *
     * @param string $file path to zip file
     * @param string $path path to extract to
     */
    protected function unzipTranslationFile(string $file, string $path)
    {
        if (!is_dir($path)) {
            GeneralUtility::mkdir_deep($path);
        }

        $zipService = GeneralUtility::makeInstance(ZipService::class);
        if ($zipService->verify($file)) {
            $zipService->extract($file, $path);
        }
        GeneralUtility::fixPermissions($path, true);
    }
}
