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

namespace TYPO3\CMS\Core\Tests\Functional\Authentication\Fixtures;

use TYPO3\CMS\Core\Authentication\AbstractAuthenticationService;

/**
 * Authentication service whose behavior is configured per registered service key,
 * used to characterize the auth service chain semantics of checkAuthentication().
 *
 * getUser() and authUser() are the instance-level service API, invoked by the
 * chain on instances it creates internally - the test never gets hold of them.
 * Static properties are therefore the only channel to configure the behavior
 * and record invocations, and resetState() is static because it clears exactly
 * that static state.
 */
final class FixtureAuthenticationService extends AbstractAuthenticationService
{
    /**
     * @var array<string, int> service key => authUser() return code
     */
    public static array $authUserReturnCodes = [];

    /**
     * @var array<string, array<string, mixed>|false> service key => getUser() result
     */
    public static array $getUserReturns = [];

    /**
     * @var list<string> chronological log of "<serviceKey>::<method>" invocations
     */
    public static array $calledMethods = [];

    public static function resetState(): void
    {
        self::$authUserReturnCodes = [];
        self::$getUserReturns = [];
        self::$calledMethods = [];
    }

    public function getUser(): array|false
    {
        self::$calledMethods[] = $this->getServiceKey() . '::getUser';
        return self::$getUserReturns[$this->getServiceKey()] ?? false;
    }

    public function authUser(array $user): int
    {
        self::$calledMethods[] = $this->getServiceKey() . '::authUser';
        return self::$authUserReturnCodes[$this->getServiceKey()] ?? 100;
    }
}
