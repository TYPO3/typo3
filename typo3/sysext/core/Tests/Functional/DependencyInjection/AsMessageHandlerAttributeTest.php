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

namespace TYPO3\CMS\Core\Tests\Functional\DependencyInjection;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Messenger\MessageBusInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestMessageHandler\Message\Captains;
use TYPO3Tests\TestMessageHandler\Message\Princesses;

final class AsMessageHandlerAttributeTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_message_handler',
    ];

    #[Test]
    public function messageHandlerAttributesPassMessageToAllSortsOfImplementations(): void
    {
        $messageBus = $this->get(MessageBusInterface::class);
        $princesses = new Princesses(names: ['Rosalind']);
        $captains = new Captains(names: ['Iglo']);
        $messageBus->dispatch($captains);
        $messageBus->dispatch($princesses);

        $expectedPrincesses = [
            'Daenerys',
            'Daisy',
            'Elsa',
            'Anna',
            'Rosalind',
            'Janeway',
            'Peach',
        ];
        $expectedCaptains = [
            'Iglo',
            'Daenerys',
            'Janeway',
            'Burn ham',
            'Pike',
            'Picard',
            'Kirk',
        ];
        self::assertEqualsCanonicalizing($expectedPrincesses, $princesses->names);
        self::assertEqualsCanonicalizing($expectedCaptains, $captains->names);
    }

}
