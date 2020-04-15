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

namespace TYPO3\CMS\Backend\Tests\Unit\View\BackendLayout;

use Prophecy\Argument;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testing backend layout representation.
 */
class BackendLayoutTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function invalidIdentifierIsRecognizedOnCreation()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1381597630);
        $identifier = StringUtility::getUniqueId('identifier__');
        $title = StringUtility::getUniqueId('title');
        $configuration = StringUtility::getUniqueId('configuration');
        new BackendLayout($identifier, $title, $configuration);
    }

    /**
     * @test
     */
    public function objectIsCreated()
    {
        $backendLayoutView = $this->prophesize(BackendLayoutView::class);
        $backendLayoutView->parseStructure(Argument::any())->willReturn([]);
        GeneralUtility::setSingletonInstance(BackendLayoutView::class, $backendLayoutView->reveal());

        $identifier = StringUtility::getUniqueId('identifier');
        $title = StringUtility::getUniqueId('title');
        $configuration = StringUtility::getUniqueId('configuration');
        $backendLayout = new BackendLayout($identifier, $title, $configuration);

        self::assertEquals($identifier, $backendLayout->getIdentifier());
        self::assertEquals($title, $backendLayout->getTitle());
        self::assertEquals($configuration, $backendLayout->getConfiguration());
    }
}
