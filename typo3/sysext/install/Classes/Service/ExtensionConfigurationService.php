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

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\TypoScript\Parser\ConstantConfigurationParser;

/**
 * Service to prepare extension configuration settings from ext_conf_template.txt
 * to be viewed in the install tool. The class basically adds display related
 * stuff on top of ext:core ExtensionConfiguration.
 *
 * Extension authors should use TYPO3\CMS\Core\Configuration\ExtensionConfiguration
 * class to get() extension configuration settings.
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class ExtensionConfigurationService
{
    /**
     * @var PackageManager
     */
    private $packageManager;

    /**
     * @var ConstantConfigurationParser
     */
    private $configurationParser;

    public function __construct(
        PackageManager $packageManager,
        ConstantConfigurationParser $configurationParser
    ) {
        $this->packageManager = $packageManager;
        $this->configurationParser = $configurationParser;
    }
    /**
     * Compiles ext_conf_template file and merges it with values from LocalConfiguration['EXTENSIONS'].
     * Returns a funny array used to display the configuration form in the install tool.
     *
     * @param string $extensionKey Extension key
     * @return array
     */
    public function getConfigurationPreparedForView(string $extensionKey): array
    {
        $package = $this->packageManager->getPackage($extensionKey);
        if (!@is_file($package->getPackagePath() . 'ext_conf_template.txt')) {
            return [];
        }
        $extensionConfiguration = new ExtensionConfiguration();
        $rawConfiguration = $extensionConfiguration->getDefaultConfigurationRawString($extensionKey);
        $configuration = $this->configurationParser->getConfigurationAsValuedArray($rawConfiguration);
        foreach ($configuration as $configurationPath => &$details) {
            try {
                $configuration[$configurationPath]['extensionKey'] = $extensionKey;
                $valueFromLocalConfiguration = $extensionConfiguration->get($extensionKey, str_replace('.', '/', $configurationPath));
                $details['value'] = $valueFromLocalConfiguration;
            } catch (ExtensionConfigurationPathDoesNotExistException $e) {
                // Deliberately empty - it can happen at runtime that a written config does not return
                // back all values (eg. saltedpassword with its userFuncs), which then miss in the written
                // configuration and are only synced after next install tool run. This edge case is
                // taken care of here.
            }
        }
        return $this->configurationParser->prepareConfigurationForView($configuration);
    }
}
