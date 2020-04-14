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

namespace TYPO3\CMS\Form\Domain\Runtime\Exception;

use TYPO3\CMS\Form\Domain\Exception;

/**
 * This Exception is thrown in the FormRuntime if the PropertyMapper throws
 * a \TYPO3\CMS\Extbase\Property\Exception. It adds some more Information to
 * better understand why the PropertyMapper failed to map the properties
 */
class PropertyMappingException extends Exception
{
}
