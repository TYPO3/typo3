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

namespace TYPO3\CMS\Dashboard\Utility;

use TYPO3\CMS\Dashboard\Widgets\Interfaces\ButtonProviderInterface;

class ButtonUtility
{
    public static function generateButtonConfig(?ButtonProviderInterface $buttonProvider): array
    {
        if ($buttonProvider instanceof ButtonProviderInterface && $buttonProvider->getTitle() && $buttonProvider->getLink()) {
            return [
                'text' => $buttonProvider->getTitle(),
                'link' => $buttonProvider->getLink(),
                'target' => $buttonProvider->getTarget(),
            ];
        }

        return [];
    }
}
