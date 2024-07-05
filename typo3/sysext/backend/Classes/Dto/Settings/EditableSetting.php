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

namespace TYPO3\CMS\Backend\Dto\Settings;

use TYPO3\CMS\Core\Settings\SettingDefinition;

/**
 * @internal
 */
final readonly class EditableSetting implements \JsonSerializable
{
    public function __construct(
        public SettingDefinition $definition,
        public string|int|float|bool|array|null $value,
        public string|int|float|bool|array|null $systemDefault,
        public string $typeImplementation,
    ) {}

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
