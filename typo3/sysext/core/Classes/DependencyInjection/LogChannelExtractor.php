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

namespace TYPO3\CMS\Core\DependencyInjection;

use TYPO3\CMS\Core\Log\Channel;

/**
 * @internal
 */
final readonly class LogChannelExtractor
{
    public function getClassChannelName(\ReflectionClass $class): ?string
    {
        $attributes = $class->getAttributes(Channel::class, \ReflectionAttribute::IS_INSTANCEOF);
        if ($attributes !== []) {
            return $attributes[0]->newInstance()->name;
        }
        if ($class->getParentClass() !== false) {
            return $this->getClassChannelName($class->getParentClass());
        }
        return null;
    }

    public function getParameterChannelName(\ReflectionParameter $parameter): ?string
    {
        $attributes = $parameter->getAttributes(Channel::class, \ReflectionAttribute::IS_INSTANCEOF);
        if ($attributes !== []) {
            return $attributes[0]->newInstance()->name;
        }
        return null;
    }
}
