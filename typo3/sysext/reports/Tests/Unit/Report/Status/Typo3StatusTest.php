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

namespace TYPO3\CMS\Reports\Tests\Unit\Report\Status;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Reports\Report\Status\Typo3Status;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class Typo3StatusTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->getLL(Argument::any())->willReturn('');
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
    }

    /**
     * @test
     */
    public function getStatusReturnsXclassStatusObjectWithSeverityOkIfNoXclassExists(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'] = [];
        $fixture = new Typo3Status();
        $result = $fixture->getStatus();
        $statusObject = $result['registeredXclass'];
        self::assertSame(ContextualFeedbackSeverity::OK, $statusObject->getSeverity());
    }

    /**
     * @test
     */
    public function getStatusReturnsXclassStatusObjectWithSeverityNoticeIfXclassExists(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'] = [
            'foo' => [
                'className' => 'bar',
            ],
        ];
        $fixture = new Typo3Status();
        $result = $fixture->getStatus();
        $statusObject = $result['registeredXclass'];
        self::assertSame(ContextualFeedbackSeverity::NOTICE, $statusObject->getSeverity());
    }
}
