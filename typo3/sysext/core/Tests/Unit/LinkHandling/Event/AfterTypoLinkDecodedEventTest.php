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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\LinkHandling\Event\AfterTypoLinkDecodedEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AfterTypoLinkDecodedEventTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnInitializedObjects(): void
    {
        $typoLink = 'https://example.com _blank some-class  &foo=bar';
        $typoLinkParts = [
            'url' => 'https://example.com',
            'target' => '_blank',
            'class' => 'some-class',
            'title' => '',
            'additionalParams' => '&foo=bar',
        ];
        $delimiter = '*';
        $emptyValueSymbol = '-';

        $event = new AfterTypoLinkDecodedEvent(
            typoLinkParts: $typoLinkParts,
            typoLink: $typoLink,
            delimiter: $delimiter,
            emptyValueSymbol: $emptyValueSymbol
        );

        self::assertEquals($typoLinkParts, $event->getTypoLinkParts());
        self::assertEquals($typoLink, $event->getTypoLink());
        self::assertEquals($delimiter, $event->getDelimiter());
        self::assertEquals($emptyValueSymbol, $event->getEmptyValueSymbol());
    }

    #[Test]
    public function setOverwritesTypoLinkParts(): void
    {
        $typoLink = 'https://example.com _blank some-class  &foo=bar';
        $typoLinkParts = [
            'url' => 'https://example.com',
            'target' => '_blank',
            'class' => 'some-class',
            'title' => '',
            'additionalParams' => '&foo=bar',
        ];
        $delimiter = '*';
        $emptyValueSymbol = '-';

        $event = new AfterTypoLinkDecodedEvent(
            typoLinkParts: $typoLinkParts,
            typoLink: $typoLink,
            delimiter: $delimiter,
            emptyValueSymbol: $emptyValueSymbol
        );

        self::assertEquals($typoLinkParts, $event->getTypoLinkParts());
        self::assertEquals($typoLink, $event->getTypoLink());
        self::assertEquals($delimiter, $event->getDelimiter());
        self::assertEquals($emptyValueSymbol, $event->getEmptyValueSymbol());

        $modifiedTypoLinkParts = array_replace($event->getTypoLinkParts(), ['class' => 'new-class', 'foo' => 'bar']);

        $event->setTypoLinkParts($modifiedTypoLinkParts);

        self::assertEquals($modifiedTypoLinkParts, $event->getTypoLinkParts());
    }
}
