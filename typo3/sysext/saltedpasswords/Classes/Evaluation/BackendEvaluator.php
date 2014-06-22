<?php
namespace TYPO3\CMS\Saltedpasswords\Evaluation;

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
 * Class implementing salted evaluation methods for BE users.
 *
 * @author Marcus Krause <marcus#exp2009@t3sec.info>
 * @author Steffen Ritter <info@rs-websystems.de>
 * @since 2009-06-14
 */
class BackendEvaluator extends \TYPO3\CMS\Saltedpasswords\Evaluation\Evaluator {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->mode = 'BE';
	}

}
