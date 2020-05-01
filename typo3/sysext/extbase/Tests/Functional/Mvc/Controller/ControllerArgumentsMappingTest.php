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

use ExtbaseTeam\BlogExample\Controller\BlogController;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class ControllerArgumentsMappingTest extends FunctionalTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Request
     */
    protected $request;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Response
     */
    protected $response;

    /**
     * @var BlogController
     */
    protected $controller;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['extbase', 'fluid'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(ORIGINAL_ROOT . 'typo3/sysext/extbase/Tests/Functional/Persistence/Fixtures/translatedBlogExampleData.csv');

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $configuration = [
            'persistence' => [
                'storagePid' => 20,
                'classes' => [
                    'TYPO3\CMS\Extbase\Domain\Model\Category' => [
                        'mapping' => ['tableName' => 'sys_category']
                    ]
                ]
            ]
        ];
        $configurationManager = $objectManager->get(ConfigurationManagerInterface::class);
        $configurationManager->setConfiguration($configuration);
        $this->request = $objectManager->get(Request::class);
        $this->request->setPluginName('Pi1');
        $this->request->setControllerExtensionName(BlogController::class);
        $this->request->setControllerName('Blog');
        $this->request->setMethod('GET');
        $this->request->setFormat('html');

        $this->response = $objectManager->get(Response::class);

        $this->controller = $objectManager->get(BlogController::class);
    }

    public function actionGetsBlogFromUidArgumentDataProvider()
    {
        return [
            [
                'language' => 0,
                'blogUid' => 1,
                'blogTitle' => 'Blog 1',
            ],
            [
                'language' => 1,
                'blogUid' => 1,
                'blogTitle' => 'Blog 1 DK',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider actionGetsBlogFromUidArgumentDataProvider
     */
    public function actionGetsBlogFromUidArgument(int $language, int $blogUid, string $expectedTitle)
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect($language, $language, LanguageAspect::OVERLAYS_ON));
        $this->request->setControllerActionName('details');
        $this->request->setArgument('blog', $blogUid);

        $this->controller->processRequest($this->request, $this->response);

        self::assertEquals($expectedTitle, $this->response->getContent());
    }
}
