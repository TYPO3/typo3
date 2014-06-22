<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

/**
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
 * This object holds validation errors for one argument.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
 */
class ArgumentError extends \TYPO3\CMS\Extbase\Validation\PropertyError {

	/**
	 * @var string The default (english) error message.
	 */
	protected $message = 'Validation errors for argument "%s"';

	/**
	 * @var string The error code
	 */
	protected $code = 1245107351;
}
