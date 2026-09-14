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

namespace TYPO3\CMS\Reactions\Form\Element;

/**
 * Where a row of the reaction field map takes its value from.
 *
 * @internal This is a specific FormEngine implementation and is not considered part of the Public TYPO3 API.
 */
enum FieldMapMode: string
{
    case UNSET = 'unset';
    case FIXED = 'fixed';
    case PAYLOAD = 'payload';
}
