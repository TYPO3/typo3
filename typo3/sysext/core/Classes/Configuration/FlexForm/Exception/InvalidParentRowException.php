<?php
namespace TYPO3\CMS\Core\Configuration\FlexForm\Exception;

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

/**
 * Exception thrown if lookup of a parent row in a tree does not return a valid result
 * during flex from structure lookup.
 */
class InvalidParentRowException extends AbstractInvalidDataStructureException
{
}
