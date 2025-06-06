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

namespace TYPO3\CMS\Dashboard\Dto;

/**
 * @internal
 */
final readonly class WidgetData implements \JsonSerializable
{
    public function __construct(
        private string $identifier,
        private string $type,
        private string $height,
        private string $width,
        private string $label,
        private string $content,
        private array $eventdata,
        private bool $refreshable,
        private bool $configurable,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'identifier' => $this->identifier,
            'type' => $this->type,
            'height' => $this->height,
            'width' => $this->width,
            'label' => $this->label,
            'content' => $this->content,
            'eventdata' => $this->eventdata,
            'refreshable' => $this->refreshable,
            'configurable' => $this->configurable,
        ];
    }
}
