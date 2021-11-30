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

namespace TYPO3\CMS\Backend\Form\Behavior;

/**
 * Updates bitmask values for multi-checkboxes.
 */
class UpdateBitmaskOnFieldChange implements OnFieldChangeInterface
{
    protected int $position;
    protected int $total;
    protected bool $invert;
    protected string $elementName;

    public function __construct(int $position, int $total, bool $invert, string $elementName)
    {
        $this->position = $position;
        $this->total = $total;
        $this->invert = $invert;
        $this->elementName = $elementName;
    }

    public function toArray(): array
    {
        return [
            'name' => 'typo3-backend-form-update-bitmask',
            'data' => [
                'position' => $this->position,
                'total' => $this->total,
                'invert' => $this->invert,
                'elementName' => $this->elementName,
            ],
        ];
    }
}
