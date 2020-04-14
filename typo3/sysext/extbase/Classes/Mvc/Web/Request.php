<?php

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

namespace TYPO3\CMS\Extbase\Mvc\Web;

/**
 * Represents a web request.
 * @deprecated since TYPO3 10.2, will be removed in version 11.0.
 */
class Request extends \TYPO3\CMS\Extbase\Mvc\Request
{
    /**
     * @var string The requested representation format
     */
    protected $format = 'html';
}
