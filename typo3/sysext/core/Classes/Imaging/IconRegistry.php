<?php

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

namespace TYPO3\CMS\Core\Imaging;

use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgSpriteIconProvider;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class IconRegistry, which makes it possible to register custom icons
 * from within an extension.
 */
class IconRegistry implements SingletonInterface
{
    /**
     * @var bool
     */
    protected $fullInitialized = false;

    /**
     * @var bool
     */
    protected $tcaInitialized = false;

    /**
     * @var bool
     */
    protected $flagsInitialized = false;

    /**
     * @var bool
     */
    protected $moduleIconsInitialized = false;

    /**
     * @var bool
     */
    protected $backendIconsInitialized = false;

    /**
     * Registered icons
     *
     * @var array
     */
    protected $icons = [];

    /**
     * @var string
     */
    protected $backendIconDeclaration = 'EXT:core/Resources/Public/Icons/T3Icons/icons.json';

    /**
     * List of allowed icon file extensions with their Provider class
     *
     * @var string[]
     */
    protected $backendIconAllowedExtensionsWithProvider = [
        'png' => BitmapIconProvider::class,
        'svg' => SvgIconProvider::class,
    ];

    /**
     * manually registered icons
     * hopefully obsolete one day
     *
     * @var array
     */
    protected $staticIcons = [

        /**
         * Important Information:
         *
         * Icons are maintained in an external repository, if new icons are needed
         * please request them at: https://github.com/typo3/typo3.icons/issues
         */
    ];

    /**
     * Mapping of file extensions to mimetypes
     *
     * @var string[]
     */
    protected $fileExtensionMapping = [
        'htm' => 'mimetypes-text-html',
        'html' => 'mimetypes-text-html',
        'css' => 'mimetypes-text-css',
        'js' => 'mimetypes-text-js',
        'csv' => 'mimetypes-text-csv',
        'php' => 'mimetypes-text-php',
        'php6' => 'mimetypes-text-php',
        'php5' => 'mimetypes-text-php',
        'php4' => 'mimetypes-text-php',
        'php3' => 'mimetypes-text-php',
        'inc' => 'mimetypes-text-php',
        'ts' => 'mimetypes-text-ts',
        'typoscript' => 'mimetypes-text-typoscript',
        'txt' => 'mimetypes-text-text',
        'class' => 'mimetypes-text-text',
        'tmpl' => 'mimetypes-text-text',
        'jpg' => 'mimetypes-media-image',
        'jpeg' => 'mimetypes-media-image',
        'gif' => 'mimetypes-media-image',
        'png' => 'mimetypes-media-image',
        'bmp' => 'mimetypes-media-image',
        'tif' => 'mimetypes-media-image',
        'tiff' => 'mimetypes-media-image',
        'tga' => 'mimetypes-media-image',
        'psd' => 'mimetypes-media-image',
        'eps' => 'mimetypes-media-image',
        'ai' => 'mimetypes-media-image',
        'svg' => 'mimetypes-media-image',
        'pcx' => 'mimetypes-media-image',
        'avi' => 'mimetypes-media-video',
        'mpg' => 'mimetypes-media-video',
        'mpeg' => 'mimetypes-media-video',
        'mov' => 'mimetypes-media-video',
        'vimeo' => 'mimetypes-media-video-vimeo',
        'youtube' => 'mimetypes-media-video-youtube',
        'wav' => 'mimetypes-media-audio',
        'mp3' => 'mimetypes-media-audio',
        'ogg' => 'mimetypes-media-audio',
        'flac' => 'mimetypes-media-audio',
        'opus' => 'mimetypes-media-audio',
        'mid' => 'mimetypes-media-audio',
        'swf' => 'mimetypes-media-flash',
        'swa' => 'mimetypes-media-flash',
        'exe' => 'mimetypes-application',
        'com' => 'mimetypes-application',
        't3x' => 'mimetypes-compressed',
        't3d' => 'mimetypes-compressed',
        'zip' => 'mimetypes-compressed',
        'tgz' => 'mimetypes-compressed',
        'gz' => 'mimetypes-compressed',
        'pdf' => 'mimetypes-pdf',
        'doc' => 'mimetypes-word',
        'dot' => 'mimetypes-word',
        'docm' => 'mimetypes-word',
        'docx' => 'mimetypes-word',
        'dotm' => 'mimetypes-word',
        'dotx' => 'mimetypes-word',
        'sxw' => 'mimetypes-word',
        'rtf' => 'mimetypes-word',
        'xls' => 'mimetypes-excel',
        'xlsm' => 'mimetypes-excel',
        'xlsx' => 'mimetypes-excel',
        'xltm' => 'mimetypes-excel',
        'xltx' => 'mimetypes-excel',
        'sxc' => 'mimetypes-excel',
        'pps' => 'mimetypes-powerpoint',
        'ppsx' => 'mimetypes-powerpoint',
        'ppt' => 'mimetypes-powerpoint',
        'pptm' => 'mimetypes-powerpoint',
        'pptx' => 'mimetypes-powerpoint',
        'potm' => 'mimetypes-powerpoint',
        'potx' => 'mimetypes-powerpoint',
        'mount' => 'apps-filetree-mount',
        'folder' => 'apps-filetree-folder-default',
        'default' => 'mimetypes-other-other',
    ];

    /**
     * Mapping of mime types to icons
     *
     * @var string[]
     */
    protected $mimeTypeMapping = [
        'video/*' => 'mimetypes-media-video',
        'audio/*' => 'mimetypes-media-audio',
        'image/*' => 'mimetypes-media-image',
        'text/*' => 'mimetypes-text-text',
    ];

    /**
     * @var array<string, string>
     */
    protected $iconAliases = [];

    /**
     * Array of deprecated icons, add deprecated icons to this array and remove it from registry
     * - Index of this array contains the deprecated icon
     * - Value of each entry may contain a possible new identifier
     *
     * Example:
     * [
     *   'deprecated-icon-identifier' => 'new-icon-identifier',
     *   'another-deprecated-identifier' => null,
     * ]
     *
     * @var array
     * @deprecated These icons will be removed in TYPO3 v12
     */
    protected $deprecatedIcons = [];

    /**
     * @var string
     */
    protected $defaultIconIdentifier = 'default-not-found';

    /**
     * @var FrontendInterface
     */
    protected $cache;

    private string $cacheIdentifier;

    public function __construct(FrontendInterface $assetsCache, string $cacheIdentifier)
    {
        $this->cache = $assetsCache;
        $this->cacheIdentifier = $cacheIdentifier;
        $this->initialize();
    }

    /**
     * Initialize the registry
     * This method can be called multiple times, depending on initialization status.
     * In some cases e.g. TCA is not available, the method must be called multiple times.
     */
    protected function initialize()
    {
        if (!$this->backendIconsInitialized) {
            $this->getCachedBackendIcons();
        }
        if (!$this->tcaInitialized && !empty($GLOBALS['TCA'])) {
            $this->registerTCAIcons();
        }
        if (!$this->moduleIconsInitialized && !empty($GLOBALS['TBE_MODULES'])) {
            $this->registerModuleIcons();
        }
        if (!$this->flagsInitialized) {
            $this->registerFlags();
        }
        if ($this->backendIconsInitialized
            && $this->tcaInitialized
            && $this->moduleIconsInitialized
            && $this->flagsInitialized) {
            $this->fullInitialized = true;
        }
    }

    protected function getBackendIconsCacheIdentifier(): string
    {
        return $this->cacheIdentifier;
    }

    /**
     * Retrieve the icons from cache render them when not cached yet
     */
    protected function getCachedBackendIcons()
    {
        $cacheIdentifier = $this->getBackendIconsCacheIdentifier();
        $cacheEntry = $this->cache->get($cacheIdentifier);

        if ($cacheEntry !== false) {
            $this->icons = $cacheEntry;
        } else {
            $this->registerBackendIcons();
            // all found icons should now be present, for historic reasons now merge w/ the statically declared icons
            $this->icons = array_merge($this->icons, $this->iconAliases, $this->staticIcons);
            $this->cache->set($cacheIdentifier, $this->icons);
        }
        // if there's now at least one icon registered, consider it successful
        if (is_array($this->icons) && (count($this->icons) >= count($this->staticIcons))) {
            $this->backendIconsInitialized = true;
        }
    }

    /**
     * Automatically find and register the core backend icons
     */
    protected function registerBackendIcons(): void
    {
        $dir = dirname($this->backendIconDeclaration);
        $absoluteIconDeclarationPath = GeneralUtility::getFileAbsFileName($this->backendIconDeclaration);
        $json = json_decode(file_get_contents($absoluteIconDeclarationPath) ?: '', true);
        foreach ($json['icons'] ?? [] as $declaration) {
            $iconOptions = [
                'sprite' => $dir . '/' . $declaration['sprite'],
                'source' => $dir . '/' . $declaration['svg'],
            ];
            // kind of hotfix for now, needs a nicer concept later
            if ($declaration['category'] === 'spinner') {
                $iconOptions['spinning'] = true;
            }

            $this->registerIcon(
                $declaration['identifier'],
                SvgSpriteIconProvider::class,
                $iconOptions
            );
        }

        foreach ($json['aliases'] as $alias => $identifier) {
            $this->registerAlias($alias, $identifier);
        }
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function isRegistered($identifier)
    {
        if (!$this->fullInitialized) {
            $this->initialize();
        }
        return isset($this->icons[$identifier]);
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function isDeprecated($identifier)
    {
        return isset($this->deprecatedIcons[$identifier]);
    }

    /**
     * @return string
     */
    public function getDefaultIconIdentifier()
    {
        return $this->defaultIconIdentifier;
    }

    /**
     * Registers an icon to be available inside the Icon Factory
     *
     * @param string $identifier
     * @param string $iconProviderClassName
     * @param array $options
     *
     * @throws \InvalidArgumentException
     */
    public function registerIcon($identifier, $iconProviderClassName, array $options = [])
    {
        if (!in_array(IconProviderInterface::class, class_implements($iconProviderClassName) ?: [], true)) {
            throw new \InvalidArgumentException('An IconProvider must implement '
                . IconProviderInterface::class, 1437425803);
        }
        $this->icons[$identifier] = [
            'provider' => $iconProviderClassName,
            'options' => $options,
        ];
    }

    /**
     * Registers an icon to be available inside the Icon Factory
     *
     * @param string $alias
     * @param string $identifier
     *
     * @throws \InvalidArgumentException
     */
    public function registerAlias($alias, $identifier)
    {
        if (!isset($this->icons[$identifier])) {
            throw new \InvalidArgumentException('No icon with identifier "' . $identifier . '" registered.', 1602251838);
        }
        $this->iconAliases[$alias] = $this->icons[$identifier];
    }

    /**
     * Register an icon for a file extension
     *
     * @param string $fileExtension
     * @param string $iconIdentifier
     */
    public function registerFileExtension($fileExtension, $iconIdentifier)
    {
        $this->fileExtensionMapping[$fileExtension] = $iconIdentifier;
    }

    /**
     * Register an icon for a mime-type
     *
     * @param string $mimeType
     * @param string $iconIdentifier
     */
    public function registerMimeTypeIcon($mimeType, $iconIdentifier)
    {
        $this->mimeTypeMapping[$mimeType] = $iconIdentifier;
    }

    /**
     * Fetches the configuration provided by registerIcon()
     *
     * @param string $identifier the icon identifier
     * @return mixed
     * @throws Exception
     */
    public function getIconConfigurationByIdentifier($identifier)
    {
        if (!$this->fullInitialized) {
            $this->initialize();
        }
        if ($this->isDeprecated($identifier)) {
            $replacement = $this->deprecatedIcons[$identifier] ?? null;
            if (!empty($replacement)) {
                $message = 'The icon "%s" is deprecated since TYPO3 v9 and will be removed in TYPO3 v10.0. Please use "%s" instead.';
                $arguments = [$identifier, $replacement];
                $identifier = $replacement;
            } else {
                $message = 'The icon "%s" is deprecated since TYPO3 v9 and will be removed in TYPO3 v10.0.';
                $arguments = [$identifier];
            }
            trigger_error(vsprintf($message, $arguments), E_USER_DEPRECATED);
        }
        if (!$this->isRegistered($identifier)) {
            throw new Exception('Icon with identifier "' . $identifier . '" is not registered"', 1437425804);
        }
        return $this->icons[$identifier];
    }

    /**
     * @return array
     */
    public function getAllRegisteredIconIdentifiers()
    {
        if (!$this->fullInitialized) {
            $this->initialize();
        }
        return array_keys($this->icons);
    }

    /**
     * @return array
     */
    public function getDeprecatedIcons(): array
    {
        return $this->deprecatedIcons;
    }

    /**
     * @param string $fileExtension
     * @return string
     */
    public function getIconIdentifierForFileExtension($fileExtension)
    {
        // If the file extension is not valid use the default one
        if (!isset($this->fileExtensionMapping[$fileExtension])) {
            $fileExtension = 'default';
        }
        return $this->fileExtensionMapping[$fileExtension];
    }

    /**
     * Get iconIdentifier for given mimeType
     *
     * @param string $mimeType
     * @return string|null Returns null if no icon is registered for the mimeType
     */
    public function getIconIdentifierForMimeType($mimeType)
    {
        if (!isset($this->mimeTypeMapping[$mimeType])) {
            return null;
        }
        return $this->mimeTypeMapping[$mimeType];
    }

    /**
     * Calculates the cache identifier based on the current registry
     *
     * @return string
     * @internal
     */
    public function getCacheIdentifier(): string
    {
        if (!$this->fullInitialized) {
            $this->initialize();
        }

        return sha1((string)json_encode($this->icons));
    }

    /**
     * Load icons from TCA for each table and add them as "tcarecords-XX" to $this->icons
     */
    protected function registerTCAIcons()
    {
        $resultArray = [];

        $tcaTables = array_keys($GLOBALS['TCA'] ?? []);
        // check every table in the TCA, if an icon is needed
        foreach ($tcaTables as $tableName) {
            // This method is only needed for TCA tables where typeicon_classes are not configured
            $iconIdentifier = 'tcarecords-' . $tableName . '-default';
            if (
                isset($this->icons[$iconIdentifier])
                || !isset($GLOBALS['TCA'][$tableName]['ctrl']['iconfile'])
            ) {
                continue;
            }
            $resultArray[$iconIdentifier] = $GLOBALS['TCA'][$tableName]['ctrl']['iconfile'];
        }

        foreach ($resultArray as $iconIdentifier => $iconFilePath) {
            $iconProviderClass = $this->detectIconProvider($iconFilePath);
            $this->icons[$iconIdentifier] = [
                'provider' => $iconProviderClass,
                'options' => [
                    'source' => $iconFilePath,
                ],
            ];
        }
        $this->tcaInitialized = true;
    }

    /**
     * Register module icons
     */
    protected function registerModuleIcons()
    {
        $moduleConfiguration = $GLOBALS['TBE_MODULES']['_configuration'] ?? [];
        foreach ($moduleConfiguration as $moduleKey => $singleModuleConfiguration) {
            $iconIdentifier = !empty($singleModuleConfiguration['iconIdentifier'])
                ? $singleModuleConfiguration['iconIdentifier']
                : null;

            if ($iconIdentifier !== null) {
                // iconIdentifier found, icon is registered, continue
                continue;
            }

            $iconPath = !empty($singleModuleConfiguration['icon'])
                ? $singleModuleConfiguration['icon']
                : null;
            $iconProviderClass = $this->detectIconProvider($iconPath);
            $iconIdentifier = 'module-icon-' . $moduleKey;

            $this->icons[$iconIdentifier] = [
                'provider' => $iconProviderClass,
                'options' => [
                    'source' => $iconPath,
                ],
            ];
        }
        $this->moduleIconsInitialized = true;
    }

    /**
     * Register flags
     */
    protected function registerFlags()
    {
        $iconFolder = 'EXT:core/Resources/Public/Icons/Flags/';
        $files = [
            'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AN', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
            'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY', 'BZ',
            'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN', 'CO', 'CR', 'CS', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ',
            'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ',
            'EC', 'EE', 'EG', 'EH', 'ER', 'ES', 'ET', 'EU',
            'FI', 'FJ', 'FK', 'FM', 'FO', 'FR',
            'GA', 'GB-ENG', 'GB-NIR', 'GB-SCT', 'GB-WLS', 'GB', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY',
            'HK', 'HM', 'HN', 'HR', 'HT', 'HU',
            'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT',
            'JE', 'JM', 'JO', 'JP',
            'KE', 'KG', 'KH', 'KI', 'KL', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ',
            'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY',
            'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MI', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ',
            'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ',
            'OM',
            'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY',
            'QA', 'QC',
            'RE', 'RO', 'RS', 'RU', 'RW',
            'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ',
            'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ',
            'UA', 'UG', 'UM', 'US', 'UY', 'UZ',
            'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU',
            'WF', 'WS',
            'YE', 'YT',
            'ZA', 'ZM', 'ZW',
            // Special Flags
            'catalonia',
            'multiple',
            'en-us-gb',
        ];
        foreach ($files as $file) {
            $identifier = strtolower($file);
            $this->icons['flags-' . $identifier] = [
                'provider' => BitmapIconProvider::class,
                'options' => [
                    'source' => $iconFolder . $file . '.png',
                ],
            ];
        }
        $this->flagsInitialized = true;
    }

    /**
     * Detect the IconProvider of an icon
     *
     * @param string $iconReference
     * @return string
     */
    public function detectIconProvider($iconReference)
    {
        if (str_ends_with(strtolower($iconReference), 'svg')) {
            return SvgIconProvider::class;
        }
        return BitmapIconProvider::class;
    }

    public function warmupCaches(CacheWarmupEvent $event): void
    {
        if ($event->hasGroup('system')) {
            $backupIcons = $this->icons;
            $backupAliases = $this->iconAliases;
            $this->icons = [];
            $this->iconAliases = [];

            $this->registerBackendIcons();
            // all found icons should now be present, for historic reasons now merge w/ the statically declared icons
            $this->icons = array_merge($this->icons, $this->iconAliases, $this->staticIcons);
            $this->cache->set($this->getBackendIconsCacheIdentifier(), $this->icons);

            $this->icons = $backupIcons;
            $this->iconAliases = $backupAliases;
        }
    }
}
