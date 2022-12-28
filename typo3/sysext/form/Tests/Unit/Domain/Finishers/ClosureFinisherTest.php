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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Finishers;

use TYPO3\CMS\Form\Domain\Finishers\ClosureFinisher;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ClosureFinisherTest extends UnitTestCase
{
    /**
     * @test
     */
    public function closureOptionForFinisherCanBeSetAndIsFunctional(): void
    {
        $closure = static function (FinisherContext $finisherContext) {
            return 'foobar';
        };

        $mockClosureFinisher = $this->getAccessibleMock(ClosureFinisher::class, null, [], '', false);
        $mockClosureFinisher->_set('options', [
            'closure' => $closure,
        ]);

        $finisherContextMock = $this->createMock(FinisherContext::class);
        $formRuntimeMock = $this->createMock(FormRuntime::class);
        $finisherContextMock->method('getFormRuntime')->willReturn($formRuntimeMock);

        $revealedFinisherContext = $finisherContextMock;

        $mockClosureFinisher->_set('finisherContext', $revealedFinisherContext);
        $closure = $mockClosureFinisher->_call('parseOption', 'closure');

        self::assertSame('foobar', $closure($revealedFinisherContext));
    }
}
