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

namespace TYPO3\CMS\Lowlevel\Tests\Unit\Inspector\Fixtures;

final class ChildWithPrivateProperty extends ParentWithPrivateProperty
{
    public const string PARENT_CLASS = ParentWithPrivateProperty::class;

    private string $private = 'child';

    public function childPrivate(): string
    {
        return $this->private;
    }
}
