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

/*
 * Inspired by and partially taken from the Neos.Form package (www.neos.io)
 */

namespace TYPO3\CMS\Form\Domain\Finishers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * The context that is passed to each finisher when executed.
 * It acts like an EventObject that is able to stop propagation.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 * @internal
 */
class FinisherContext
{

    /**
     * If TRUE further finishers won't be invoked
     *
     * @var bool
     */
    protected $cancelled = false;

    /**
     * A reference to the Form Runtime the finisher belongs to
     */
    protected FormRuntime $formRuntime;

    /**
     * The assigned controller context which might be needed by the finisher.
     * @deprecated since v11, will be removed with v12.
     */
    protected ControllerContext $controllerContext;

    /**
     * The assigned controller context which might be needed by the finisher.
     */
    protected FinisherVariableProvider $finisherVariableProvider;

    private Request $request;

    /**
     * @param FormRuntime $formRuntime
     * @param ControllerContext $controllerContext @deprecated since v11, will be removed with v12.
     * @param Request $request
     * @internal
     */
    public function __construct(FormRuntime $formRuntime, ControllerContext $controllerContext, Request $request)
    {
        $this->formRuntime = $formRuntime;
        // @deprecated since v11, will be removed with v12.
        $this->controllerContext = $controllerContext;
        $this->request = $request;
        $this->finisherVariableProvider = GeneralUtility::makeInstance(FinisherVariableProvider::class);
    }

    /**
     * Cancels the finisher invocation after the current finisher
     */
    public function cancel()
    {
        $this->cancelled = true;
    }

    /**
     * TRUE if no further finishers should be invoked. Defaults to FALSE
     *
     * @return bool
     * @internal
     */
    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    /**
     * The Form Runtime that is associated with the current finisher
     *
     * @return FormRuntime
     */
    public function getFormRuntime(): FormRuntime
    {
        return $this->formRuntime;
    }

    /**
     * The values of the submitted form (after validation and property mapping)
     *
     * @return array
     */
    public function getFormValues(): array
    {
        return $this->formRuntime->getFormState()->getFormValues();
    }

    /**
     * @return ControllerContext
     * @deprecated since v11, will be removed in v12
     */
    public function getControllerContext(): ControllerContext
    {
        return $this->controllerContext;
    }

    /**
     * @return FinisherVariableProvider
     */
    public function getFinisherVariableProvider(): FinisherVariableProvider
    {
        return $this->finisherVariableProvider;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
