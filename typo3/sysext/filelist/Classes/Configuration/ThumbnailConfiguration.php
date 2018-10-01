<?php
declare(strict_types = 1);

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:filelist and not part of TYPO3's Core API.
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
        $userTsConfig = $this->getBackendUser()->getTSConfig();
        $this->width = (int)($userTsConfig['options.']['file_list.']['thumbnail.']['width'] ?? 64);
        $this->height = (int)($userTsConfig['options.']['file_list.']['thumbnail.']['height'] ?? 64);
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

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
