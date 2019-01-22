<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Finishers;

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

use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper;

/**
 * A finisher that outputs a given text
 *
 * Options:
 *
 * - message: A hard-coded message to be rendered
 *
 * Usage:
 * //...
 * $confirmationFinisher = $this->objectManager->get(ConfirmationFinisher::class);
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
    ];

    /**
     * Executes this finisher
     *
     * @see AbstractFinisher::execute()
     * @return string
     */
    protected function executeInternal()
    {
        $message = $this->parseOption('message');

        $standaloneView = $this->initializeStandaloneView(
            $this->finisherContext->getFormRuntime()
        );

        $standaloneView->assign('message', $message);

        return $standaloneView->render();
    }

    /**
     * @param FormRuntime $formRuntime
     * @return StandaloneView
     *
     * @throws FinisherException
     */
    protected function initializeStandaloneView(FormRuntime $formRuntime): StandaloneView
    {
        $standaloneView = $this->objectManager->get(StandaloneView::class);

        if (!isset($this->options['templateName'])) {
            throw new FinisherException('The option "templateName" must be set for the ConfirmationFinisher.', 1521573955);
        }

        $standaloneView->setTemplate($this->options['templateName']);
        $standaloneView->getTemplatePaths()->fillFromConfigurationArray($this->options);

        if (isset($this->options['variables']) && is_array($this->options['variables'])) {
            $standaloneView->assignMultiple($this->options['variables']);
        }

        $standaloneView->assign('form', $formRuntime);
        $standaloneView->assign('finisherVariableProvider', $this->finisherContext->getFinisherVariableProvider());

        $standaloneView->getRenderingContext()
            ->getViewHelperVariableContainer()
            ->addOrUpdate(RenderRenderableViewHelper::class, 'formRuntime', $formRuntime);

        return $standaloneView;
    }
}
