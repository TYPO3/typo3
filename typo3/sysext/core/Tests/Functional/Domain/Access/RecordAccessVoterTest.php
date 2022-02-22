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

namespace TYPO3\CMS\Core\Tests\Functional\Domain\Access;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Domain\Access\RecordAccessVoter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RecordAccessVoterTest extends FunctionalTestCase
{
    protected RecordAccessVoter $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new RecordAccessVoter(
            $this->get(EventDispatcherInterface::class)
        );
    }

    public function accessGrantedTestDataProvider(): \Generator
    {
        yield 'No access permissions set' => [
            'pages',
            [
                'uid' => 1,
            ],
            true,
        ];
        yield 'Record disabled' => [
            'pages',
            [
                'uid' => 1,
                'hidden' => 1,
            ],
            false,
        ];
        yield 'Starttime set' => [
            'pages',
            [
                'uid' => 1,
                'starttime' => 43,
            ],
            false,
        ];
        yield 'Endtime set' => [
            'pages',
            [
                'uid' => 1,
                'endtime' => 41,
            ],
            false,
        ];
        yield 'Invalid endtime' => [
            'pages',
            [
                'uid' => 1,
                'endtime' => 0,
            ],
            true,
        ];
        yield 'group access set' => [
            'pages',
            [
                'uid' => 1,
                'fe_group' => '1,2',
            ],
            false,
        ];
        yield 'all enable fields set - valid' => [
            'pages',
            [
                'uid' => 1,
                'hidden' => 0,
                'starttime' => 41,
                'endtime' => 43,
                'fe_group' => '3,4',
            ],
            true,
        ];
    }

    /**
     * @test
     * @dataProvider accessGrantedTestDataProvider
     */
    public function accessGrantedTest(string $table, array $record, bool $access): void
    {
        $GLOBALS['SIM_ACCESS_TIME'] = 42;
        $context = new Context(['frontend.user' => new UserAspect(null, [3, 4])]);

        self::assertEquals($access, $this->subject->accessGranted($table, $record, $context));
    }

    /**
     * @test
     */
    public function accessGrantedRespectsVisibilityAspect(): void
    {
        // Page is available even if the "disabled" flag is set
        self::assertTrue($this->subject->accessGranted(
            'pages',
            ['uid' => 1, 'hidden' => 1],
            new Context(['visibility' => new VisibilityAspect(includeHiddenPages: true)])
        ));

        // Content is available even if the "disabled" flag is set
        self::assertTrue($this->subject->accessGranted(
            'tt_content',
            ['uid' => 1, 'hidden' => 1],
            new Context(['visibility' => new VisibilityAspect(includeHiddenContent: true)])
        ));
    }

    public function groupAccessGrantedTestDataProvider(): \Generator
    {
        yield 'No enable field' => [
            'aTable',
            [
                'uid' => 1,
            ],
            true,
        ];
        yield 'No group access defined' => [
            'pages',
            [
                'uid' => 1,
                'fe_group' => '',
            ],
            true,
        ];
        yield 'Insufficient permissions' => [
            'pages',
            [
                'uid' => 1,
                'fe_group' => '1,2',
            ],
            false,
        ];
        yield 'Sufficient permissions' => [
            'pages',
            [
                'uid' => 1,
                'fe_group' => '3,4',
            ],
            true,
        ];
    }

    /**
     * @test
     * @dataProvider groupAccessGrantedTestDataProvider
     */
    public function groupAccessGrantedTest(string $table, array $record, bool $access): void
    {
        $context = new Context([
            'frontend.user' => new UserAspect(null, [3, 4]),
        ]);

        self::assertEquals($access, $this->subject->groupAccessGranted($table, $record, $context));
    }

    /**
     * @test
     */
    public function accessGrantedForPageInRootLineReturnsTrueForDisabledExtendToSubpages(): void
    {
        self::assertTrue($this->subject->accessGrantedForPageInRootLine(['uid ' => 1, 'hidden' => 1], new Context()));
    }
}
