<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Step\Backend;

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
 * A backend user with admin access
 */
class Admin extends \AcceptanceTester
{
    /**
     * The session cookie that is used if the session is injected.
     * This session must exist in the database fixture to get a logged in state.
     *
     * @var string
     */
    protected $sessionCookie = '886526ce72b86870739cc41991144ec1';
}
