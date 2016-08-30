<?php
namespace TYPO3\CMS\Lang\Domain\Repository;

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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Extension repository
 */
class ExtensionRepository
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\ListUtility
     */
    protected $listUtility;

    /**
     * @var array
     */
    protected $extensions = [];

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility
     */
    public function injectListUtility(\TYPO3\CMS\Extensionmanager\Utility\ListUtility $listUtility)
    {
        $this->listUtility = $listUtility;
    }

    /**
     * Returns all objects of this repository
     *
     * @return array The extensions
     */
    public function findAll()
    {
        if (empty($this->extensions)) {
            $extensions = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
            foreach ($extensions as $entry) {
                /** @var $extension \TYPO3\CMS\Lang\Domain\Model\Extension */
                $extension = $this->objectManager->get(
                    \TYPO3\CMS\Lang\Domain\Model\Extension::class,
                    $entry['key'],
                    $entry['title'],
                    $this->getExtensionIconWithPath($entry)
                );
                $extension->setVersionFromString($entry['version']);
                if ($entry['ext_icon_width'] > 0) {
                    $extension->setIconWidth($entry['ext_icon_width']);
                }
                if ($entry['ext_icon_height'] > 0) {
                    $extension->setIconHeight($entry['ext_icon_height']);
                }

                $this->extensions[$entry['key']] = $extension;
            }
            ksort($this->extensions);
        }
        return $this->extensions;
    }

    /**
     * Counts all objects of this repository
     *
     * @return int The extension count
     */
    public function countAll()
    {
        $extensions = $this->findAll();
        return count($extensions);
    }

    /**
     * Find one extension by offset
     *
     * @param int $offset The offset
     * @return TYPO3\CMS\Lang\Domain\Model\Extension The extension
     */
    public function findOneByOffset($offset)
    {
        $extensions = $this->findAll();
        $extensions = array_values($extensions);
        $offset = (int)$offset;
        if (!empty($extensions[$offset])) {
            return $extensions[$offset];
        }
        return null;
    }

    /**
     * Returns the extension icon
     *
     * @param array $extensionEntry
     * @return string
     */
    protected function getExtensionIconWithPath($extensionEntry)
    {
        $extensionIcon = $GLOBALS['TYPO3_LOADED_EXT'][$extensionEntry['key']]['ext_icon'];
        if (empty($extensionIcon)) {
            $extensionIcon = ExtensionManagementUtility::getExtensionIcon(PATH_site . $extensionEntry['siteRelPath'] . '/');
        }
        if (empty($extensionIcon)) {
            $extensionIcon = ExtensionManagementUtility::siteRelPath('core') . 'ext_icon.png';
        } else {
            $extensionIcon = '../' . $extensionEntry['siteRelPath'] . '/' . $extensionIcon;
        }
        return $extensionIcon;
    }
}
