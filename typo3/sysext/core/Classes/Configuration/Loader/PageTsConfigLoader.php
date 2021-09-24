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

namespace TYPO3\CMS\Core\Configuration\Loader;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\Event\ModifyLoadedPageTsConfigEvent;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Traverses a root line of a pagetree up and includes all available TSconfig settings, including default
 * setup. Then include lines are checked, and merged together into one string, ready to be parsed.
 *
 * Can be used in Frontend or Backend.
 *
 * Have a look at the PageTsConfigParser which can then parse (and cache) this information based on the.
 *
 * Currently, this accumulated information of the pages is NOT cached, as it would need to be tagged with any
 * page, also including external files.
 */
class PageTsConfigLoader
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Main method to get all PageTSconfig from the rootline including the defaultTSconfig settings.
     * @param array $rootLine
     * @return string
     */
    public function load(array $rootLine): string
    {
        // Verifying includes, and melt the inclusions together into one string
        $tsData = $this->collect($rootLine);
        return implode("\n[GLOBAL]\n", $tsData);
    }

    /**
     * Same as "load()" but returns an array of all parts. Only useful in the TYPO3 Backend for inspection purposes.
     *
     * @param array $rootLine
     * @return array
     * @internal
     */
    public function collect(array $rootLine): array
    {
        $tsData = [
            'default' => $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'] ?? '',
        ];
        foreach ($rootLine as $page) {
            // Can happen when the rootline is given from BE context, we skip this
            if ((int)$page['uid'] === 0) {
                continue;
            }
            if (trim($page['tsconfig_includes'] ?? '')) {
                $includeTsConfigFileList = GeneralUtility::trimExplode(',', $page['tsconfig_includes'], true);
                // Traversing list
                foreach ($includeTsConfigFileList as $key => $includeTsConfigFile) {
                    if (PathUtility::isExtensionPath($includeTsConfigFile)) {
                        [$includeTsConfigFileExtensionKey, $includeTsConfigFilename] = explode(
                            '/',
                            substr($includeTsConfigFile, 4),
                            2
                        );
                        if ((string)$includeTsConfigFileExtensionKey !== ''
                            && ExtensionManagementUtility::isLoaded($includeTsConfigFileExtensionKey)
                            && (string)$includeTsConfigFilename !== ''
                        ) {
                            $extensionPath = ExtensionManagementUtility::extPath($includeTsConfigFileExtensionKey);
                            $includeTsConfigFileAndPath = PathUtility::getCanonicalPath($extensionPath . $includeTsConfigFilename);
                            if (strpos($includeTsConfigFileAndPath, $extensionPath) === 0 && file_exists($includeTsConfigFileAndPath)) {
                                $tsData['page_' . $page['uid'] . '_includes_' . $key] = (string)file_get_contents($includeTsConfigFileAndPath);
                            }
                        }
                    }
                }
            }
            $tsData['page_' . $page['uid']] = $page['TSconfig'] ?? '';
        }

        $event = $this->eventDispatcher->dispatch(new ModifyLoadedPageTsConfigEvent($tsData, $rootLine));

        // Apply includes
        return TypoScriptParser::checkIncludeLines_array($event->getTsConfig());
    }
}
