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

namespace TYPO3\CMS\Extbase\Tests\UnitDeprecated\Object\Container;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Extbase\Object\Container\Container;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;
use TYPO3\CMS\Extbase\Tests\UnitDeprecated\Object\Container\Fixtures\ArgumentTestClassForPublicPropertyInjection;
use TYPO3\CMS\Extbase\Tests\UnitDeprecated\Object\Container\Fixtures\PublicPropertyInjectClass;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ContainerTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var Container
     */
    protected $subject;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->getMockBuilder(Logger::class)
            ->setMethods(['notice'])
            ->disableOriginalConstructor()
            ->getMock();
        $reflectionService = new ReflectionService();

        $notFoundException = new class() extends \Exception implements NotFoundExceptionInterface {
        };

        $psrContainer = $this->getMockBuilder(ContainerInterface::class)
            ->setMethods(['has', 'get'])
            ->getMock();
        $psrContainer->expects(self::any())->method('has')->willReturn(false);
        $psrContainer->expects(self::any())->method('get')->will(self::throwException($notFoundException));

        $this->subject = $this->getMockBuilder(Container::class)
            ->setConstructorArgs([$psrContainer])
            ->setMethods(['getLogger', 'getReflectionService'])
            ->getMock();
        $this->subject->setLogger($this->logger);
        $this->subject->expects(self::any())->method('getReflectionService')->willReturn($reflectionService);
    }

    /**
     * @test
     */
    public function getInstanceInjectsPublicProperties()
    {
        $object = $this->subject->getInstance(PublicPropertyInjectClass::class);
        self::assertInstanceOf(ArgumentTestClassForPublicPropertyInjection::class, $object->foo);
    }
}
