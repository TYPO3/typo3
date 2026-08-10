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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Domain\Access\RecordAccessVoter;
use TYPO3\CMS\Core\Domain\DateTimeFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecordAccessVoterTest extends FunctionalTestCase
{
    private RecordAccessVoter $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new RecordAccessVoter(
            $this->get(EventDispatcherInterface::class),
            $this->get(TcaSchemaFactory::class),
        );
    }

    /**
     * The date aspect timestamp 90 is floored to the full minute 60 when starttime / endtime are evaluated,
     * so records use 50 for "in the past" and 70 for "in the future".
     */
    private function createContextWithAccessTime(): Context
    {
        $context = new Context();
        $context->setAspect('date', new DateTimeAspect(DateTimeFactory::createFromTimestamp(90)));
        return $context;
    }

    public static function accessGrantedTestDataProvider(): \Generator
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
                'starttime' => 70,
            ],
            false,
        ];
        yield 'Endtime set' => [
            'pages',
            [
                'uid' => 1,
                'endtime' => 50,
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
                'starttime' => 50,
                'endtime' => 70,
                'fe_group' => '3,4',
            ],
            true,
        ];
    }

    #[DataProvider('accessGrantedTestDataProvider')]
    #[Test]
    public function accessGrantedTest(string $table, array $record, bool $access): void
    {
        $context = $this->createContextWithAccessTime();
        $context->setAspect('frontend.user', new UserAspect(null, [3, 4]));
        self::assertEquals($access, $this->subject->accessGranted($table, $record, $context));
    }

    #[Test]
    public function accessGrantedRespectsVisibilityAspect(): void
    {
        // Page is available even if the "disabled" flag is set
        $context = $this->createContextWithAccessTime();
        $context->setAspect('visibility', new VisibilityAspect(includeHiddenPages: true));
        self::assertTrue($this->subject->accessGranted(
            'pages',
            ['uid' => 1, 'hidden' => 1],
            $context
        ));

        // Content is available even if the "disabled" flag is set
        $context = $this->createContextWithAccessTime();
        $context->setAspect('visibility', new VisibilityAspect(includeHiddenContent: true));
        self::assertTrue($this->subject->accessGranted(
            'tt_content',
            ['uid' => 1, 'hidden' => 1],
            $context
        ));
    }

    #[Test]
    public function accessGrantedRespectsIncludeScheduledRecordsForStarttime(): void
    {
        // Record with starttime in the future should be denied by default
        $context = $this->createContextWithAccessTime();
        self::assertFalse($this->subject->accessGranted(
            'pages',
            ['uid' => 1, 'starttime' => 70],
            $context
        ));

        // Record with starttime in the future should be allowed when includeScheduledRecords is true
        $context = $this->createContextWithAccessTime();
        $context->setAspect('visibility', new VisibilityAspect(includeScheduledRecords: true));
        self::assertTrue($this->subject->accessGranted(
            'pages',
            ['uid' => 1, 'starttime' => 70],
            $context
        ));
    }

    #[Test]
    public function accessGrantedRespectsIncludeScheduledRecordsForEndtime(): void
    {
        // Record with endtime in the past should be denied by default
        $context = $this->createContextWithAccessTime();
        self::assertFalse($this->subject->accessGranted(
            'pages',
            ['uid' => 1, 'endtime' => 50],
            $context
        ));

        // Record with endtime in the past should be allowed when includeScheduledRecords is true
        $context = $this->createContextWithAccessTime();
        $context->setAspect('visibility', new VisibilityAspect(includeScheduledRecords: true));
        self::assertTrue($this->subject->accessGranted(
            'pages',
            ['uid' => 1, 'endtime' => 50],
            $context
        ));
    }

    public static function groupAccessGrantedTestDataProvider(): \Generator
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

    #[DataProvider('groupAccessGrantedTestDataProvider')]
    #[Test]
    public function groupAccessGrantedTest(string $table, array $record, bool $access): void
    {
        $context = $this->createContextWithAccessTime();
        $context->setAspect('frontend.user', new UserAspect(null, [3, 4]));
        self::assertEquals($access, $this->subject->groupAccessGranted($table, $record, $context));
    }

    #[Test]
    public function accessGrantedForPageInRootLineReturnsTrueForDisabledExtendToSubpages(): void
    {
        self::assertTrue($this->subject->accessGrantedForPageInRootLine(['uid ' => 1, 'hidden' => 1], new Context()));
    }
}
