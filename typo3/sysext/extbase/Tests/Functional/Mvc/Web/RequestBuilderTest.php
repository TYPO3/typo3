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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Exception;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidActionNameException;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerNameException;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RequestBuilderTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function buildBuildsARequestInterfaceObject()
    {
        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers']['ExtbaseTeam\BlogExample\Controller\BlogController'] = [
            'className' => 'ExtbaseTeam\BlogExample\Controller\BlogController',
            'alias' => 'BlogController',
            'actions' => [
                'list'
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('html', $request->getFormat());
    }

    /**
     * @test
     */
    public function loadDefaultValuesOverridesFormatIfConfigured()
    {
        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers']['ExtbaseTeam\BlogExample\Controller\BlogController'] = [
            'className' => 'ExtbaseTeam\BlogExample\Controller\BlogController',
            'alias' => 'BlogController',
            'actions' => [
                'list'
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;
        $configuration['format'] = 'json';

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('json', $request->getFormat());
    }

    /**
     * @test
     */
    public function buildOverridesFormatIfSetInGetParameters()
    {
        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers']['ExtbaseTeam\BlogExample\Controller\BlogController'] = [
            'className' => 'ExtbaseTeam\BlogExample\Controller\BlogController',
            'alias' => 'BlogController',
            'actions' => [
                'list'
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withQueryParams(['tx_blog_example_blog' => ['format' => 'json']]);
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('json', $request->getFormat());
    }

    /**
     * @test
     */
    public function loadDefaultValuesThrowsExceptionIfExtensionNameIsNotProperlyConfigured()
    {
        static::expectException(Exception::class);
        static::expectExceptionCode(1289843275);
        static::expectExceptionMessage('"extensionName" is not properly configured. Request can\'t be dispatched!');

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $requestBuilder->build($mainRequest);
    }

    /**
     * @test
     */
    public function loadDefaultValuesThrowsExceptionIfPluginNameIsNotProperlyConfigured()
    {
        static::expectException(Exception::class);
        static::expectExceptionCode(1289843277);
        static::expectExceptionMessage('"pluginName" is not properly configured. Request can\'t be dispatched!');

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration(['extensionName' => 'blog_example']);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $requestBuilder->build($mainRequest);
    }

    /**
     * @test
     */
    public function untangleFilesArrayDetectsASingleUploadedFile()
    {
        $_FILES['tx_blog_example_blog'] = [
            'name' => 'name.pdf',
            'type' => 'application/pdf',
            'tmp_name' => '/tmp/php/php1h4j1o',
            'error' => UPLOAD_ERR_OK,
            'size' => 98174,
        ];

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers']['ExtbaseTeam\BlogExample\Controller\BlogController'] = [
            'className' => 'ExtbaseTeam\BlogExample\Controller\BlogController',
            'alias' => 'BlogController',
            'actions' => [
                'list'
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/', 'POST');
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
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
    public function untangleFilesArrayDetectsMultipleUploadedFile()
    {
        $_FILES['tx_blog_example_blog'] = [
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

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers']['ExtbaseTeam\BlogExample\Controller\BlogController'] = [
            'className' => 'ExtbaseTeam\BlogExample\Controller\BlogController',
            'alias' => 'BlogController',
            'actions' => [
                'list'
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/', 'POST');
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
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
        self::assertTrue(!isset($argument['size']));
    }

    /**
     * @test
     */
    public function resolveControllerClassNameThrowsInvalidControllerNameExceptionIfNonExistentControllerIsSetViaGetParameter()
    {
        static::expectException(InvalidControllerNameException::class);
        static::expectExceptionCode(1313855173);
        static::expectExceptionMessage('The controller "NonExistentController" is not allowed by plugin "blog". Please check for TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin() in your ext_localconf.php.');

        $_GET['tx_blog_example_blog']['controller'] = 'NonExistentController';

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers']['ExtbaseTeam\BlogExample\Controller\BlogController'] = [
            'className' => 'ExtbaseTeam\BlogExample\Controller\BlogController',
            'alias' => 'BlogController',
            'actions' => [
                'list'
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withQueryParams(['tx_blog_example_blog' => ['controller' => 'NonExistentController']]);
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $requestBuilder->build($mainRequest);
    }

    /**
     * @test
     */
    public function resolveControllerClassNameThrowsPageNotFoundException()
    {
        static::expectException(PageNotFoundException::class);
        static::expectExceptionCode(1313857897);
        static::expectExceptionMessage('The requested resource was not found');

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers']['ExtbaseTeam\BlogExample\Controller\BlogController'] = [
            'className' => 'ExtbaseTeam\BlogExample\Controller\BlogController',
            'alias' => 'BlogController',
            'actions' => [
                'list'
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;
        $configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved'] = true;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withQueryParams(['tx_blog_example_blog' => ['controller' => 'NonExistentController']]);
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $requestBuilder->build($mainRequest);
    }

    /**
     * @test
     */
    public function resolveControllerClassNameThrowsAnExceptionIfTheDefaultControllerCannotBeDetermined()
    {
        static::expectException(Exception::class);
        static::expectExceptionCode(1316104317);
        static::expectExceptionMessage('The default controller for extension "blog_example" and plugin "blog" can not be determined. Please check for TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin() in your ext_localconf.php.');

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $requestBuilder->build($mainRequest);
    }

    /**
     * @test
     */
    public function resolveControllerClassNameReturnsDefaultControllerIfCallDefaultActionIfActionCantBeResolvedIsConfigured()
    {
        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers']['ExtbaseTeam\BlogExample\Controller\BlogController'] = [
            'className' => 'ExtbaseTeam\BlogExample\Controller\BlogController',
            'alias' => 'BlogController',
            'actions' => [
                'list'
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;
        $configuration['mvc']['callDefaultActionIfActionCantBeResolved'] = true;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withQueryParams(['tx_blog_example_blog' => ['controller' => 'NonExistentController']]);
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('BlogController', $request->getControllerName());
    }

    /**
     * @test
     */
    public function resolveControllerClassNameReturnsControllerDefinedViaParametersIfControllerIsConfigured()
    {
        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers'] = [
            'ExtbaseTeam\BlogExample\Controller\BlogController' => [
                'className' =>  'ExtbaseTeam\BlogExample\Controller\BlogController',
                'alias' => 'BlogController',
                'actions' => [
                    'list'
                ]
            ],
            'ExtbaseTeam\BlogExample\Controller\UserController' => [
                'className' =>  'ExtbaseTeam\BlogExample\Controller\UserController',
                'alias' => 'UserController',
                'actions' => [
                    'list'
                ]
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withQueryParams(['tx_blog_example_blog' => ['controller' => 'UserController']]);
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('UserController', $request->getControllerName());
    }

    /**
     * @test
     */
    public function resolveActionNameThrowsInvalidActionNameExceptionIfNonExistentActionIsSetViaGetParameter()
    {
        static::expectException(InvalidActionNameException::class);
        static::expectExceptionCode(1313855175);
        static::expectExceptionMessage('The action "NonExistentAction" (controller "ExtbaseTeam\BlogExample\Controller\BlogController") is not allowed by this plugin / module. Please check TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin() in your ext_localconf.php / TYPO3\CMS\Extbase\Utility\ExtensionUtility::configureModule() in your ext_tables.php.');

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers']['ExtbaseTeam\BlogExample\Controller\BlogController'] = [
            'className' => 'ExtbaseTeam\BlogExample\Controller\BlogController',
            'alias' => 'BlogController',
            'actions' => [
                'list'
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withQueryParams(['tx_blog_example_blog' => ['action' => 'NonExistentAction']]);
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $requestBuilder->build($mainRequest);
    }

    /**
     * @test
     */
    public function resolveActionNameThrowsPageNotFoundException()
    {
        static::expectException(PageNotFoundException::class);
        static::expectExceptionCode(1313857898);
        static::expectExceptionMessage('The requested resource was not found');

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers']['ExtbaseTeam\BlogExample\Controller\BlogController'] = [
            'className' => 'ExtbaseTeam\BlogExample\Controller\BlogController',
            'alias' => 'BlogController',
            'actions' => [
                'list'
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;
        $configuration['mvc']['throwPageNotFoundExceptionIfActionCantBeResolved'] = true;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withQueryParams(['tx_blog_example_blog' => ['action' => 'NonExistentAction']]);
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $requestBuilder->build($mainRequest);
    }

    /**
     * @test
     */
    public function resolveActionNameReturnsDefaultActionIfCallDefaultActionIfActionCantBeResolvedIsConfigured()
    {
        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers']['ExtbaseTeam\BlogExample\Controller\BlogController'] = [
            'className' => 'ExtbaseTeam\BlogExample\Controller\BlogController',
            'alias' => 'BlogController',
            'actions' => [
                'list'
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;
        $configuration['mvc']['callDefaultActionIfActionCantBeResolved'] = true;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withQueryParams(['tx_blog_example_blog' => ['action' => 'NonExistentAction']]);
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('list', $request->getControllerActionName());
    }

    /**
     * @test
     */
    public function resolveActionNameReturnsActionDefinedViaParametersIfActionIsConfigured()
    {
        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers'] = [
            'ExtbaseTeam\BlogExample\Controller\BlogController' => [
                'className' =>  'ExtbaseTeam\BlogExample\Controller\BlogController',
                'alias' => 'BlogController',
                'actions' => [
                    'list', 'show'
                ]
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest->withQueryParams(['tx_blog_example_blog' => ['action' => 'show']]);
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('show', $request->getControllerActionName());
    }

    /**
     * @test
     */
    public function resolveActionNameThrowsAnExceptionIfTheDefaultActionCannotBeDetermined()
    {
        static::expectException(Exception::class);
        static::expectExceptionCode(1295479651);
        static::expectExceptionMessage('The default action can not be determined for controller "ExtbaseTeam\BlogExample\Controller\BlogController". Please check TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin() in your ext_localconf.php.');

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers'] = [
            'ExtbaseTeam\BlogExample\Controller\BlogController' => [
                'className' =>  'ExtbaseTeam\BlogExample\Controller\BlogController',
                'alias' => 'BlogController'
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $requestBuilder->build($mainRequest);
    }

    /**
     * @test
     */
    public function resolveActionNameReturnsActionDefinedViaParametersOfServerRequest()
    {
        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest
            ->withQueryParams(['tx_blog_example_blog' => ['action' => 'show']])
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers'] = [
            'ExtbaseTeam\BlogExample\Controller\BlogController' => [
                'className' =>  'ExtbaseTeam\BlogExample\Controller\BlogController',
                'alias' => 'BlogController',
                'actions' => [
                    'list', 'show'
                ]
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('show', $request->getControllerActionName());
    }

    /**
     * @test
     */
    public function resolveActionNameReturnsActionDefinedViaPageArgumentOfServerRequest()
    {
        $pageArguments = new PageArguments(1, '0', ['tx_blog_example_blog' => ['action' => 'show']]);

        $mainRequest = $this->prepareServerRequest('https://example.com/');
        $mainRequest = $mainRequest
            ->withAttribute('routing', $pageArguments)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers'] = [
            'ExtbaseTeam\BlogExample\Controller\BlogController' => [
                'className' =>  'ExtbaseTeam\BlogExample\Controller\BlogController',
                'alias' => 'BlogController',
                'actions' => [
                    'list', 'show'
                ]
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('show', $request->getControllerActionName());
    }

    /**
     * @test
     */
    public function resolveActionNameReturnsActionDefinedViaParsedBodyOfServerRequest()
    {
        $mainRequest = $this->prepareServerRequest('https://example.com/', 'POST');
        $mainRequest = $mainRequest
            ->withParsedBody(['tx_blog_example_blog' => ['action' => 'show']])
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);

        $extensionName = 'blog_example';
        $pluginName = 'blog';

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers'] = [
            'ExtbaseTeam\BlogExample\Controller\BlogController' => [
                'className' =>  'ExtbaseTeam\BlogExample\Controller\BlogController',
                'alias' => 'BlogController',
                'actions' => [
                    'list', 'show'
                ]
            ]
        ];

        $configuration = [];
        $configuration['extensionName'] = $extensionName;
        $configuration['pluginName'] = $pluginName;

        $configurationManager = $this->getContainer()->get(ConfigurationManager::class);
        $configurationManager->setConfiguration($configuration);

        $requestBuilder = $this->getContainer()->get(RequestBuilder::class);
        $request = $requestBuilder->build($mainRequest);

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('show', $request->getControllerActionName());
    }

    protected function prepareServerRequest(string $url, $method = 'GET'): ServerRequestInterface
    {
        $request = new ServerRequest($url, $method);
        $normalizedParams = NormalizedParams::createFromRequest($request);
        return $request->withAttribute('normalizedParams', $normalizedParams);
    }
}
