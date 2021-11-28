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

namespace TYPO3\CMS\Core\Tests\Unit\DataHandling\SoftReference;

use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\DataHandling\SoftReference\EmailSoftReferenceParser;
use TYPO3\CMS\Core\DataHandling\SoftReference\ExtensionPathSoftReferenceParser;
use TYPO3\CMS\Core\DataHandling\SoftReference\NotifySoftReferenceParser;
use TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserFactory;
use TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserInterface;
use TYPO3\CMS\Core\DataHandling\SoftReference\SubstituteSoftReferenceParser;
use TYPO3\CMS\Core\DataHandling\SoftReference\TypolinkSoftReferenceParser;
use TYPO3\CMS\Core\DataHandling\SoftReference\TypolinkTagSoftReferenceParser;
use TYPO3\CMS\Core\DataHandling\SoftReference\UrlSoftReferenceParser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
abstract class AbstractSoftReferenceParserTest extends UnitTestCase
{
    use ProphecyTrait;

    protected bool $resetSingletonInstances = true;

    protected function getParserByKey($softrefKey): SoftReferenceParserInterface
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $runtimeCache = $this->prophesize(FrontendInterface::class);
        $logger = $this->prophesize(LoggerInterface::class);

        $softReferenceParserFactory = new SoftReferenceParserFactory($runtimeCache->reveal(), $logger->reveal());
        $softReferenceParserFactory->addParser(new SubstituteSoftReferenceParser(), 'substitute');
        $softReferenceParserFactory->addParser(new NotifySoftReferenceParser(), 'notify');
        $softReferenceParserFactory->addParser(new TypolinkSoftReferenceParser($eventDispatcher->reveal()), 'typolink');
        $softReferenceParserFactory->addParser(new TypolinkTagSoftReferenceParser(), 'typolink_tag');
        $softReferenceParserFactory->addParser(new ExtensionPathSoftReferenceParser(), 'ext_fileref');
        $softReferenceParserFactory->addParser(new EmailSoftReferenceParser(), 'email');
        $softReferenceParserFactory->addParser(new UrlSoftReferenceParser(), 'url');

        return $softReferenceParserFactory->getSoftReferenceParser($softrefKey);
    }
}
