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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataGroup;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataGroup\FlexFormSegment;
use TYPO3\CMS\Backend\Form\FormDataGroup\OrderedProviderList;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FlexFormSegmentTest extends UnitTestCase
{
    #[Test]
    public function compileReturnsIncomingData(): void
    {
        $subject = new FlexFormSegment(
            new OrderedProviderList($this->createMock(FrontendInterface::class), new DependencyOrderingService())
        );
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'] = [];
        $input = ['foo'];
        self::assertEquals($input, $subject->compile($input));
    }

    #[Test]
    public function compileReturnsResultChangedByDataProvider(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'] = [
            \stdClass::class => [],
        ];
        GeneralUtility::addInstance(
            \stdClass::class,
            new class () extends \stdClass implements FormDataProviderInterface {
                public function addData(array $result)
                {
                    return ['foo'];
                }
            }
        );
        $subject = new FlexFormSegment(
            new OrderedProviderList($this->createMock(FrontendInterface::class), new DependencyOrderingService())
        );
        self::assertEquals(['foo'], $subject->compile([]));
    }

    #[Test]
    public function compileThrowsExceptionIfDataProviderDoesNotImplementInterface(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1485299408);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['flexFormSegment'] = [
            \stdClass::class => [],
        ];
        $subject = new FlexFormSegment(
            new OrderedProviderList($this->createMock(FrontendInterface::class), new DependencyOrderingService())
        );
        $subject->compile([]);
    }
}
