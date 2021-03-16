<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Functional\Authentication\Fixtures;

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

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;

class AnyUserAuthentication extends AbstractUserAuthentication
{
    /**
     * @var array
     */
    public $uc = [];

    /**
     * @var string
     */
    public $loginType = 'ANY';

    public function __construct(string $sessionId)
    {
        parent::__construct();
        $this->id = $sessionId;
    }
}
