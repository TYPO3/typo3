<?php

declare(strict_types = 1);

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

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RequestBuilderTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function silentlyIgnoreInvalidParameterAndUseDefaultAction(): void
    {
        $pageArguments = new PageArguments(1, '0', ['tx_blog_example_blog' => 'not_an_array']);

        $serverRequest = new ServerRequest(new Uri(''));
        $serverRequest = $serverRequest->withAttribute('routing', $pageArguments);
        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;

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

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $configurationManager = $objectManager->get(ConfigurationManagerInterface::class);
        $configurationManager->setConfiguration([
            'extensionName' => $extensionName,
            'pluginName' => $pluginName
        ]);

        $requestBuilder = $objectManager->get(RequestBuilder::class);
        $request = $requestBuilder->build();

        self::assertInstanceOf(RequestInterface::class, $request);
        self::assertSame('list', $request->getControllerActionName());
    }
}
