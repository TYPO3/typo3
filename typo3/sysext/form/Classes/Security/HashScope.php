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

namespace TYPO3\CMS\Form\Security;

/**
 * Contains Extbase-specific scopes to be used as additional secret for HMACs.
 * @internal
 */
enum HashScope: string
{
    case FormState = '__state';
    case FormSession = '__session';
    case ResourcePointer = 'resourcePointer';

    public function prefix(): string
    {
        return self::class . '::' . $this->value;
    }
}
