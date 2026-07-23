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

namespace TYPO3\CMS\Backend\Tests\Unit\Wizard;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Backend\Wizard\WizardProviderInterface;
use TYPO3\CMS\Backend\Wizard\WizardProviderRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class WizardProviderRegistryTest extends UnitTestCase
{
    private WizardProviderRegistry $subject;

    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\TYPO3\CMS\Backend\Wizard\WizardProviderInterface
     */
    private \PHPUnit\Framework\MockObject\Stub $wizardProviderStub;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wizardProviderStub = self::createStub(WizardProviderInterface::class);
        $this->subject = new WizardProviderRegistry(new ServiceLocator(['foo' => fn() => $this->wizardProviderStub]));
    }

    #[Test]
    public function returnsRequestsWizardProvider(): void
    {
        self::assertEquals($this->wizardProviderStub, $this->subject->getProvider('foo'));
    }

    #[Test]
    public function throwsExceptionOnMissingWizardProvider(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->subject->getProvider('bar');
    }
}
