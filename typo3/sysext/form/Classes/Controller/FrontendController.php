<?php
namespace TYPO3\CMS\Form\Controller;

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

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Form\Domain\Builder\FormBuilder;
use TYPO3\CMS\Form\Domain\Builder\ValidationBuilder;
use TYPO3\CMS\Form\Domain\Model\Configuration;
use TYPO3\CMS\Form\Domain\Model\ValidationElement;
use TYPO3\CMS\Form\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Form\Utility\FormUtility;

/**
 * The form wizard controller
 */
class FrontendController extends ActionController
{
    /**
     * @var FormBuilder
     */
    protected $formBuilder;

    /**
     * @var ValidationBuilder
     */
    protected $validationBuilder;

    /**
     * @var \TYPO3\CMS\Form\Utility\SessionUtility
     */
    protected $sessionUtility;

    /**
     * @var FormUtility
     */
    protected $formUtility;

    /**
     * The TypoScript array
     *
     * @var array
     */
    protected $typoscript = [];

    /**
     * TRUE if the validation of the form should be skipped
     *
     * @var bool
     */
    protected $skipValidation = false;

    /**
     * @var ControllerContext
     */
    protected $controllerContext;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @param \TYPO3\CMS\Form\Utility\SessionUtility $sessionUtility
     * @return void
     */
    public function injectSessionUtility(\TYPO3\CMS\Form\Utility\SessionUtility $sessionUtility)
    {
        $this->sessionUtility = $sessionUtility;
    }

    /**
     * initialize action
     *
     * @return void
     */
    protected function initializeAction()
    {
        $this->configuration = Configuration::create()->setTypoScript($this->settings['typoscript']);
        $this->formUtility = FormUtility::create($this->configuration);
        $this->validationBuilder = ValidationBuilder::create($this->configuration);
        $this->validationBuilder->setFormUtility($this->formUtility);
        $this->formBuilder = FormBuilder::create($this->configuration);
        $this->formBuilder->setValidationBuilder($this->validationBuilder);
        $this->formBuilder->setFormUtility($this->formUtility);
        $this->typoscript = $this->settings['typoscript'];

            // uploaded file storage
        $this->sessionUtility->initSession($this->configuration->getPrefix());
            // move the incoming "formPrefix" data to the $model argument
            // now we can validate the $model argument
        if ($this->request->hasArgument($this->configuration->getPrefix())) {
            $this->skipValidation = false;
            $argument = $this->request->getArgument($this->configuration->getPrefix());
            $this->request->setArgument('model', $argument);
        } else {
            // If there are more forms at a page we have to skip
            // the validation of not submitted forms
            $this->skipValidation = true;
            $this->request->setArgument('model', []);
        }
    }

    /**
     * initialize show action
     *
     * @return void
     */
    protected function initializeShowAction()
    {
        $validationResults = $this->request->getOriginalRequestMappingResults()->forProperty('model');
        $this->validationBuilder->buildRules();
        if ($validationResults->hasErrors()) {
            $this->formBuilder->setValidationErrors($validationResults);
        }
    }

    /**
     * initialize the confirmation action
     *
     * @return void
     */
    protected function initializeConfirmationAction()
    {
        $this->prepareValidations();
    }

    /**
     * initialize the process action
     *
     * @return void
     */
    protected function initializeProcessAction()
    {
        $this->prepareValidations();
    }

    /**
     * Builds the controller context by extending
     * the Extbase context with custom additions.
     *
     * @return ControllerContext
     */
    protected function buildControllerContext()
    {
        $controllerContext = ControllerContext::extend(parent::buildControllerContext())
            ->setConfiguration($this->configuration);
        $this->formBuilder->setControllerContext($controllerContext);
        return $controllerContext;
    }

    /**
     * Handles show action, presenting the actual form.
     *
     * @param \TYPO3\CMS\Form\Domain\Model\ValidationElement $incomingData
     * @ignorevalidation $incomingData
     * @return void
     */
    public function showAction(ValidationElement $incomingData = null)
    {
        if ($incomingData !== null) {
            $this->controllerContext->setValidationElement($incomingData);
        }
        $form = $this->formBuilder->buildModel();
        $this->view->assign('model', $form);
    }

    /**
     * Handles confirmation action, presenting the user submitted
     * data again for final confirmation.
     *
     * @param \TYPO3\CMS\Form\Domain\Model\ValidationElement $model
     * @return void
     */
    public function confirmationAction(ValidationElement $model)
    {
        $this->skipForeignFormProcessing();

        if (count($model->getIncomingFields()) === 0) {
            $this->sessionUtility->destroySession();
            $this->forward('show');
        }
        $this->controllerContext->setValidationElement($model);
        $form = $this->formBuilder->buildModel();
        // store uploaded files
        $this->sessionUtility->storeSession();
        $this->view->assign('model', $form);

        $message = $this->formUtility->renderItem(
            $this->typoscript['confirmation.']['message.'],
            $this->typoscript['confirmation.']['message'],
            LocalizationUtility::translate('tx_form_view_confirmation.message', 'form')
        );
        $this->view->assign('message', $message);
    }

    /**
     * action dispatchConfirmationButtonClick
     *
     * @param \TYPO3\CMS\Form\Domain\Model\ValidationElement $model
     * @return void
     */
    public function dispatchConfirmationButtonClickAction(ValidationElement $model)
    {
        $this->skipForeignFormProcessing();

        if ($this->request->hasArgument('confirmation-true')) {
            $this->forward('process', null, null, [$this->configuration->getPrefix() => $this->request->getArgument('model')]);
        } else {
            $this->sessionUtility->destroySession();
            $this->forward('show', null, null, ['incomingData' => $this->request->getArgument('model')]);
        }
    }

    /**
     * Handles process action, actually processing the user
     * submitted data and forwarding it to post-processors
     * (e.g. sending out mail messages).
     *
     * @param \TYPO3\CMS\Form\Domain\Model\ValidationElement $model
     * @return void
     */
    public function processAction(ValidationElement $model)
    {
        $this->skipForeignFormProcessing();

        $this->controllerContext->setValidationElement($model);
        $form = $this->formBuilder->buildModel();
        $postProcessorTypoScript = [];
        if (isset($this->typoscript['postProcessor.'])) {
            $postProcessorTypoScript = $this->typoscript['postProcessor.'];
        }

        /** @var $postProcessor \TYPO3\CMS\Form\PostProcess\PostProcessor */
        $postProcessor = $this->objectManager->get(
            \TYPO3\CMS\Form\PostProcess\PostProcessor::class,
            $form, $postProcessorTypoScript
        );
        $postProcessor->setControllerContext($this->controllerContext);

        // @todo What is happening here?
        $content = $postProcessor->process();
        $this->sessionUtility->destroySession();
        $this->forward('afterProcess', null, null, ['postProcessorContent' => $content]);
    }

    /**
     * action after process
     *
     * @param string $postProcessorContent
     * @return void
     */
    public function afterProcessAction($postProcessorContent)
    {
        $this->view->assign('postProcessorContent', $postProcessorContent);
    }

    /**
     * Skip the processing of foreign forms.
     * If there is more than one form on a page
     * we have to be sure that only the submitted form will be
     * processed. On data submission, the extbase action "confirmation" or
     * "process" is called. The detection which form is submitted
     * is done by the form prefix. All forms which do not have any
     * submitted data are skipped and forwarded to the show action.
     *
     * @return void
     */
    protected function skipForeignFormProcessing()
    {
        if (
            !$this->request->hasArgument($this->configuration->getPrefix())
            && !$this->sessionUtility->getSessionData()
        ) {
            $this->forward('show');
        }
    }

    /**
     * If the current form should be validated
     * then set the dynamic validation
     *
     * @return void
     */
    protected function prepareValidations()
    {
        if ($this->skipValidation || !$this->arguments->hasArgument('model')) {
            return;
        }

        $this->validationBuilder->buildRules($this->request->getArgument('model'));
        $this->setDynamicValidation($this->validationBuilder->getRules());
        $this->skipValidation = false;
    }

    /**
     * Sets the dynamic validation rules.
     *
     * @param array $toValidate
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     * @throws \TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException
     */
    protected function setDynamicValidation(array $toValidate = [])
    {
        // build custom validation chain
        /** @var \TYPO3\CMS\Extbase\Validation\ValidatorResolver $validatorResolver */
        $validatorResolver = $this->objectManager->get(\TYPO3\CMS\Extbase\Validation\ValidatorResolver::class);

        /** @var \TYPO3\CMS\Form\Domain\Validator\ValidationElementValidator $modelValidator */
        $modelValidator = $validatorResolver->createValidator(\TYPO3\CMS\Form\Domain\Validator\ValidationElementValidator::class);
        foreach ($toValidate as $propertyName => $validations) {
            foreach ($validations as $validation) {
                if (empty($validation['validator'])) {
                    throw new \TYPO3\CMS\Extbase\Validation\Exception\NoSuchValidatorException('Invalid validate configuration for ' . $propertyName . ': Could not resolve class name for validator "' . $validation['validatorName'] . '".', 1441893777);
                }
                $modelValidator->addPropertyValidator($propertyName, $validation['validator']);
            }
        }

        if ($modelValidator->countPropertyValidators()) {
            /** @var \TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator $baseConjunctionValidator */
            $baseConjunctionValidator = $this->arguments->getArgument('model')->getValidator();
            if ($baseConjunctionValidator === null) {
                $baseConjunctionValidator = $validatorResolver->createValidator(\TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator::class);
                $this->arguments->getArgument('model')->setValidator($baseConjunctionValidator);
            }
            $baseConjunctionValidator->addValidator($modelValidator);
        }
    }
}
