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

namespace TYPO3\CMS\Dashboard\Widgets;

/**
 * In case a widget should provide additional data as JSON payload, the widget must implement this interface.
 */
interface EventDataInterface
{
    /**
     * This method returns data which should be send to the widget as JSON encoded value.
     *
     * @return array
     */
    public function getEventData(): array;
}
