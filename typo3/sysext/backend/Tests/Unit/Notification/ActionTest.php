<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Notification;

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

use TYPO3\CMS\Backend\Exception\UnknownTypeException;
use TYPO3\CMS\Backend\Notification\Action;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ActionTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function actionDataProvider()
    {
        return [
            Action::TYPE_DEFERRED => [Action::TYPE_DEFERRED, false],
            Action::TYPE_IMMEDIATE => [Action::TYPE_IMMEDIATE, false],
            'foo' => ['foo', true],
        ];
    }

    /**
     * @test
     * @dataProvider actionDataProvider
     * @param string $type
     * @param bool $expectException
     */
    public function notificationJavaScriptCodeWillBeCreated(string $type, bool $expectException)
    {
        if ($expectException) {
            $this->expectException(UnknownTypeException::class);
            $this->expectExceptionCode(1567493886);
        }
        new Action('test', '', $type);
    }
}
