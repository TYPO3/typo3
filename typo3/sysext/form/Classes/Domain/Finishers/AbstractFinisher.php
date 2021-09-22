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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Model\FormElements\StringableFormElementInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
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
     * @deprecated since v11, will be removed in v12. Drop together with inject method and ObjectManager removal.
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
     * @deprecated since v11, will be removed in v12. Drop together with property and ObjectManager removal.
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @deprecated since v11, will be removed in v12. Drop together with ObjectManager cleanup.
     */
    public function __construct()
    {
        // no-op so parent::construct() calls don't fail in v11.
    }

    /**
     * @param string $finisherIdentifier The identifier for this finisher
     */
    public function setFinisherIdentifier(string $finisherIdentifier): void
    {
        if (empty($this->finisherIdentifier)) {
            // @deprecated since v11, will be removed in v12. Do not override finisher in case
            // some class implemented setting something in initializeObject() or constructor.
            // In v12, drop the if condition, but keep the body and enable setFinisherIdentifier()
            // in FinisherInterface.
            $this->finisherIdentifier = $finisherIdentifier;
            $this->shortFinisherIdentifier = preg_replace('/Finisher$/', '', $this->finisherIdentifier) ?? '';
        }
    }

    /**
     * @return string
     */
    public function getFinisherIdentifier(): string
    {
        return $this->finisherIdentifier;
    }

    /**
     * @param array $options configuration options in the format ['option1' => 'value1', 'option2' => 'value2', ...]
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
     */
    public function setOption(string $optionName, $optionValue)
    {
        $this->options[$optionName] = $optionValue;
    }

    /**
     * Executes the finisher
     *
     * @param FinisherContext $finisherContext The Finisher context that contains the current Form Runtime and Response
     * @return string|null
     */
    final public function execute(FinisherContext $finisherContext)
    {
        $this->finisherContext = $finisherContext;

        if (!$this->isEnabled()) {
            return null;
        }

        return $this->executeInternal();
    }

    /**
     * This method is called in the concrete finisher whenever self::execute() is called.
     *
     * Override and fill with your own implementation!
     *
     * @return string|null
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
     */
    protected function parseOption(string $optionName)
    {
        if ($optionName === 'translation') {
            return null;
        }

        try {
            $optionValue = ArrayUtility::getValueByPath($this->options, $optionName, '.');
        } catch (MissingArrayPathException $exception) {
            $optionValue = null;
        }
        try {
            $defaultValue = ArrayUtility::getValueByPath($this->defaultOptions, $optionName, '.');
        } catch (MissingArrayPathException $exception) {
            $defaultValue = null;
        }

        if ($optionValue === null && $defaultValue !== null) {
            $optionValue = $defaultValue;
        }

        if ($optionValue === null) {
            return null;
        }

        if (!is_string($optionValue) && !is_array($optionValue)) {
            return $optionValue;
        }

        $formRuntime = $this->finisherContext->getFormRuntime();
        $optionValue = $this->substituteRuntimeReferences($optionValue, $formRuntime);

        if (is_string($optionValue)) {
            $translationOptions = isset($this->options['translation']) && \is_array($this->options['translation'])
                                ? $this->options['translation']
                                : [];

            $optionValue = $this->translateFinisherOption(
                $optionValue,
                $formRuntime,
                $optionName,
                $optionValue,
                $translationOptions
            );

            $optionValue = $this->substituteRuntimeReferences($optionValue, $formRuntime);
        }

        if (empty($optionValue)) {
            if ($defaultValue !== null) {
                $optionValue = $defaultValue;
            }
        }
        return $optionValue;
    }

    /**
     * Wraps TranslationService::translateFinisherOption to recursively
     * invoke all array items of resolved form state values or nested
     * finisher option configuration settings.
     *
     * @param string|array $subject
     * @param FormRuntime $formRuntime
     * @param string $optionName
     * @param string|array $optionValue
     * @param array $translationOptions
     * @return array|string
     */
    protected function translateFinisherOption(
        $subject,
        FormRuntime $formRuntime,
        string $optionName,
        $optionValue,
        array $translationOptions
    ) {
        if (is_array($subject)) {
            foreach ($subject as $key => $value) {
                $subject[$key] = $this->translateFinisherOption(
                    $value,
                    $formRuntime,
                    $optionName . '.' . $value,
                    $value,
                    $translationOptions
                );
            }
            return $subject;
        }

        return GeneralUtility::makeInstance(TranslationService::class)->translateFinisherOption(
            $formRuntime,
            $this->finisherIdentifier,
            $optionName,
            $optionValue,
            $translationOptions
        );
    }

    /**
     * You can encapsulate an option value with {}.
     * This enables you to access every gettable property from the
     * TYPO3\CMS\Form\Domain\Runtime\FormRuntime.
     *
     * For example: {formState.formValues.<elementIdentifier>}
     * or {<elementIdentifier>}
     *
     * Both examples are equal to "$formRuntime->getFormState()->getFormValues()[<elementIdentifier>]"
     * There is a special option value '{__currentTimestamp}'.
     * This will be replaced with the current timestamp.
     *
     * @param string|array $needle
     * @param FormRuntime $formRuntime
     * @return mixed
     */
    protected function substituteRuntimeReferences($needle, FormRuntime $formRuntime)
    {
        // neither array nor string, directly return
        if (!is_array($needle) && !is_string($needle)) {
            return $needle;
        }

        // resolve (recursively) all array items
        if (is_array($needle)) {
            $substitutedNeedle = [];
            foreach ($needle as $key => $item) {
                $key = $this->substituteRuntimeReferences($key, $formRuntime);
                $item = $this->substituteRuntimeReferences($item, $formRuntime);
                $substitutedNeedle[$key] = $item;
            }
            return $substitutedNeedle;
        }

        // substitute one(!) variable in string which either could result
        // again in a string or an array representing multiple values
        if (preg_match('/^{([^}]+)}$/', $needle, $matches)) {
            return $this->resolveRuntimeReference(
                $matches[1],
                $formRuntime
            );
        }

        // in case string contains more than just one variable or just a static
        // value that does not need to be substituted at all, candidates are:
        // * "prefix{variable}suffix
        // * "{variable-1},{variable-2}"
        // * "some static value"
        // * mixed cases of the above
        return preg_replace_callback(
            '/{([^}]+)}/',
            function ($matches) use ($formRuntime) {
                $value = $this->resolveRuntimeReference(
                    $matches[1],
                    $formRuntime
                );

                // substitute each match by returning the resolved value
                if (!is_array($value)) {
                    return $value;
                }

                // now the resolve value is an array that shall substitute
                // a variable in a string that probably is not the only one
                // or is wrapped with other static string content (see above)
                // ... which is just not possible
                throw new FinisherException(
                    'Cannot convert array to string',
                    1519239265
                );
            },
            $needle
        );
    }

    /**
     * Resolving property by name from submitted form data.
     *
     * @param string $property
     * @param FormRuntime $formRuntime
     * @return int|string|array
     */
    protected function resolveRuntimeReference(string $property, FormRuntime $formRuntime)
    {
        if ($property === '__currentTimestamp') {
            return time();
        }

        // try to resolve the path '{...}' within the FormRuntime
        $value = ObjectAccess::getPropertyPath($formRuntime, $property);

        if (is_object($value)) {
            $element = $formRuntime->getFormDefinition()->getElementByIdentifier($property);

            if (!$element instanceof StringableFormElementInterface) {
                throw new FinisherException(
                    sprintf('Cannot convert object value of "%s" to string', $property),
                    1574362327
                );
            }

            $value = $element->valueToString($value);
        }

        if ($value === null) {
            // try to resolve the path '{...}' within the FinisherVariableProvider
            $value = ObjectAccess::getPropertyPath(
                $this->finisherContext->getFinisherVariableProvider(),
                $property
            );
        }

        if ($value !== null) {
            return $value;
        }

        // in case no value could be resolved
        return '{' . $property . '}';
    }

    /**
     * Returns whether this finisher is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return !isset($this->options['renderingOptions']['enabled']) || (bool)$this->parseOption('renderingOptions.enabled') === true;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
