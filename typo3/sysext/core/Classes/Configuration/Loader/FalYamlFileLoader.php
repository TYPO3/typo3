<?php
namespace TYPO3\CMS\Core\Configuration\Loader;

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

use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader\Configuration;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * YAML loader for FAL files
 */
class FalYamlFileLoader extends YamlFileLoader
{
    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @param Configuration|null $configuration
     */
    public function __construct(Configuration $configuration = null, ResourceFactory $resourceFactory = null)
    {
        parent::__construct($configuration);
        $this->resourceFactory = $resourceFactory ?: GeneralUtility::makeInstance(ResourceFactory::class);
    }

    /**
     * Loads and parses a YAML file, returns an array with the data found
     *
     * @param File|string $fileName either relative to PATH_site or prefixed with EXT:... or File object
     * @return array the configuration as array
     */
    public function load($fileName): array
    {
        return $this->loadFromContent($this->getFileContents($fileName));
    }

    /**
     * @param File|string $fileName either relative to PATH_site or prefixed with EXT:... or File object
     * @return string the contents of the file
     * @throws \RuntimeException when the file was not accessible
     */
    protected function getFileContents($fileName): string
    {
        $file = null;

        if (is_string($fileName)) {
            $file = $this->resourceFactory->retrieveFileOrFolderObject($fileName);
        } elseif (is_object($fileName)) {
            $file = $fileName;
        }

        if ($file instanceof File) {
            $content = $file->getContents();

            if (!$content) {
                throw new \RuntimeException('YAML file "' . $file->getIdentifier() . '" could not be loaded', 1512561127);
            }

            return $content;
        }

        return parent::getFileContents($fileName);
    }
}
