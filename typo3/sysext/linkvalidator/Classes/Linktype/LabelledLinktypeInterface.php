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

namespace TYPO3\CMS\Linkvalidator\Linktype;

/**
 * This class is used for composition in addition to LinktypeInterface,
 * and provides the ability to expand implementing classes with the possibility
 * to provide a custom Linktype label. It is utilized in the abstract class
 * `AbstractLinktype`.
 */
interface LabelledLinktypeInterface
{
    /**
     * Get localized label for this Linktype to be displayed in Backend user interface.
     * Implementing classes should implement this method.
     */
    public function getReadableName(): string;
}
