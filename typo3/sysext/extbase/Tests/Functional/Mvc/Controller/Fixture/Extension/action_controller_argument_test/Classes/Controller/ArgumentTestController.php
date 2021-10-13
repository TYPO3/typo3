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

    protected function addErrorFlashMessage(): void
    {
        // ignore flash messages
    }

    public function forwardAction(): ResponseInterface
    {
        return (new ForwardResponse($this->forwardTargetAction))->withArguments($this->forwardTargetArguments);
    }

    public function inputPresetModelAction(Model $preset): ResponseInterface
    {
        $model = new Model();
        $model->setValue($preset->getValue());
        $this->view->assignMultiple([
            'model' => $model,
        ]);
        return $this->htmlResponse($this->view->render());
    }

    public function inputPresetDtoAction(ModelDto $preset): ResponseInterface
    {
        $dto = new ModelDto();
        $dto->setValue($preset->getValue());
        $this->view->assignMultiple([
            'dto' => $dto,
        ]);
        return $this->htmlResponse($this->view->render());
    }

    /**
     * @Extbase\Validate("ExtbaseTeam.ActionControllerArgumentTest.Domain:FailingValidator", param="model")
     */
    public function validateModelAction(Model $model): ResponseInterface
    {
        $this->view->assignMultiple([
            'model' => $model,
        ]);
        return $this->htmlResponse($this->view->render());
    }

    /**
     * @Extbase\Validate("ExtbaseTeam.ActionControllerArgumentTest.Domain:FailingValidator", param="dto")
     */
    public function validateDtoAction(ModelDto $dto): ResponseInterface
    {
        $this->view->assignMultiple([
            'dto' => $dto,
        ]);
        return $this->htmlResponse($this->view->render());
    }
}
