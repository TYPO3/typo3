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

namespace TYPO3\CMS\Form\Domain\Finishers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * A finisher that outputs a given text
 *
 * Options:
 *
 * - message: A hard-coded message to be rendered
 * - contentElementUid: A content element uid to be rendered
 *
 * Usage:
 * //...
 * $confirmationFinisher = GeneralUtility::makeInstance(ConfirmationFinisher::class);
 * $confirmationFinisher->setOptions(
 *   [
 *     'message' => 'foo',
 *   ]
 * );
 * $formDefinition->addFinisher($confirmationFinisher);
 * // ...
 *
 * Scope: frontend
 */
class ConfirmationFinisher extends AbstractFinisher
{
    /**
     * @var array
     */
    protected $defaultOptions = [
        'message' => 'The form has been submitted.',
        'contentElementUid' => 0,
        'typoscriptObjectPath' => 'lib.tx_form.contentElementRendering',
    ];

    public function __construct(
        private readonly ExtbaseConfigurationManagerInterface $extbaseConfigurationManager,
        private readonly ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * @throws FinisherException
     */
    protected function executeInternal(): string
    {
        $options = $this->options;
        if (!isset($options['templateName']) || !is_string($options['templateName'])) {
            throw new FinisherException(
                'The option "templateName" must be set for the ConfirmationFinisher.',
                1521573955
            );
        }

        $contentElementUid = $this->parseOption('contentElementUid');
        $typoscriptObjectPath = $this->parseOption('typoscriptObjectPath');
        $typoscriptObjectPath = is_string($typoscriptObjectPath) ? $typoscriptObjectPath : '';
        if (!empty($contentElementUid)) {
            $pathSegments = GeneralUtility::trimExplode('.', $typoscriptObjectPath);
            $lastSegment = array_pop($pathSegments);
            $setup = $this->extbaseConfigurationManager->getConfiguration(ExtbaseConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
            foreach ($pathSegments as $segment) {
                if (!array_key_exists($segment . '.', $setup)) {
                    throw new FinisherException(
                        sprintf('TypoScript object path "%s" does not exist', $typoscriptObjectPath),
                        1489238980
                    );
                }
                $setup = $setup[$segment . '.'];
            }
            $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $contentObjectRenderer->setRequest($this->finisherContext->getRequest()->withoutAttribute('extbase'));
            $contentObjectRenderer->start([$contentElementUid]);
            $contentObjectRenderer->setCurrentVal((string)$contentElementUid);
            $message = $contentObjectRenderer->cObjGetSingle($setup[$lastSegment], $setup[$lastSegment . '.'], $lastSegment);
        } else {
            $message = $this->parseOption('message');
        }

        $formRuntime = $this->finisherContext->getFormRuntime();
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: is_array($options['templateRootPaths'] ?? false) ? $options['templateRootPaths'] : [],
            partialRootPaths: is_array($options['partialRootPaths'] ?? false) ? $options['partialRootPaths'] : [],
            layoutRootPaths: is_array($options['layoutRootPaths'] ?? false) ? $options['layoutRootPaths'] : [],
            request: $this->finisherContext->getRequest(),
        );
        $view = $this->viewFactory->create($viewFactoryData);
        if ($view instanceof FluidViewAdapter) {
            $view->getRenderingContext()->getViewHelperVariableContainer()
                ->addOrUpdate(RenderRenderableViewHelper::class, 'formRuntime', $formRuntime);
        }
        if (isset($this->options['variables']) && is_array($this->options['variables'])) {
            $view->assignMultiple($this->options['variables']);
        }
        $view->assignMultiple([
            'form' => $formRuntime,
            'finisherVariableProvider' => $this->finisherContext->getFinisherVariableProvider(),
            'message' => $message,
            'isPreparedMessage' => !empty($contentElementUid),
        ]);
        return $view->render($options['templateName']);
    }
}
