<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Resource\Search\Result;

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
 * Representation of a result for a search for files performed by FileSearchQuery,
 * which is a collection of matching files.
 */
interface FileSearchResultInterface extends \Countable, \Iterator
{
}
