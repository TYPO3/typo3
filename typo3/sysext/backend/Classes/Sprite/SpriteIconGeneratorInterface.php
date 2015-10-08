<?php
namespace TYPO3\CMS\Backend\Sprite;

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
 * Interface all handlers in SpriteManager have to implement.
 */
interface SpriteIconGeneratorInterface
{
    /**
     * the implementation of this function has to do the main task
     * this function will be called if the extension list changed or
     * registered icons in TBE_STYLES[spritemanager] changed
     *
     * @return void
     */
    public function generate();

    /**
     * The sprite manager will call this function after the call to "generate"
     * it should return an array of all sprite-icon-names generated through the run
     *
     * @return array All generated/detected sprite-icon-names
     */
    public function getAvailableIconNames();
}
