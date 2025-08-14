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
 * Implement cObj "LOAD_REGISTER":
 * Get latest Register, clone it, set a key/value, push as new latest RegisterStack entry.
 *
 * Note the naming "LOAD_REGISTER" is kinda misleading since it rather "loads into" or
 * sets a new value and pushes as key/value to the stack. "RESTORE_REGISTER" is the
 * counterpart cObj to get rid of that state again.
 */
class LoadRegisterContentObject extends AbstractContentObject
{
    /**
     * Does not return any content, it just sets internal data based on the TypoScript properties.
     *
     * @param array $conf Array of TypoScript properties
     * @return string Empty string
     */
    public function render($conf = []): string
    {
        $registerStack = $this->request->getAttribute('frontend.register.stack');
        $clonedRegister = clone $registerStack->current();
        if (is_array($conf)) {
            $isExecuted = [];
            foreach ($conf as $key => $value) {
                $key = rtrim($key, '.');
                if (!isset($isExecuted[$key]) || !$isExecuted[$key]) {
                    $registerProperties = $key . '.';
                    if (isset($conf[$key]) && isset($conf[$registerProperties])) {
                        $value = $this->cObj->stdWrap($conf[$key], $conf[$registerProperties]);
                    } elseif (isset($conf[$registerProperties])) {
                        $value = $this->cObj->stdWrap('', $conf[$registerProperties]);
                    }
                    $clonedRegister->set($key, $value);
                    $isExecuted[$key] = true;
                }
            }
        }
        $registerStack->push($clonedRegister);
        return '';
    }
}
