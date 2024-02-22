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

namespace TYPO3\CMS\Form\Tests\Unit\Mvc\Persistence\Event;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Mvc\Persistence\Event\AfterFormDefinitionLoadedEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AfterFormDefinitionLoadedEventTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnInitializedObjects(): void
    {
        $persistenceIdentifier = 'ext-form-identifier';
        $cacheKey = 'formLoad' . md5($persistenceIdentifier);
        $formDefinition = ['identifier' => $persistenceIdentifier];

        $event = new AfterFormDefinitionLoadedEvent(
            formDefinition: $formDefinition,
            persistenceIdentifier: $persistenceIdentifier,
            cacheKey: $cacheKey,
        );

        self::assertSame($formDefinition, $event->getFormDefinition());
        self::assertSame($persistenceIdentifier, $event->getPersistenceIdentifier());
        self::assertSame($cacheKey, $event->getCacheKey());
    }

    #[Test]
    public function setterOverwritesResult(): void
    {
        $persistenceIdentifier = 'ext-form-identifier';
        $cacheKey = 'formLoad' . md5($persistenceIdentifier);
        $formDefinition = ['identifier' => $persistenceIdentifier];

        $event = new AfterFormDefinitionLoadedEvent(
            formDefinition: $formDefinition,
            persistenceIdentifier: $persistenceIdentifier,
            cacheKey: $cacheKey,
        );

        self::assertSame($formDefinition, $event->getFormDefinition());
        self::assertSame($persistenceIdentifier, $event->getPersistenceIdentifier());
        self::assertSame($cacheKey, $event->getCacheKey());

        $modifiedFormDefinition = ['identifier' => 'modified-ext-form-identifier'];
        $event->setFormDefinition($modifiedFormDefinition);

        self::assertSame($modifiedFormDefinition, $event->getFormDefinition());
    }
}
