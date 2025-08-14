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

namespace TYPO3\CMS\Frontend\ContentObject;

/**
 * Contains TypoScript "Register" state together with class Register.
 *
 * An instance of this class is created during frontend rendering and registered
 * as Request attribute "frontend.register.stack".
 *
 * The TypoScript "register" is a key-value store (implemented by class Register)
 * combined with a stack. This has probably been invented for menu rendering back
 * then: content objects in general and TypoScript based menu rendering specifically
 * can be nested when multiple menu depths/layers are rendered. Each of those layers
 * may need own values within their scope.
 *
 * The two main usages are TypoScript data/getText to access register entries along
 * with content objects LOAD_REGISTER and RESTORE_REGISTER which push and pop register
 * objects from the stack and store key/value entries.
 *
 * There is one quirk in comparison to a "classic" stack: New Register instances are
 * typically clones of the underlying Register instance plus new key/values, see class
 * LoadRegisterContentObject for an implementation: Values can be set on a lower
 * level register and is still "seen/part of" a register on top. key/values bubble up,
 * but not down.
 *
 * @internal This class is not part of the TYPO3 Core API
 */
final class RegisterStack
{
    /**
     * @var Register[]
     */
    private array $registerStack = [];

    public function __construct()
    {
        $this->push(new Register());
    }

    /**
     * Peek current Register. Does not change stack.
     */
    public function current(): Register
    {
        return $this->registerStack[array_key_last($this->registerStack)];
    }

    /**
     * Add a new Register instance to top of stack
     */
    public function push(Register $register): void
    {
        $this->registerStack[] = $register;
    }

    /**
     * Remove top of stack Register and return it.
     * Re-inits with an empty register if empty to avoid exception or nullable handling
     * in consumers if there is for example a "RESTORE_REGISTER" cObj too much. As
     * drawback, consumers never know if there is a leftover pop().
     */
    public function pop(): Register
    {
        $register = array_pop($this->registerStack);
        if (empty($this->registerStack)) {
            $this->push(new Register());
        }
        return $register;
    }
}
