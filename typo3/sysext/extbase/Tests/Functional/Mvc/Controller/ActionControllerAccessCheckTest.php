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

namespace TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\ActionControllerTest\Controller\AccessCheckController;

final class ActionControllerAccessCheckTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/action_controller_test',
    ];

    #[Test]
    public function accessDeniedExceptionIsThrownWhenLoginIsRequired(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        self::expectException(PropagateResponseException::class);
        self::expectExceptionCode(1761287264);

        $extbaseRequestParameters = new ExtbaseRequestParameters();

        $serverRequest = (new ServerRequest('https://example.com/', 'POST'))
            ->withAttribute('extbase', $extbaseRequestParameters);
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('AccessCheckController')
            ->withControllerActionName('feUserRequired')
            ->withPluginName('Pi1')
            ->withArgument('accessCheckArgument', 1);

        $subject = $this->get(AccessCheckController::class);
        $subject->processRequest($request);
    }

    #[Test]
    public function actionIsCalledWhenLoginIsRequiredAndUserLoggedIn(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        $extbaseRequestParameters = new ExtbaseRequestParameters();

        $serverRequest = (new ServerRequest('https://example.com/', 'POST'))
            ->withAttribute('extbase', $extbaseRequestParameters);
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('AccessCheckController')
            ->withControllerActionName('feUserRequired')
            ->withPluginName('Pi1')
            ->withArgument('accessCheckArgument', 1);

        $frontendUser = new FrontendUserAuthentication();
        $frontendUser->user = [
            'uid' => 1,
        ];
        $frontendUser->userGroups = [
            1 => ['title' => 'editor'],
        ];
        $frontendUserAspect = new UserAspect($frontendUser);

        $context = $this->get(Context::class);
        $context->setAspect('frontend.user', $frontendUserAspect);

        $subject = $this->get(AccessCheckController::class);
        $response = $subject->processRequest($request);
        self::assertEquals('Success', (string)$response->getBody());
    }

    #[Test]
    public function accessDeniedExceptionIsThrownWhenGroupUidIsRequired(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        self::expectException(PropagateResponseException::class);
        self::expectExceptionCode(1761287264);

        $extbaseRequestParameters = new ExtbaseRequestParameters();

        $serverRequest = (new ServerRequest('https://example.com/', 'POST'))
            ->withAttribute('extbase', $extbaseRequestParameters);
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('AccessCheckController')
            ->withControllerActionName('feGroupUidRequired')
            ->withPluginName('Pi1')
            ->withArgument('accessCheckArgument', 1);

        $subject = $this->get(AccessCheckController::class);
        $subject->processRequest($request);
    }

    #[Test]
    public function actionIsCalledWhenGroupUidIsRequiredAndGroupUidIsAllowed(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        $extbaseRequestParameters = new ExtbaseRequestParameters();

        $serverRequest = (new ServerRequest('https://example.com/', 'POST'))
            ->withAttribute('extbase', $extbaseRequestParameters);
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('AccessCheckController')
            ->withControllerActionName('feGroupUidRequired')
            ->withPluginName('Pi1')
            ->withArgument('accessCheckArgument', 1);

        $frontendUser = new FrontendUserAuthentication();
        $frontendUser->user = [
            'uid' => 1,
        ];
        $frontendUser->userGroups = [
            1 => ['title' => 'editor'],
        ];
        $frontendUserAspect = new UserAspect($frontendUser);

        $context = $this->get(Context::class);
        $context->setAspect('frontend.user', $frontendUserAspect);

        $subject = $this->get(AccessCheckController::class);
        $response = $subject->processRequest($request);
        self::assertEquals('Success', (string)$response->getBody());
    }

    #[Test]
    public function accessDeniedExceptionIsThrownWhenGroupUidIsRequiredAndGroupUidIsNotAllowed(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        self::expectException(PropagateResponseException::class);
        self::expectExceptionCode(1761287264);

        $extbaseRequestParameters = new ExtbaseRequestParameters();

        $serverRequest = (new ServerRequest('https://example.com/', 'POST'))
            ->withAttribute('extbase', $extbaseRequestParameters);
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('AccessCheckController')
            ->withControllerActionName('feGroupNameRequired')
            ->withPluginName('Pi1')
            ->withArgument('accessCheckArgument', 1);

        $frontendUser = new FrontendUserAuthentication();
        $frontendUser->user = [
            'uid' => 1,
        ];
        $frontendUser->userGroups = [
            2 => ['title' => 'editor'],
        ];
        $frontendUserAspect = new UserAspect($frontendUser);

        $context = $this->get(Context::class);
        $context->setAspect('frontend.user', $frontendUserAspect);

        $subject = $this->get(AccessCheckController::class);
        $subject->processRequest($request);
    }

    #[Test]
    public function actionIsCalledWhenGroupNameIsRequiredAndGroupNameIsAllowed(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        $extbaseRequestParameters = new ExtbaseRequestParameters();

        $serverRequest = (new ServerRequest('https://example.com/', 'POST'))
            ->withAttribute('extbase', $extbaseRequestParameters);
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('AccessCheckController')
            ->withControllerActionName('feGroupNameRequired')
            ->withPluginName('Pi1')
            ->withArgument('accessCheckArgument', 1);

        $frontendUser = new FrontendUserAuthentication();
        $frontendUser->user = [
            'uid' => 1,
        ];
        $frontendUser->userGroups = [
            1 => ['title' => 'admin'],
        ];
        $frontendUserAspect = new UserAspect($frontendUser);

        $context = $this->get(Context::class);
        $context->setAspect('frontend.user', $frontendUserAspect);

        $subject = $this->get(AccessCheckController::class);
        $response = $subject->processRequest($request);
        self::assertEquals('Success', (string)$response->getBody());
    }

    #[Test]
    public function actionIsCalledWhenGroupNameOrUidIsRequiredAndGroupNameIsAllowed(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        $extbaseRequestParameters = new ExtbaseRequestParameters();

        $serverRequest = (new ServerRequest('https://example.com/', 'POST'))
            ->withAttribute('extbase', $extbaseRequestParameters);
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('AccessCheckController')
            ->withControllerActionName('oneOfMultipleFeGroupsRequired')
            ->withPluginName('Pi1')
            ->withArgument('accessCheckArgument', 1);

        $frontendUser = new FrontendUserAuthentication();
        $frontendUser->user = [
            'uid' => 1,
        ];
        $frontendUser->userGroups = [
            2 => ['title' => 'admin'],
        ];
        $frontendUserAspect = new UserAspect($frontendUser);

        $context = $this->get(Context::class);
        $context->setAspect('frontend.user', $frontendUserAspect);

        $subject = $this->get(AccessCheckController::class);
        $response = $subject->processRequest($request);
        self::assertEquals('Success', (string)$response->getBody());
    }

    #[Test]
    public function accessDeniedExceptionIsThrownWhenSimpleCallbackDeniesAccess(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        self::expectException(PropagateResponseException::class);
        self::expectExceptionCode(1761287264);

        $extbaseRequestParameters = new ExtbaseRequestParameters();

        $serverRequest = (new ServerRequest('https://example.com/', 'POST'))
            ->withAttribute('extbase', $extbaseRequestParameters);
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('AccessCheckController')
            ->withControllerActionName('authorizationWithSimpleCallback')
            ->withPluginName('Pi1')
            ->withArgument('accessCheckArgument', 0);

        $subject = $this->get(AccessCheckController::class);
        $subject->processRequest($request);
    }

    #[Test]
    public function actionIsCalledWhenSimpleCallbackAllowsAccess(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        $extbaseRequestParameters = new ExtbaseRequestParameters();

        $serverRequest = (new ServerRequest('https://example.com/', 'POST'))
            ->withAttribute('extbase', $extbaseRequestParameters);
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('AccessCheckController')
            ->withControllerActionName('authorizationWithSimpleCallback')
            ->withPluginName('Pi1')
            ->withArgument('accessCheckArgument', 1);

        $subject = $this->get(AccessCheckController::class);
        $response = $subject->processRequest($request);
        self::assertEquals('Success', (string)$response->getBody());
    }

    #[Test]
    public function accessDeniedExceptionIsThrownWhenCustomAccessServiceCallbackDeniesAccess(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        self::expectException(PropagateResponseException::class);
        self::expectExceptionCode(1761287264);

        $extbaseRequestParameters = new ExtbaseRequestParameters();

        $serverRequest = (new ServerRequest('https://example.com/', 'POST'))
            ->withAttribute('extbase', $extbaseRequestParameters);
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('AccessCheckController')
            ->withControllerActionName('authorizationWithCustomServiceClassCallback')
            ->withPluginName('Pi1')
            ->withArgument('accessCheckArgument', 0);

        $subject = $this->get(AccessCheckController::class);
        $subject->processRequest($request);
    }

    #[Test]
    public function actionIsCalledWhenCustomAccessServiceCallbackAllowsAccess(): void
    {
        // Init ConfigurationManagerInterface stateful singleton, usually done by extbase bootstrap
        $this->get(ConfigurationManagerInterface::class)->setRequest(
            (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
        );

        $extbaseRequestParameters = new ExtbaseRequestParameters();

        $serverRequest = (new ServerRequest('https://example.com/', 'POST'))
            ->withAttribute('extbase', $extbaseRequestParameters);
        $request = (new Request($serverRequest))
            ->withControllerExtensionName('ActionControllerTest')
            ->withControllerName('AccessCheckController')
            ->withControllerActionName('authorizationWithCustomServiceClassCallback')
            ->withPluginName('Pi1')
            ->withArgument('accessCheckArgument', 1);

        $subject = $this->get(AccessCheckController::class);
        $response = $subject->processRequest($request);
        self::assertEquals('Success', (string)$response->getBody());
    }
}
