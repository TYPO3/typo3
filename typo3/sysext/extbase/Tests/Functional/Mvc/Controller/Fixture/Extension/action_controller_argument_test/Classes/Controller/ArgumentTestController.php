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

namespace ExtbaseTeam\ActionControllerArgumentTest\Controller;

use ExtbaseTeam\ActionControllerArgumentTest\Domain\Model\Model;
use ExtbaseTeam\ActionControllerArgumentTest\Domain\Model\ModelDto;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

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

    protected function addErrorFlashMessage()
    {
        // ignore flash messages
    }

    public function forwardAction(): ResponseInterface
    {
        return (new ForwardResponse($this->forwardTargetAction))->withArguments($this->forwardTargetArguments);
    }

    /**
     * @param \ExtbaseTeam\ActionControllerArgumentTest\Domain\Model\Model $preset
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
     * @param \ExtbaseTeam\ActionControllerArgumentTest\Domain\Model\ModelDto $preset
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
     * @param \ExtbaseTeam\ActionControllerArgumentTest\Domain\Model\Model $model
     * @Extbase\Validate("ExtbaseTeam.ActionControllerArgumentTest.Domain:FailingValidator", param="model")
     */
    public function validateModelAction($model): void
    {
        // rendered in template `InputPresetModel.html`
    }

    /**
     * @param \ExtbaseTeam\ActionControllerArgumentTest\Domain\Model\ModelDto $dto
     * @Extbase\Validate("ExtbaseTeam.ActionControllerArgumentTest.Domain:FailingValidator", param="dto")
     */
    public function validateDtoAction($dto): void
    {
        // rendered in template `InputPresetDto.html`
    }
}
