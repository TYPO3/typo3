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

namespace TYPO3\CMS\Core\Messaging\Renderer;

use TYPO3\CMS\Core\Messaging\FlashMessage;

/**
 * Interface must be implemented by all flash message renderer classes
 */
interface FlashMessageRendererInterface
{
    /**
     * Render method
     *
     * @param FlashMessage[] $flashMessages
     * @return string Representation of the flash message
     */
    public function render(array $flashMessages): string;
}
