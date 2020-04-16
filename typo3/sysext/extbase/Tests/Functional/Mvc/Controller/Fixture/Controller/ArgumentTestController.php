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

namespace TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Controller;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Domain\Model\Model;
use TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Domain\Model\ModelDto;
use TYPO3\CMS\Fluid\View\TemplateView;

/**
 * Fixture controller
 */
class ArgumentTestController extends ActionController
{
    /**
     * Action to be used in `forwardAction`.
     *
     * @var string
     */
    protected $forwardTargetAction;

    /**
     * Arguments to be used in `forwardAction`.
     *
     * @var array
     */
    protected $forwardTargetArguments;

    public function declareForwardTargetAction(string $forwardTargetAction): void
    {
        $this->forwardTargetAction = $forwardTargetAction;
    }

    public function declareForwardTargetArguments(array $forwardTargetArguments): void
    {
        $this->forwardTargetArguments = $forwardTargetArguments;
    }

    protected function setViewConfiguration(ViewInterface $view)
    {
        if ($view instanceof TemplateView) {
            // assign template path directly without forging external configuration for that...
            $view->getTemplatePaths()->setTemplateRootPaths([dirname(__DIR__) . '/Templates']);
        }
    }

    protected function addErrorFlashMessage()
    {
        // ignore flash messages
    }

    public function forwardAction(): void
    {
        $this->forward(
            $this->forwardTargetAction,
            null,
            null,
            $this->forwardTargetArguments
        );
    }

    /**
     * @param \TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Domain\Model\Model $preset
     */
    public function inputPresetModelAction(Model $preset): void
    {
        $model = new Model();
        $model->setValue($preset->getValue());
        $this->view->assignMultiple([
            'model' => $model,
        ]);
    }

    /**
     * @param \TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Domain\Model\ModelDto $preset
     */
    public function inputPresetDtoAction(ModelDto $preset): void
    {
        $dto = new ModelDto();
        $dto->setValue($preset->getValue());
        $this->view->assignMultiple([
            'dto' => $dto,
        ]);
    }

    /**
     * @param \TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Domain\Model\Model $model
     * @Extbase\Validate("TYPO3.CMS.Extbase.Tests.Functional.Mvc.Controller.Fixture:FailingValidator", param="model")
     */
    public function validateModelAction($model): void
    {
        // rendered in template `InputPresetModel.html`
    }

    /**
     * @param \TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Domain\Model\ModelDto $dto
     * @Extbase\Validate("TYPO3.CMS.Extbase.Tests.Functional.Mvc.Controller.Fixture:FailingValidator", param="dto")
     */
    public function validateDtoAction($dto): void
    {
        // rendered in template `InputPresetDto.html`
    }
}
