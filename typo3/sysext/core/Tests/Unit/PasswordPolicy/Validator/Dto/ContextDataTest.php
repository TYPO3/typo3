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

namespace TYPO3\CMS\Core\Tests\Unit\PasswordPolicy\Validator\Dto;

use TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ContextDataTest extends UnitTestCase
{
    /**
     * @test
     */
    public function contextDataContainsExpectedDefaults(): void
    {
        $subject = new ContextData();

        self::assertEquals('BE', $subject->getLoginMode());
        self::assertEquals('', $subject->getCurrentPasswordHash());
        self::assertEquals('', $subject->getNewUsername());
        self::assertEquals('', $subject->getNewUserFirstName());
        self::assertEquals('', $subject->getNewUserLastName());
        self::assertEquals('', $subject->getNewUserFullName());
    }

    /**
     * @test
     */
    public function contextDataPropertiesSetInConstructor(): void
    {
        $subject = new ContextData(
            'FE',
            'passwordhash',
            'username',
            'firstname',
            'lastname',
            'fullname',
        );

        self::assertEquals('FE', $subject->getLoginMode());
        self::assertEquals('passwordhash', $subject->getCurrentPasswordHash());
        self::assertEquals('username', $subject->getNewUsername());
        self::assertEquals('firstname', $subject->getNewUserFirstName());
        self::assertEquals('lastname', $subject->getNewUserLastName());
        self::assertEquals('fullname', $subject->getNewUserFullName());
    }

    /**
     * @test
     */
    public function getDataReturnsExpectedDataForContextDataSetInConstructor(): void
    {
        $subject = new ContextData(newUserFullName: 'Firstname Lastname');

        self::assertEquals('Firstname Lastname', $subject->getData('newUserFullName'));
    }

    /**
     * @test
     */
    public function setDataSetsData(): void
    {
        $subject = new ContextData();
        $subject->setData('customKey', 'customValue');

        self::assertEquals('customValue', $subject->getData('customKey'));
    }
}
