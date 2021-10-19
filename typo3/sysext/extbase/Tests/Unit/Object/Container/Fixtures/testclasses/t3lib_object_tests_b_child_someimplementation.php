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

/**
 * test class B-Child that extends Class B (therefore depends also on Class C)
 */
class t3lib_object_tests_b_child_someimplementation extends \t3lib_object_tests_b implements \t3lib_object_tests_someinterface
{
}
