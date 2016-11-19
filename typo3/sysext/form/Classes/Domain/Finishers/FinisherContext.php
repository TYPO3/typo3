<?php
declare(strict_types=1);
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

use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
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
     * A reference to the Form Runtime that the finisher belongs to
     *
     * @var \TYPO3\CMS\Form\Domain\Runtime\FormRuntime
     */
    protected $formRuntime;

    /**
     * The assigned controller context which might be needed by the finisher.
     *
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * @param FormRuntime $formRuntime
     * @internal
     */
    public function __construct(FormRuntime $formRuntime, ControllerContext $controllerContext)
    {
        $this->formRuntime = $formRuntime;
        $this->controllerContext = $controllerContext;
    }

    /**
     * Cancels the finisher invocation after the current finisher
     *
     * @return void
     * @api
     */
    public function cancel()
    {
        $this->cancelled = true;
    }

    /**
     * TRUE if no futher finishers should be invoked. Defaults to FALSE
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
     * @api
     */
    public function getFormRuntime(): FormRuntime
    {
        return $this->formRuntime;
    }

    /**
     * The values of the submitted form (after validation and property mapping)
     *
     * @return array
     * @api
     */
    public function getFormValues(): array
    {
        return $this->formRuntime->getFormState()->getFormValues();
    }

    /**
     * @return ControllerContext
     * @api
     */
    public function getControllerContext(): ControllerContext
    {
        return $this->controllerContext;
    }
}
