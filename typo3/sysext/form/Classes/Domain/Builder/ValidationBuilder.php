<?php
namespace TYPO3\CMS\Form\Domain\Builder;

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

use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Form\Domain\Model\Configuration;
use TYPO3\CMS\Form\Domain\Validator\AbstractValidator;
use TYPO3\CMS\Form\Utility\FormUtility;

/**
 * Parse and hole all the validation rules
 */
class ValidationBuilder
{
    /**
     * @param Configuration $configuration
     * @return ValidationBuilder
     */
    public static function create(Configuration $configuration)
    {
        /** @var ValidationBuilder $validationBuilder */
        $validationBuilder = \TYPO3\CMS\Form\Utility\FormUtility::getObjectManager()->get(self::class);
        $validationBuilder->setConfiguration($configuration);
        return $validationBuilder;
    }

    /**
     * @var array|array[]
     */
    protected $rules = [];

    /**
     * @var string
     */
    protected $formPrefix = '';

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Form\Domain\Repository\TypoScriptRepository
     */
    protected $typoScriptRepository;

    /**
     * @var FormUtility
     */
    protected $formUtility;

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     * @return void
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Form\Domain\Repository\TypoScriptRepository $typoScriptRepository
     * @return void
     */
    public function injectTypoScriptRepository(\TYPO3\CMS\Form\Domain\Repository\TypoScriptRepository $typoScriptRepository)
    {
        $this->typoScriptRepository = $typoScriptRepository;
    }

    /**
     * @param Configuration $configuration
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param FormUtility $formUtility
     */
    public function setFormUtility(FormUtility $formUtility)
    {
        $this->formUtility = $formUtility;
    }

    /**
     * Build validation rules from typoscript.
     * The old breakOnError property are no longer supported
     *
     * @param array $rawArgument
     * @return void
     */
    public function buildRules(array $rawArgument = [])
    {
        $userConfiguredFormTyposcript = $this->configuration->getTypoScript();
        $rulesTyposcript = isset($userConfiguredFormTyposcript['rules.']) ? $userConfiguredFormTyposcript['rules.'] : null;
        $this->rules[$this->configuration->getPrefix()] = [];
        if (is_array($rulesTyposcript)) {
            $keys = TemplateService::sortedKeyList($rulesTyposcript);
            foreach ($keys as $key) {
                $ruleName = $rulesTyposcript[$key];
                $validatorClassName = $this->typoScriptRepository->getRegisteredClassName($ruleName, 'registeredValidators');
                if ($validatorClassName === null) {
                    throw new \RuntimeException('Class "' . $validatorClassName . '" not registered via typoscript.');
                }
                if (
                    (int)$key
                    && strpos($key, '.') === false
                ) {
                    $ruleArguments = $rulesTyposcript[$key . '.'];
                    $fieldName = $this->formUtility->sanitizeNameAttribute($ruleArguments['element']);
                        // remove unsupported validator options
                    $validatorOptions = $ruleArguments;
                    $validatorOptions['errorMessage'] = [$ruleArguments['error.'], $ruleArguments['error']];
                    $keysToRemove = array_flip([
                        'breakOnError',
                        'message',
                        'message.',
                        'error',
                        'error.',
                        'showMessage',
                    ]);
                    $validatorOptions = array_diff_key($validatorOptions, $keysToRemove);

                    // Instantiate the validator to check if all required options are assigned
                    // and to use the validator message rendering function to pre-render the mandatory message
                    /** @var AbstractValidator $validator */
                    $validator = $this->objectManager->get($validatorClassName, $validatorOptions);

                    if ($validator instanceof AbstractValidator) {
                        $validator->setRawArgument($rawArgument);
                        $validator->setFormUtility($this->formUtility);

                        if ((int)$ruleArguments['showMessage'] === 1) {
                            $mandatoryMessage = $validator->renderMessage($ruleArguments['message.'], $ruleArguments['message']);
                        } else {
                            $mandatoryMessage = null;
                        }

                        $this->rules[$this->configuration->getPrefix()][$fieldName][] = [
                            'validator' => $validator,
                            'validatorName' => $validatorClassName,
                            'validatorOptions' => $validatorOptions,
                            'mandatoryMessage' => $mandatoryMessage
                        ];
                    } else {
                        throw new \RuntimeException('Class "' . $validatorClassName . '" could not be loaded.');
                    }
                }
            }
        }
    }

    /**
     * Set all validation rules
     *
     * @param array $rules
     * @return void
     */
    public function setRules(array $rules)
    {
        $this->rules = $rules[$this->configuration->getPrefix()];
    }

    /**
     * Get all validation rules
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules[$this->configuration->getPrefix()];
    }

    /**
     * Set a validation rule
     *
     * @param string $key
     * @param array $rule
     * @return void
     */
    public function setRulesByElementName($key = '', array $rule = [])
    {
        $this->rules[$this->configuration->getPrefix()][$key] = $rule;
    }

    /**
     * Get a validation rule by key
     *
     * @param string $key
     * @return NULL|array
     */
    public function getRulesByElementName($key = '')
    {
        if (isset($this->rules[$this->configuration->getPrefix()][$key])) {
            return $this->rules[$this->configuration->getPrefix()][$key];
        }
        return null;
    }

    /**
     * Remove a validation rule by key
     *
     * @param string $key
     * @return void
     */
    public function removeRule($key = '')
    {
        unset($this->rules[$this->configuration->getPrefix()][$key]);
    }

    /**
     * Get all mandatory validation messages for a element
     *
     * @param string $key
     * @return array
     */
    public function getMandatoryValidationMessagesByElementName($key = '')
    {
        $mandatoryMessages = [];
        if ($this->getRulesByElementName($key)) {
            $rules = $this->getRulesByElementName($key);
            foreach ($rules as $rule) {
                if ($rule['mandatoryMessage']) {
                    $mandatoryMessages[] = $rule['mandatoryMessage'];
                }
            }
        }
        return $mandatoryMessages;
    }
}
