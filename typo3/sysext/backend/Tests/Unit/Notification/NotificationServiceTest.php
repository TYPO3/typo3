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

use Prophecy\Argument;
use TYPO3\CMS\Backend\Notification\Action;
use TYPO3\CMS\Backend\Notification\NotificationService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class NotificationServiceTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * @return array
     */
    public function notificationDataProvider()
    {
        return [
            'notice' => [
                'notice',
                'Notice',
                'This is a notice',
                'Notification.notice(\'Notice\', \'This\u0020is\u0020a\u0020notice\', 5, [])'
            ],
            'info' => [
                'info',
                'Info',
                'This is an info',
                'Notification.info(\'Info\', \'This\u0020is\u0020an\u0020info\', 5, [])'
            ],
            'success' => [
                'success',
                'Success',
                'This is a success message',
                'Notification.success(\'Success\', \'This\u0020is\u0020a\u0020success\u0020message\', 5, [])'
            ],
            'warning' => [
                'warning',
                'Warning',
                'This is a warning',
                'Notification.warning(\'Warning\', \'This\u0020is\u0020a\u0020warning\', 5, [])'
            ],
            'error' => [
                'error',
                'Error',
                'This is an error',
                'Notification.error(\'Error\', \'This\u0020is\u0020an\u0020error\', 0, [])'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider notificationDataProvider
     * @param string $type
     * @param string $title
     * @param string $message
     * @param string $notificationCode
     */
    public function notificationJavaScriptCodeWillBeCreated(string $type, string $title, string $message, string $notificationCode)
    {
        $pageRenderer = $this->prophesize(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Notification', Argument::that(function (string $callback) use ($type, $title, $message, $notificationCode) {
            $this->assertStringContainsString($type, $callback);
            $this->assertStringContainsString($title, $callback);
            $this->assertStringContainsString(GeneralUtility::quoteJSvalue($message), $callback);
            $this->assertStringContainsString($notificationCode, $callback);
            return true;
        }))->shouldBeCalledOnce();
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRenderer->reveal());

        GeneralUtility::makeInstance(NotificationService::class)
            ->$type($title, $message);
    }

    /**
     * @test
     * @dataProvider notificationDataProvider
     * @param string $type
     * @param string $title
     * @param string $message
     * @param string $notificationCode
     */
    public function notificationJavaScriptCodeWillBeCreatedWithCustomDuration(string $type, string $title, string $message, string $notificationCode)
    {
        $pageRenderer = $this->prophesize(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Notification', Argument::that(function (string $callback) use ($type, $title, $message, $notificationCode) {
            $this->assertStringContainsString($type, $callback);
            $this->assertStringContainsString($title, $callback);
            $this->assertStringContainsString(GeneralUtility::quoteJSvalue($message), $callback);
            $this->assertStringContainsString(', 123,', $callback);
            return true;
        }))->shouldBeCalledOnce();
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRenderer->reveal());

        GeneralUtility::makeInstance(NotificationService::class)
            ->$type($title, $message, 123);
    }

    /**
     * @test
     * @dataProvider notificationDataProvider
     * @param string $type
     * @param string $title
     * @param string $message
     * @param string $notificationCode
     */
    public function notificationJavaScriptCodeWillBeCreatedWithCustomDurationAndImmediateAction(string $type, string $title, string $message, string $notificationCode)
    {
        $pageRenderer = $this->prophesize(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Notification', Argument::that(function (string $callback) use ($type, $title, $message, $notificationCode) {
            $this->assertStringContainsString($type, $callback);
            $this->assertStringContainsString($title, $callback);
            $this->assertStringContainsString(GeneralUtility::quoteJSvalue($message), $callback);
            $this->assertStringContainsString(', 123,', $callback);
            $this->assertStringContainsString(Action::TYPE_IMMEDIATE, $callback);
            return true;
        }))->shouldBeCalledOnce();
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRenderer->reveal());

        GeneralUtility::makeInstance(NotificationService::class)
            ->$type($title, $message, 123, [new Action('test', 'foo()')]);
    }

    /**
     * @test
     * @dataProvider notificationDataProvider
     * @param string $type
     * @param string $title
     * @param string $message
     * @param string $notificationCode
     */
    public function notificationJavaScriptCodeWillBeCreatedWithCustomDurationAndDeferredAction(string $type, string $title, string $message, string $notificationCode)
    {
        $pageRenderer = $this->prophesize(PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Notification', Argument::that(function (string $callback) use ($type, $title, $message, $notificationCode) {
            $this->assertStringContainsString($type, $callback);
            $this->assertStringContainsString($title, $callback);
            $this->assertStringContainsString(GeneralUtility::quoteJSvalue($message), $callback);
            $this->assertStringContainsString(', 123,', $callback);
            $this->assertStringContainsString(Action::TYPE_DEFERRED, $callback);
            return true;
        }))->shouldBeCalledOnce();
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRenderer->reveal());

        GeneralUtility::makeInstance(NotificationService::class)
            ->$type($title, $message, 123, [new Action('test', 'foo()', Action::TYPE_DEFERRED)]);
    }
}
