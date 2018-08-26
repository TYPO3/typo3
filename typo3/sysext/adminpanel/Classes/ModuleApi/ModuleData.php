<?php
declare(strict_types = 1);

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

namespace TYPO3\CMS\Adminpanel\ModuleApi;

/**
 * ModuleData is a simple wrapper object which extends ArrayObject
 * which is used to hold Adminpanel module data
 *
 * It's a separate class to add semantic meaning to its' usage
 */
class ModuleData extends \ArrayObject
{
}
