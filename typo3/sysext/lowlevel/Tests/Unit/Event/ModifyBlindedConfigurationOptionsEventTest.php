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

namespace TYPO3\CMS\Lowlevel\Tests\Unit\Event;

use TYPO3\CMS\Lowlevel\Event\ModifyBlindedConfigurationOptionsEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ModifyBlindedConfigurationOptionsEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function gettersReturnInitializedObjects(): void
    {
        $identifier = 'myidentifier';
        $configuration = ['foo' => 'bar'];
        $blindedConfiguration = ['foo' => '***'];
        $event = new ModifyBlindedConfigurationOptionsEvent($configuration, $identifier);
        self::assertEquals($configuration, $event->getBlindedConfigurationOptions());
        self::assertEquals($identifier, $event->getProviderIdentifier());
        $event->setBlindedConfigurationOptions($blindedConfiguration);
        self::assertEquals($blindedConfiguration, $event->getBlindedConfigurationOptions());
    }
}
