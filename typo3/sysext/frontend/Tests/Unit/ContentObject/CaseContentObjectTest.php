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

namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

use TYPO3\CMS\Frontend\ContentObject\CaseContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\TextContentObject;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CaseContentObjectTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var CaseContentObject|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $subject;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();

        $contentObjectRenderer = new ContentObjectRenderer($tsfe);
        $contentObjectRenderer->setContentObjectClassMap([
            'CASE' => CaseContentObject::class,
            'TEXT' => TextContentObject::class,
        ]);
        $this->subject = new CaseContentObject($contentObjectRenderer);
    }

    /**
     * @test
     */
    public function renderReturnsEmptyStringIfNoKeyMatchesAndIfNoDefaultObjectIsSet(): void
    {
        $conf = [
            'key' => 'not existing'
        ];
        self::assertSame('', $this->subject->render($conf));
    }

    /**
     * @test
     */
    public function renderReturnsContentFromDefaultObjectIfKeyDoesNotExist(): void
    {
        $conf = [
            'key' => 'not existing',
            'default' => 'TEXT',
            'default.' => [
                'value' => 'expected value'
            ],
        ];
        self::assertSame('expected value', $this->subject->render($conf));
    }
}
