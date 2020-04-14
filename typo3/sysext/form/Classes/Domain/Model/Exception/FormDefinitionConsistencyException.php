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

namespace TYPO3\CMS\Form\Domain\Model\Exception;

use TYPO3\CMS\Form\Domain\Model\Exception;

/**
 * This exception is thrown if the form definition would get an inconsistent state, like
 * adding a page to two different forms
 */
class FormDefinitionConsistencyException extends Exception
{
}
