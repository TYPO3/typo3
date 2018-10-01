<?php
namespace TYPO3\CMS\Extbase\Domain\Model;

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

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * A file reference object (File Abstraction Layer)
 *
 * @internal experimental! This class is experimental and subject to change!
 */
class FileReference extends \TYPO3\CMS\Extbase\Domain\Model\AbstractFileFolder
{
    /**
     * Uid of the referenced sys_file. Needed for extbase to serialize the
     * reference correctly.
     *
     * @var int
     */
    protected $uidLocal;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param \TYPO3\CMS\Core\Resource\ResourceInterface $originalResource
     */
    public function setOriginalResource(\TYPO3\CMS\Core\Resource\ResourceInterface $originalResource)
    {
        $this->originalResource = $originalResource;
        $this->uidLocal = (int)$originalResource->getOriginalFile()->getUid();
    }

    /**
     * @return \TYPO3\CMS\Core\Resource\FileReference
     */
    public function getOriginalResource()
    {
        if ($this->originalResource === null) {
            $uid = $this->getUid();
            if ($this->configurationManager->isFeatureEnabled('consistentTranslationOverlayHandling')) {
                $uid = $this->_localizedUid;
            }
            $this->originalResource = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFileReferenceObject($uid);
        }

        return $this->originalResource;
    }
}
