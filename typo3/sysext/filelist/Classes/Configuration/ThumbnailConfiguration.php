<?php
declare(strict_types=1);

namespace TYPO3\CMS\Filelist\Configuration;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class TYPO3\CMS\Filelist\Configuration\ThumbnailConfiguration
 */
class ThumbnailConfiguration implements SingletonInterface
{
    /**
     * @var int
     */
    protected $width = 64;

    /**
     * @var int
     */
    protected $height = 64;

    public function __construct()
    {
        $modTSconfig = BackendUtility::getModTSconfig(0, 'options.file_list');
        if (isset($modTSconfig['properties']['thumbnail.']['width'])
            && MathUtility::canBeInterpretedAsInteger($modTSconfig['properties']['thumbnail.']['width'])
        ) {
            $this->width = (int)$modTSconfig['properties']['thumbnail.']['width'];
        }
        if (isset($modTSconfig['properties']['thumbnail.']['height'])
            && MathUtility::canBeInterpretedAsInteger($modTSconfig['properties']['thumbnail.']['height'])
        ) {
            $this->height = (int)$modTSconfig['properties']['thumbnail.']['height'];
        }
    }

    /**
     * @return int
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight(): int
    {
        return $this->height;
    }
}
