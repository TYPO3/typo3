<?php

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

namespace TYPO3\CMS\Frontend\ContentObject;

/**
 * Implement cObj "RESTORE_REGISTER":
 * As the counterpart of "LOAD_REGISTER", "RESTORE_REGISTER" removes any state
 * added by latest "LOAD_REGISTER" again.
 */
class RestoreRegisterContentObject extends AbstractContentObject
{
    /**
     * Does not return any content, it just sets internal data based on the TypoScript properties.
     *
     * @param array $conf Array of TypoScript properties
     * @return string Empty string
     */
    public function render($conf = []): string
    {
        $this->request->getAttribute('frontend.register.stack')->pop();
        return '';
    }
}
