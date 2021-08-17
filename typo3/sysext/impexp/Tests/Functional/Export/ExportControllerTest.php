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

namespace TYPO3\CMS\Impexp\Tests\Functional\Export;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseFactoryInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Impexp\Controller\ExportController;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;

class ExportControllerTest extends AbstractImportExportTestCase
{
    /**
     * @var ExportController|MockObject|AccessibleObjectInterface
     */
    protected $exportControllerMock;

    protected function setUp(): void
    {
        parent::setUp();
        $container = $this->getContainer();
        $this->exportControllerMock = $this->getAccessibleMock(
            ExportController::class,
            ['dummy'],
            [
                $container->get(IconFactory::class),
                $container->get(PageRenderer::class),
                $container->get(UriBuilder::class),
                $container->get(ModuleTemplateFactory::class),
                $container->get(ResponseFactoryInterface::class)
            ]
        );
    }

    /**
     * @test
     */
    public function tableSelectOptionsContainPresetsTable(): void
    {
        $tables = $this->exportControllerMock->_call('getTableSelectOptions');
        self::assertArrayHasKey('tx_impexp_presets', $tables);
    }

    /**
     * @test
     */
    public function throwsPropagateResponseExceptionOnDownloadExportFile(): void
    {
        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withParsedBody([
                'tx_impexp' => [
                    'download_export' => 1
                ]
            ]);

        $this->expectExceptionCode(1629196918);
        $this->expectException(PropagateResponseException::class);
        $this->exportControllerMock->mainAction($request);
    }
}
