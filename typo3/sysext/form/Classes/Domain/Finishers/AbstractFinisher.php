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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Finisher base class.
 *
 * Scope: frontend
 * **This class is meant to be sub classed by developers**
 */
abstract class AbstractFinisher implements FinisherInterface
{

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var string
     */
    protected $finisherIdentifier = '';

    /**
     * The options which have been set from the outside. Instead of directly
     * accessing them, you should rather use parseOption().
     *
     * @var array
     */
    protected $options = [];

    /**
     * These are the default options of the finisher.
     * Override them in your concrete implementation.
     * Default options should not be changed from "outside"
     *
     * @var array
     */
    protected $defaultOptions = [];

    /**
     * @var \TYPO3\CMS\Form\Domain\Finishers\FinisherContext
     */
    protected $finisherContext;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     * @internal
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array $options configuration options in the format ['option1' => 'value1', 'option2' => 'value2', ...]
     * @return void
     * @api
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Sets a single finisher option (@see setOptions())
     *
     * @param string $optionName name of the option to be set
     * @param mixed $optionValue value of the option
     * @return void
     * @api
     */
    public function setOption(string $optionName, $optionValue)
    {
        $this->options[$optionName] = $optionValue;
    }

    /**
     * Executes the finisher
     *
     * @param FinisherContext $finisherContext The Finisher context that contains the current Form Runtime and Response
     * @return void
     * @api
     */
    final public function execute(FinisherContext $finisherContext)
    {
        $this->finisherIdentifier = (new \ReflectionClass($this))->getShortName();
        $this->finisherContext = $finisherContext;
        $this->executeInternal();
    }

    /**
     * This method is called in the concrete finisher whenever self::execute() is called.
     *
     * Override and fill with your own implementation!
     *
     * @return void
     * @api
     */
    abstract protected function executeInternal();

    /**
     * Read the option called $optionName from $this->options, and parse {...}
     * as object accessors.
     *
     * Then translate the value.
     *
     * If $optionName was not found, the corresponding default option is returned (from $this->defaultOptions)
     *
     * @param string $optionName
     * @return string|array|null
     * @api
     */
    protected function parseOption(string $optionName)
    {
        if ($optionName === 'translation') {
            return null;
        }

        $optionValue = ArrayUtility::getValueByPath($this->options, $optionName);
        $defaultValue = ArrayUtility::getValueByPath($this->defaultOptions, $optionName);

        if ($optionValue === null && $defaultValue !== null) {
            $optionValue = $defaultValue;
        }

        if ($optionValue === null) {
            return null;
        }

        if (is_array($optionValue)) {
            return $optionValue;
        }

        $formRuntime = $this->finisherContext->getFormRuntime();
        $optionToCompare = $optionValue;

        // You can encapsulate a option value with {}.
        // This enables you to access every getable property from the
        // TYPO3\CMS\Form\Domain\Runtime.
        //
        // For example: {formState.formValues.<elemenIdentifier>}
        // This is equal to "$formRuntime->getFormState()->getFormValues()[<elemenIdentifier>]"
        $optionValue = preg_replace_callback('/{([^}]+)}/', function ($match) use ($formRuntime) {
            return ObjectAccess::getPropertyPath($formRuntime, $match[1]);
        }, $optionValue);

        if ($optionToCompare === $optionValue) {

            // This is just a shortcut for a {formState.formValues.<elementIdentifier>} notation.
            // If one of the finisher option values is equal
            // to a identifier from the form definition then
            // the value of the submitted form element is used
            // insteed.
            // Lets say you have a textfield in your form with the
            // identifier "Text1". If you put "Text1"
            // in the email finisher option "subject" then the submited value
            // from the "Text1" element is used as the email subject.
            $formValues = $this->finisherContext->getFormValues();
            if (!is_bool($optionValue) && array_key_exists($optionValue, $formValues)) {
                $optionValue = $formRuntime[$optionValue];
            }
        }

        if (isset($this->options['translation']['translationFile'])) {
            $optionValue = TranslationService::getInstance()->translateFinisherOption(
                $formRuntime,
                $this->finisherIdentifier,
                $optionName,
                $optionValue,
                $this->options['translation']
            );
        }

        if (empty($optionValue)) {
            if ($defaultValue !== null) {
                $optionValue = $defaultValue;
            }
        }
        return $optionValue;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
