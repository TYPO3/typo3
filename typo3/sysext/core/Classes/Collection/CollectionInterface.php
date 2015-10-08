<?php
namespace TYPO3\CMS\Core\Collection;

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
 * Marker interface for collection classes
 *
 * Collections are containers-classes handling the storage
 * of data values (f.e. strings, records, relations) in a
 * common and generic way, while the class manages the storage
 * in an appropriate way itself
 */
interface CollectionInterface extends \Iterator, \Serializable, \Countable
{
}
