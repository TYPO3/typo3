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

namespace TYPO3\CMS\Core\Tests\Unit\LinkHandling\Event;

use TYPO3\CMS\Core\LinkHandling\Event\BeforeTypoLinkEncodedEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BeforeTypoLinkEncodedEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function gettersReturnInitializedObjects(): void
    {
        $parameters = [
            'url' => 'https://example.com',
            'target' => '_blank',
            'class' => 'some-class',
            'title' => '',
            'additionalParams' => '&foo=bar',
        ];
        $typoLinkParts = [
            'additionalParams' => '&foo=bar',
            'title' => '',
            'class' => 'some-class',
            'target' => '_blank',
            'url' => 'https://example.com',
        ];
        $delimiter = '*';
        $emptyValueSymbol = '-';

        $event = new BeforeTypoLinkEncodedEvent(
            parameters: $parameters,
            typoLinkParts: $typoLinkParts,
            delimiter: $delimiter,
            emptyValueSymbol: $emptyValueSymbol
        );

        self::assertEquals($parameters, $event->getParameters());
        self::assertEquals($typoLinkParts, $event->getTypoLinkParts());
        self::assertEquals($delimiter, $event->getDelimiter());
        self::assertEquals($emptyValueSymbol, $event->getEmptyValueSymbol());
    }

    /**
     * @test
     */
    public function setOverwritesParameters(): void
    {
        $parameters = [
            'url' => 'https://example.com',
            'target' => '_blank',
            'class' => 'some-class',
            'title' => '',
            'additionalParams' => '&foo=bar',
        ];
        $typoLinkParts = [
            'additionalParams' => '&foo=bar',
            'title' => '',
            'class' => 'some-class',
            'target' => '_blank',
            'url' => 'https://example.com',
        ];
        $delimiter = '*';
        $emptyValueSymbol = '-';

        $event = new BeforeTypoLinkEncodedEvent(
            parameters: $parameters,
            typoLinkParts: $typoLinkParts,
            delimiter: $delimiter,
            emptyValueSymbol: $emptyValueSymbol
        );

        self::assertEquals($parameters, $event->getParameters());
        self::assertEquals($typoLinkParts, $event->getTypoLinkParts());
        self::assertEquals($delimiter, $event->getDelimiter());
        self::assertEquals($emptyValueSymbol, $event->getEmptyValueSymbol());

        $modifiedParameters = array_replace($event->getParameters(), ['class' => 'new-class', 'foo' => 'bar']);

        $event->setParameters($modifiedParameters);

        self::assertEquals($modifiedParameters, $event->getParameters());
    }
}
