<?php
namespace TYPO3\CMS\Core\Resource\Processing;

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

/**
 * Abstract base implementation of a task.
 *
 * If you extend this class, make sure that you redefine the member variables $type and $name
 * or set them in the constructor. Otherwise your task won't be recognized by the system and several
 * things will fail.
 */
abstract class AbstractGraphicalTask extends AbstractTask
{
    /**
     * @var string
     */
    protected $targetFileExtension;

    /**
     * Returns the name the processed file should have
     * in the filesystem.
     *
     * @return string
     */
    public function getTargetFilename()
    {
        return $this->getSourceFile()->getNameWithoutExtension()
            . '_' . $this->getConfigurationChecksum()
            . '.' . $this->getTargetFileExtension();
    }

    /**
     * Determines the file extension the processed file
     * should have in the filesystem.
     *
     * @return string
     */
    public function getTargetFileExtension()
    {
        if (!isset($this->targetFileExtension)) {
            $this->targetFileExtension = $this->determineTargetFileExtension();
        }

        return $this->targetFileExtension;
    }

    /**
     * Gets the file extension the processed file should
     * have in the filesystem by either using the configuration
     * setting, or the extension of the original file.
     *
     * @return string
     */
    protected function determineTargetFileExtension()
    {
        if (!empty($this->configuration['fileExtension'])) {
            $targetFileExtension = $this->configuration['fileExtension'];
        } elseif (in_array($this->getSourceFile()->getExtension(), ['jpg', 'jpeg', 'png', 'gif'], true)) {
            $targetFileExtension = $this->getSourceFile()->getExtension();
        } elseif ($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails_png']) {
            // If true, thumbnails from non-processable files will be converted to 'png', otherwise 'gif'
            $targetFileExtension = 'png';
        } else {
            $targetFileExtension = 'gif';
        }
        return $targetFileExtension;
    }
}
