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

namespace TYPO3\CMS\Extbase\Tests\Functional\Mvc\Web;

use ExtbaseTeam\BlogExample\Controller\BlogController;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\ExtbaseModule;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Exception;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;
use TYPO3\TestingFramework\Core\Functional\Framework\FrameworkState;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RequestBuilderTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Some tests need GeneralUtility::getIndpEnv('SCRIPT_NAME') to return correct value instead of
        // 'vendor/phpunit/phpunit/phpunit', used eg. in TypoScriptFrontendController. To manipulate/set it
        // before creating request from globals, the global must be set. Thus the framework state has to be
        // saved/reset and later restored. Similar requirement is needed when emitting frontend requests with
        // the testing-framework. This is done globally for the testcase, so reset can be cleanly done even if
        // a test has failed.
        // @see FunctionalTestCase::retrieveFrontendSubRequestResult()
        // @todo: Get rid of getIndpEnv var usage in TSFE
        FrameworkState::push();
        FrameworkState::reset();
    }

    protected function tearDown(): void
    {
        // Restore previously saved framework state.
        // This is to clean-up manipulated framework state for testing
        // purposes even on failures, thus done globally for the testcase.
        // @see FunctionalTestCase::retrieveFrontendSubRequestResult()
        // @todo: Get rid of getIndpEnv var usage in TSFE
        FrameworkState::pop();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function buildBuildsARequestInterfaceObject(): void
    {
        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => ['list'],
            ],
        ]);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withAttribute('module', $module);
        $requestBuilder = $this->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('html', $request->getFormat());
    }

    /**
     * @test
     */
    public function loadDefaultValuesOverridesFormatIfConfigured(): void
    {
        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => ['list'],
            ],
        ]);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;
        $configuration['format'] = 'json';

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withAttribute('module', $module);
        $requestBuilder = $this->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('json', $request->getFormat());
    }

    /**
     * @test
     */
    public function buildOverridesFormatIfSetInGetParameters(): void
    {
        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => ['list'],
            ],
        ]);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withQueryParams(['format' => 'json']);
        $mainRequest = $mainRequest->withAttribute('module', $module);
        $requestBuilder = $this->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('json', $request->getFormat());
    }

    /**
     * @test
     */
    public function loadDefaultValuesThrowsExceptionIfExtensionNameIsNotProperlyConfigured(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1289843275);
        $this->expectExceptionMessage('"extensionName" is not properly configured. Request can\'t be dispatched!');

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $requestBuilder = $this->get(RequestBuilder::class);
        $requestBuilder->build($mainRequest);
    }

    /**
     * @test
     */
    public function loadDefaultValuesThrowsExceptionIfPluginNameIsNotProperlyConfigured(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1289843277);
        $this->expectExceptionMessage('"pluginName" is not properly configured. Request can\'t be dispatched!');

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration(['extensionName' => 'blog_example']);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $requestBuilder = $this->get(RequestBuilder::class);
        $requestBuilder->build($mainRequest);
    }

    /**
     * @test
     */
    public function untangleFilesArrayDetectsASingleUploadedFileInBackend(): void
    {
        $_FILES['dummy'] = [
            'name' => 'name.pdf',
            'type' => 'application/pdf',
            'tmp_name' => '/tmp/php/php1h4j1o',
            'error' => UPLOAD_ERR_OK,
            'size' => 98174,
        ];

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => ['list'],
            ],
        ]);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'https://example.com/';
        // Needed for GeneralUtility::getIndpEnv('SCRIPT_NAME') to return correct value instead of
        // 'vendor/phpunit/phpunit/phpunit', used eg. in TypoScriptFrontendController absRefPrefix='auto
        // and other places.
        // @see FunctionalTestCase::retrieveFrontendSubRequestResult()
        // @todo: Get rid of getIndpEnv var usage in TSFE
        $_SERVER['SCRIPT_NAME'] = '/typo3/index.php';

        $mainRequest = ServerRequestFactory::fromGlobals()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $normalizedParams = NormalizedParams::createFromRequest($mainRequest);
        $mainRequest = $mainRequest->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('module', $module);
        $requestBuilder = $this->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('name.pdf', $request->getArgument('name'));
        self::assertSame('application/pdf', $request->getArgument('type'));
        self::assertSame('/tmp/php/php1h4j1o', $request->getArgument('tmp_name'));
        self::assertSame(UPLOAD_ERR_OK, $request->getArgument('error'));
        self::assertSame(98174, $request->getArgument('size'));
    }

    /**
     * @test
     */
    public function untangleFilesArrayDetectsMultipleUploadedFileInBackend(): void
    {
        $_FILES['dummy'] = [
            'error' => [
                'pdf' => UPLOAD_ERR_OK,
                'jpg' => UPLOAD_ERR_OK,
            ],
            'name' => [
                'pdf' => 'name.pdf',
                'jpg' => 'name.jpg',
            ],
            'type' => [
                'pdf' => 'application/pdf',
                'jpg' => 'image/jpg',
            ],
            'tmp_name' => [
                'pdf' => '/tmp/php/php1h4j1o',
                'jpg' => '/tmp/php/php6hst32',
            ],
            'size' => [
                'pdf' => 98174,
            ],
        ];

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => ['list'],
            ],
        ]);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = 'https://example.com/';
        // Needed for GeneralUtility::getIndpEnv('SCRIPT_NAME') to return correct value instead of
        // 'vendor/phpunit/phpunit/phpunit', used eg. in TypoScriptFrontendController absRefPrefix='auto
        // and other places.
        // @see FunctionalTestCase::retrieveFrontendSubRequestResult()
        // @todo: Get rid of getIndpEnv var usage in TSFE
        $_SERVER['SCRIPT_NAME'] = '/typo3/index.php';

        $mainRequest = ServerRequestFactory::fromGlobals()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $normalizedParams = NormalizedParams::createFromRequest($mainRequest);
        $mainRequest = $mainRequest->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('module', $module);
        $requestBuilder = $this->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);

        $argument = $request->getArgument('pdf');
        self::assertIsArray($argument);
        self::assertSame('name.pdf', $argument['name']);
        self::assertSame('application/pdf', $argument['type']);
        self::assertSame('/tmp/php/php1h4j1o', $argument['tmp_name']);
        self::assertSame(UPLOAD_ERR_OK, $argument['error']);
        self::assertSame(98174, $argument['size']);

        $argument = $request->getArgument('jpg');
        self::assertIsArray($argument);
        self::assertSame('name.jpg', $argument['name']);
        self::assertSame('image/jpg', $argument['type']);
        self::assertSame('/tmp/php/php6hst32', $argument['tmp_name']);
        self::assertSame(UPLOAD_ERR_OK, $argument['error']);
        self::assertNotTrue(isset($argument['size']));
    }

    /**
     * @test
     */
    public function resolveControllerClassNameThrowsInvalidControllerNameExceptionIfNonExistentControllerIsSetViaGetParameter(): void
    {
        $this->expectException(InvalidControllerNameException::class);
        $this->expectExceptionCode(1313855173);
        $this->expectExceptionMessage('The controller "NonExistentController" is not allowed by plugin "blog". Please check for TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin() in your ext_localconf.php.');

        $_GET['tx_blog_example_blog']['controller'] = 'NonExistentController';

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => ['list'],
            ],
        ]);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withAttribute('module', $module);
        $mainRequest = $mainRequest->withQueryParams(['controller' => 'NonExistentController']);
        $requestBuilder = $this->get(RequestBuilder::class);
        $requestBuilder->build($mainRequest);
    }

    /**
     * @test
     */
    public function resolveControllerClassNameThrowsPageNotFoundException(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->expectExceptionCode(1313857897);
        $this->expectExceptionMessage('The requested resource was not found');

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => ['list'],
            ],
        ]);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;
        $configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved'] = true;

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withQueryParams(['controller' => 'NonExistentController']);
        $mainRequest = $mainRequest->withAttribute('module', $module);
        $requestBuilder = $this->get(RequestBuilder::class);
        $requestBuilder->build($mainRequest);
    }

    /**
     * @test
     */
    public function resolveControllerClassNameThrowsAnExceptionIfTheDefaultControllerCannotBeDetermined(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1316104317);
        $this->expectExceptionMessage('The default controller for extension "blog_example" and plugin "blog" can not be determined. Please check for TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin() in your ext_localconf.php.');

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $requestBuilder = $this->get(RequestBuilder::class);
        $requestBuilder->build($mainRequest);
    }

    /**
     * @test
     */
    public function resolveControllerClassNameReturnsDefaultControllerIfCallDefaultActionIfActionCantBeResolvedIsConfigured(): void
    {
        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => ['list'],
            ],
        ]);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;
        $configuration['mvc']['callDefaultActionIfActionCantBeResolved'] = true;

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withAttribute('module', $module);
        $mainRequest = $mainRequest->withQueryParams(['controller' => 'NonExistentController']);
        $requestBuilder = $this->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('Blog', $request->getControllerName());
    }

    /**
     * @test
     */
    public function resolveControllerClassNameReturnsControllerDefinedViaParametersIfControllerIsConfigured(): void
    {
        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => ['list'],
                'ExtbaseTeam\BlogExample\Controller\UserController' => ['list'],
            ],
        ]);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withAttribute('module', $module);
        $mainRequest = $mainRequest->withQueryParams(['controller' => 'User']);
        $requestBuilder = $this->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('User', $request->getControllerName());
    }

    /**
     * @test
     */
    public function resolveActionNameThrowsInvalidActionNameExceptionIfNonExistentActionIsSetViaGetParameter(): void
    {
        $this->expectException(InvalidActionNameException::class);
        $this->expectExceptionCode(1313855175);
        $this->expectExceptionMessage('The action "NonExistentAction" (controller "ExtbaseTeam\BlogExample\Controller\BlogController") is not allowed by this plugin / module. Please check TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin() in your ext_localconf.php / TYPO3\CMS\Extbase\Utility\ExtensionUtility::configureModule() in your ext_tables.php.');

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => ['list'],
            ],
        ]);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withAttribute('module', $module);
        $mainRequest = $mainRequest->withQueryParams(['action' => 'NonExistentAction']);
        $requestBuilder = $this->get(RequestBuilder::class);
        $requestBuilder->build($mainRequest);
    }

    /**
     * @test
     */
    public function resolveActionNameThrowsPageNotFoundException(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->expectExceptionCode(1313857898);
        $this->expectExceptionMessage('The requested resource was not found');

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => ['list'],
            ],
        ]);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;
        $configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved'] = true;

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withAttribute('module', $module);
        $mainRequest = $mainRequest->withQueryParams(['action' => 'NonExistentAction']);
        $requestBuilder = $this->get(RequestBuilder::class);
        $requestBuilder->build($mainRequest);
    }

    /**
     * @test
     */
    public function resolveActionNameReturnsDefaultActionIfCallDefaultActionIfActionCantBeResolvedIsConfigured(): void
    {
        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => ['list'],
            ],
        ]);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;
        $configuration['mvc']['callDefaultActionIfActionCantBeResolved'] = true;

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withAttribute('module', $module);
        $mainRequest = $mainRequest->withQueryParams(['tx_blog_example_blog' => ['action' => 'NonExistentAction']]);
        $requestBuilder = $this->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('list', $request->getControllerActionName());
    }

    /**
     * @test
     */
    public function resolveActionNameReturnsActionDefinedViaParametersIfActionIsConfigured(): void
    {
        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => ['list', 'show'],
            ],
        ]);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withAttribute('module', $module);
        $mainRequest = $mainRequest->withQueryParams(['action' => 'show']);
        $requestBuilder = $this->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('show', $request->getControllerActionName());
    }

    /**
     * @test
     */
    public function resolveActionNameThrowsAnExceptionIfTheDefaultActionCannotBeDetermined(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1295479651);
        $this->expectExceptionMessage('The default action can not be determined for controller "ExtbaseTeam\BlogExample\Controller\BlogController". Please check TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin() in your ext_localconf.php.');

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => '',
            ],
        ]);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withAttribute('module', $module);
        $requestBuilder = $this->get(RequestBuilder::class);
        $requestBuilder->build($mainRequest);
    }

    /**
     * @test
     */
    public function resolveActionNameReturnsActionDefinedViaParametersOfServerRequest(): void
    {
        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest
            ->withQueryParams(['action' => 'show'])
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => ['list', 'show'],
            ],
        ]);
        $mainRequest = $mainRequest->withAttribute('module', $module);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $requestBuilder = $this->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('show', $request->getControllerActionName());
    }

    /**
     * @test
     */
    public function resolveActionNameReturnsActionDefinedViaPageArgumentOfServerRequest(): void
    {
        $pageArguments = new PageArguments(1, '0', ['tx_blog_example_blog' => ['action' => 'show']]);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest
            ->withAttribute('routing', $pageArguments)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $GLOBALS['TYPO3_REQUEST'] = $mainRequest;

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'] = [
            BlogController::class => [
                'className' => BlogController::class,
                'alias' => 'blog',
                'actions' => ['list', 'show'],
            ],
        ];

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $requestBuilder = $this->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('show', $request->getControllerActionName());
    }

    /**
     * @test
     */
    public function resolveActionNameReturnsActionDefinedViaParsedBodyOfServerRequest(): void
    {
        $mainRequest = $this->prepareServerRequest('https://example.com/', 'POST');
        $mainRequest = $mainRequest
            ->withParsedBody(['action' => 'show'])
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => ['list', 'show'],
            ],
        ]);
        $mainRequest = $mainRequest->withAttribute('module', $module);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $requestBuilder = $this->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('show', $request->getControllerActionName());
    }

    /**
     * @test
     */
    public function silentlyIgnoreInvalidParameterAndUseDefaultAction(): void
    {
        $pageArguments = new PageArguments(1, '0', ['tx_blog_example_blog' => 'not_an_array']);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest
            ->withParsedBody(['tx_blog_example_blog' => ['action' => 'show']])
            ->withAttribute('routing', $pageArguments)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $module = ExtbaseModule::createFromConfiguration($pluginName, [
            'packageName' => 'typo3/cms-blog-example',
            'path' => '/blog-example',
            'extensionName' => $extensionName,
            'controllerActions' => [
                BlogController::class => ['list', 'show'],
            ],
        ]);
        $mainRequest = $mainRequest->withAttribute('module', $module);

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;
        $configurationManager = $this->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $requestBuilder = $this->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('list', $request->getControllerActionName());
    }

    protected function prepareServerRequest(string $url, $method = 'GET'): ServerRequestInterface
    {
        $request = (new ServerRequest($url, $method))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $normalizedParams = NormalizedParams::createFromRequest($request);
        return $request->withAttribute('normalizedParams', $normalizedParams);
    }
}
