<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Domain\Finishers;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It originated from the Neos.Form package (www.neos.io)
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
     * @var string
     */
    protected $shortFinisherIdentifier = '';

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
     * @param string $finisherIdentifier The identifier for this finisher
     */
    public function __construct(string $finisherIdentifier = '')
    {
        if (empty($finisherIdentifier)) {
            $this->finisherIdentifier = (new \ReflectionClass($this))->getShortName();
        } else {
            $this->finisherIdentifier = $finisherIdentifier;
        }

        $this->shortFinisherIdentifier = preg_replace('/Finisher$/', '', $this->finisherIdentifier);
    }

    /**
     * @param array $options configuration options in the format ['option1' => 'value1', 'option2' => 'value2', ...]
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
     * @api
     */
    final public function execute(FinisherContext $finisherContext)
    {
        $this->finisherContext = $finisherContext;
        $this->executeInternal();
    }

    /**
     * This method is called in the concrete finisher whenever self::execute() is called.
     *
     * Override and fill with your own implementation!
     *
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

        try {
            $optionValue = ArrayUtility::getValueByPath($this->options, $optionName, '.');
        } catch (\RuntimeException $exception) {
            $optionValue = null;
        }
        try {
            $defaultValue = ArrayUtility::getValueByPath($this->defaultOptions, $optionName, '.');
        } catch (\RuntimeException $exception) {
            $defaultValue = null;
        }

        if ($optionValue === null && $defaultValue !== null) {
            $optionValue = $defaultValue;
        }

        if ($optionValue === null) {
            return null;
        }

        if (is_array($optionValue)) {
            return $optionValue;
        }

        if ($optionValue instanceof \Closure) {
            return $optionValue;
        }

        $formRuntime = $this->finisherContext->getFormRuntime();

        // You can encapsulate a option value with {}.
        // This enables you to access every getable property from the
        // TYPO3\CMS\Form\Domain\Runtime\FormRuntime.
        //
        // For example: {formState.formValues.<elemenIdentifier>}
        // or {<elemenIdentifier>}
        //
        // Both examples are equal to "$formRuntime->getFormState()->getFormValues()[<elemenIdentifier>]"
        // If the value is not a string nothing will be replaced.
        // There is a special option value '{__currentTimestamp}'.
        // This will be replaced with the current timestamp.
        $optionValue = preg_replace_callback('/{([^}]+)}/', function ($match) use ($formRuntime) {
            if ($match[1] === '__currentTimestamp') {
                $value = time();
            } else {
                // try to resolve the path '{...}' within the FormRuntime
                $value = ObjectAccess::getPropertyPath($formRuntime, $match[1]);
                if ($value === null) {
                    // try to resolve the path '{...}' within the FinisherVariableProvider
                    $value = ObjectAccess::getPropertyPath(
                        $this->finisherContext->getFinisherVariableProvider(),
                        $match[1]
                    );
                }
            }
            if (!is_string($value) && !is_int($value)) {
                $value = '{' . $match[1] . '}';
            }
            return $value;
        }, $optionValue);

        $renderingOptions = is_array($this->options['translation'])
                            ? $this->options['translation']
                            : [];

        $optionValue = TranslationService::getInstance()->translateFinisherOption(
            $formRuntime,
            $this->finisherIdentifier,
            $optionName,
            $optionValue,
            $renderingOptions
        );

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
