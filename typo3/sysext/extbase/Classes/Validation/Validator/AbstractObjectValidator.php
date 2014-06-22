<?php
namespace TYPO3\CMS\Extbase\Validation\Validator;

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
 * An abstract Object Validator
 *
 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
 */
abstract class AbstractObjectValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator implements \TYPO3\CMS\Extbase\Validation\Validator\ObjectValidatorInterface {

	/**
	 * Allows to set a container to keep track of validated instances.
	 *
	 * @param \SplObjectStorage $validatedInstancesContainer A container to keep track of validated instances
	 * @return void
	 * @api
	 */
	public function setValidatedInstancesContainer(\SplObjectStorage $validatedInstancesContainer) {
		// deliberately empty
	}
}
