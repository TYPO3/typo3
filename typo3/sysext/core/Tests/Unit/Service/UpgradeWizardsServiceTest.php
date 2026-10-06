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

namespace TYPO3\CMS\Core\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Service\UpgradeWizardsService;
use TYPO3\CMS\Core\Upgrades\RowUpdater\RowUpdaterRegistry;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UpgradeWizardsServiceTest extends UnitTestCase
{
    #[Test]
    public function markWizardUndoneKeepsWizardDoneIfNoUpdateIsNecessary(): void
    {
        $registry = $this->createMock(Registry::class);
        $registry->method('get')->willReturn(1);
        $registry->expects($this->never())->method('set');

        $subject = $this->createSubject($this->createWizard(false), $registry);

        self::assertFalse($subject->markWizardUndone('testWizard'));
    }

    #[Test]
    public function markWizardUndoneMarksWizardUndoneIfUpdateIsNecessary(): void
    {
        $registry = $this->createMock(Registry::class);
        $registry->method('get')->willReturn(1);
        $registry->expects($this->once())->method('set')->with('installUpdate', self::anything(), 0);

        $subject = $this->createSubject($this->createWizard(true), $registry);

        self::assertTrue($subject->markWizardUndone('testWizard'));
    }

    private function createSubject(UpgradeWizardInterface $wizard, Registry $registry): UpgradeWizardsService
    {
        return new UpgradeWizardsService(
            new UpgradeWizardRegistry(new ServiceLocator(['testWizard' => static fn(): UpgradeWizardInterface => $wizard])),
            new RowUpdaterRegistry(new ServiceLocator([])),
            $registry,
        );
    }

    private function createWizard(bool $updateNecessary): UpgradeWizardInterface
    {
        $wizard = self::createStub(UpgradeWizardInterface::class);
        $wizard->method('getTitle')->willReturn('Test wizard');
        $wizard->method('getDescription')->willReturn('');
        $wizard->method('updateNecessary')->willReturn($updateNecessary);
        return $wizard;
    }
}
