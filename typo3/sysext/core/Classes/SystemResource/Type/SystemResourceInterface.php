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

namespace TYPO3\CMS\Core\SystemResource\Type;

use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceDoesNotExistException;

/**
 * This interface is public API and can be referenced in third party code
 * or throughout the TYPO3 core.
 * Implementations of this interface are internal though
 * and must only happen in TYPO3\CMS\Core\SystemResource namespace
 */
interface SystemResourceInterface extends StaticResourceInterface
{
    /**
     * @throws SystemResourceDoesNotExistException
     */
    public function getContents(): string;
    public function getName(): string;
    public function getNameWithoutExtension(): string;
    public function getExtension(): string;
    public function getMimeType(): string;
    public function getHash(): string;
}
