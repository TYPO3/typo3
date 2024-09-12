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

namespace TYPO3\CMS\Backend\Preview;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException;

/**
 * Check if a Fluid-based preview template was defined for a given
 * CType and render it via Fluid. Also works for list_type / plugins.
 *
 * Currently two ways are supported while the generic record preview
 * allows auto-discovery and out-of-the-box usage of partials and
 * layouts. The "old" method allows to define dedicated templates
 * for each CType / list_type.
 *
 * Example configuration for auto-discovery:
 * record.preview.tt_content.paths.10 = EXT:site_mysite/Resources/Private/Templates/Preview/Content/
 *
 * Example configuration for legacy template rendering:
 * mod.web_layout.tt_content.preview.textmedia = EXT:site_mysite/Resources/Private/Templates/Preview/Textmedia.html
 *
 * @internal not part of the TYPO3 Core API
 */
final readonly class FluidBasedRecordPreviewRenderer
{
    public function __construct(
        private FlexFormService $flexFormService,
        private RecordFactory $recordFactory,
        private LoggerInterface $logger,
        private ViewFactoryInterface $viewFactory,
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    #[AsEventListener('typo3-backend/fluid-preview/content')]
    public function renderPageContentPreview(PageContentPreviewRenderingEvent $event): void
    {
        $record = $this->recordFactory->createResolvedRecordFromDatabaseRow($event->getTable(), $event->getRecord());
        $request = $event->getPageLayoutContext()->getCurrentRequest();
        $tsConfig =  BackendUtility::getPagesTSconfig($record->getPid());
        $recordType = $this->resolveRecordType($record);

        $previewContent = $this->renderRecordPreviewFromPaths($record, $recordType, $request, $tsConfig);
        if ($previewContent === null) {
            $previewContent = $this->renderContentElementPreviewFromFluidTemplate($record, $recordType, $request, $tsConfig);
        }
        if ($previewContent !== null) {
            $event->setPreviewContent($previewContent);
        }
    }

    private function renderRecordPreviewFromPaths(RecordInterface $record, string $recordType, ServerRequestInterface $request, array $tsConfig): ?string
    {
        $paths = $tsConfig['record.']['preview.'][$record->getMainType() . '.']['paths.'] ?? [];
        if ($paths === []) {
            return null;
        }
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: $paths,
            partialRootPaths: array_map(static fn(string $path): string => $path . 'Partials/', $paths),
            layoutRootPaths: array_map(static fn(string $path): string => $path . 'Layouts/', $paths),
            request: $request
        );
        // Transform record type to a template name using "underscoredToUpperCamelCase". Example: FeloginLogin.html
        // Subtypes are separated by "/" making the record type to be the directory name. Example: List/TxBlogPi1.html
        $templateName = implode(
            '/',
            array_map(static fn(string $recordType) => GeneralUtility::underscoredToUpperCamelCase($recordType), GeneralUtility::trimExplode('.', $recordType, true, 2))
        ) . '.html';
        try {
            return $this->viewFactory
                ->create($viewFactoryData)
                ->assign('record', $record)
                ->render($templateName);
        } catch (InvalidTemplateResourceException) {
            // It might be that paths are defined but no template exists for this record type. This is actually no error and we therefore exit here
            return null;
        } catch (\Exception $e) {
            // An error occurred while rendering the template
            $this->logger->warning('The backend preview for record {uid} can not be rendered using the Fluid template file "{file}" in template paths {paths}', [
                'uid' => $record->getUid(),
                'file' => $templateName,
                'paths' => implode(',', $paths),
                'exception' => $e,
            ]);
            if ($this->getBackendUser()->shallDisplayDebugInformation()) {
                $viewFactoryData = new ViewFactoryData(
                    templatePathAndFilename: 'EXT:backend/Resources/Private/Templates/PageLayout/FluidBasedContentPreviewRenderingException.html',
                    request: $request
                );
                $view = $this->viewFactory->create($viewFactoryData);
                $view->assign('error', [
                    'message' => str_replace(Environment::getProjectPath(), '', $e->getMessage()),
                    'title' => 'Error while rendering FluidTemplate preview using ' . str_replace(Environment::getProjectPath(), '', $templateName) . ' in template paths ' . implode(',', $paths),
                ]);
                return $view->render();
            }
        }
        return null;
    }

    private function renderContentElementPreviewFromFluidTemplate(RecordInterface $record, string $recordType, ServerRequestInterface $request, array $tsConfig): ?string
    {
        $previewConfig = $tsConfig['mod.']['web_layout.'][$record->getMainType() . '.']['preview.'] ?? [];
        if ($previewConfig === []) {
            return null;
        }
        $recordType = str_replace('list.', '', $recordType);
        if ($record->getFullType() === 'tt_content.list' && !empty($previewConfig['list.'][$recordType])) {
            $fluidTemplateFile = $previewConfig['list.'][$recordType];
        } elseif (!empty($previewConfig[$recordType])) {
            $fluidTemplateFile = $previewConfig[$recordType];
        } else {
            return null;
        }
        $fluidTemplateFileAbsolutePath = GeneralUtility::getFileAbsFileName($fluidTemplateFile);
        if ($fluidTemplateFileAbsolutePath === '') {
            return null;
        }
        try {
            $row = $record->getRawRecord()?->toArray() ?? [];
            $viewFactoryData = new ViewFactoryData(
                templatePathAndFilename: $fluidTemplateFileAbsolutePath,
                request: $request
            );
            $view = $this->viewFactory->create($viewFactoryData);
            $view->assignMultiple($row);
            if ($record->getMainType() === 'tt_content' && !empty($row['pi_flexform'])) {
                $view->assign('pi_flexform_transformed', $this->flexFormService->convertFlexFormContentToArray($row['pi_flexform']));
            }
            // @todo Should we make sure that "record" is actually a Record object?
            $view->assign('record', $record);
            return $view->render();
        } catch (\Exception $e) {
            $this->logger->warning('The backend preview for content element {uid} can not be rendered using the Fluid template file "{file}"', [
                'uid' => $record->getUid(),
                'file' => $fluidTemplateFileAbsolutePath,
                'exception' => $e,
            ]);
            if ($this->getBackendUser()->shallDisplayDebugInformation()) {
                $viewFactoryData = new ViewFactoryData(
                    templatePathAndFilename: 'EXT:backend/Resources/Private/Templates/PageLayout/FluidBasedContentPreviewRenderingException.html'
                );
                $view = $this->viewFactory->create($viewFactoryData);
                $view->assign('error', [
                    'message' => str_replace(Environment::getProjectPath(), '', $e->getMessage()),
                    'title' => 'Error while rendering FluidTemplate preview using ' . str_replace(Environment::getProjectPath(), '', $fluidTemplateFileAbsolutePath),
                ]);
                return $view->render();
            }
        }
        return null;
    }

    /**
     * @todo Support of "subtypes" will most likely be deprecated in upcoming versions
     */
    private function resolveRecordType(RecordInterface $record): string
    {
        $recordType = $record->getRecordType();
        if (!$this->tcaSchemaFactory->has($record->getFullType())) {
            // There might be elements with a record type in the database for which corresponding configuration
            // does no longer exist (e.g. a extension which added a specific content type has been removed).
            return $recordType;
        }
        $schema = $this->tcaSchemaFactory->get($record->getFullType());
        if ($schema->getSubTypeDivisorField() !== null // record type supports "subtypes"
            && $record->has($schema->getSubTypeDivisorField()->getName()) // record has the subtype field
            && ($subtype = ($record->get($schema->getSubTypeDivisorField()->getName()))) // a subtype is set for the record
            && isset($schema->getSubSchemata()[$subtype]) // defined subtype is valid for the record type
        ) {
            $recordType .= '.' . $subtype;
        }
        return $recordType;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
