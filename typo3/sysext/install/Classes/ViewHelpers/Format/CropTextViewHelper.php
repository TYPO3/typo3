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

namespace TYPO3\CMS\Install\ViewHelpers\Format;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Crop a string to a given length.
 *
 * @internal
 */
final class CropTextViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('maxCharacters', 'int', 'Positive int. Place where to truncate the string', true);
    }

    public function render(): string
    {
        $maxCharacters = (int)$this->arguments['maxCharacters'];
        $stringToCrop = (string)$this->renderChildren();
        if ($maxCharacters <= 0) {
            return $stringToCrop;
        }
        $croppedString = mb_substr($stringToCrop, 0, $maxCharacters, 'utf-8');
        if ($croppedString !== $stringToCrop) {
            return $croppedString . '…';
        }
        return $stringToCrop;
    }
}
