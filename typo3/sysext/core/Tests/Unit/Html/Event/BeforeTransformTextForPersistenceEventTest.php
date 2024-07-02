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

namespace TYPO3\CMS\Core\Tests\Unit\Html\Event;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Html\Event\BeforeTransformTextForPersistenceEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BeforeTransformTextForPersistenceEventTest extends UnitTestCase
{
    protected array $procOptions = ['overruleMode' => 'default', 'allowTagsOutside' => 'hr,abbr,figure'];

    #[Test]
    public function gettersReturnInitializedObjects(): void
    {
        $event = new BeforeTransformTextForPersistenceEvent(
            '<p>Some input</p>',
            "\n<p>Some input</p>\n",
            $this->procOptions
        );

        self::assertEquals('<p>Some input</p>', $event->getHtmlContent());
        self::assertEquals("\n<p>Some input</p>\n", $event->getInitialHtmlContent());
        self::assertEquals($this->procOptions, $event->getProcessingConfiguration());

        $event->setHtmlContent('Something old, something new, something borrowed and something blue.');
        self::assertEquals('Something old, something new, something borrowed and something blue.', $event->getHtmlContent());
    }
}
