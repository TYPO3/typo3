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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\StringableFormElementInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Service\FormValueResolver;
use TYPO3\CMS\Form\Service\TranslationService;

/**
 * Finisher base class.
 *
 * Scope: frontend
 * **This class is meant to be sub classed by developers**
 */
abstract class AbstractFinisher implements FinisherInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

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
     * @var FinisherContext
     */
    protected $finisherContext;

    private ViewFactoryInterface $viewFactory;

    private TranslationService $translationService;

    private FormValueResolver $formValueResolver;

    public function injectViewFactory(ViewFactoryInterface $viewFactory)
    {
        $this->viewFactory = $viewFactory;
    }

    public function injectTranslationService(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    public function injectFormValueResolver(FormValueResolver $formValueResolver): void
    {
        $this->formValueResolver = $formValueResolver;
    }

    /**
     * @param string $finisherIdentifier The identifier for this finisher
     */
    public function setFinisherIdentifier(string $finisherIdentifier): void
    {
        $this->finisherIdentifier = $finisherIdentifier;
        $this->shortFinisherIdentifier = preg_replace('/Finisher$/', '', $finisherIdentifier) ?? '';
    }

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

        try {
            return $this->executeInternal();
        } catch (FinisherException $e) {
            $this->logger->error('Failed to execute finisher', ['exception' => $e]);
            $this->finisherContext->cancel();
            $formRuntime = $this->finisherContext->getFormRuntime();
            $renderingOptions = $formRuntime->getRenderingOptions();
            $viewFactoryData = new ViewFactoryData(
                templateRootPaths: is_array($renderingOptions['templateRootPaths'] ?? null) ? $renderingOptions['templateRootPaths'] : [],
                partialRootPaths: is_array($renderingOptions['partialRootPaths'] ?? null) ? $renderingOptions['partialRootPaths'] : [],
                layoutRootPaths: is_array($renderingOptions['layoutRootPaths'] ?? null) ? $renderingOptions['layoutRootPaths'] : [],
                request: $this->finisherContext->getRequest(),
            );
            $view = $this->viewFactory->create($viewFactoryData);
            $message = $this->parseOption('errorMessage') ?: $this->translationService->translate('form.finisher.error', null, 'EXT:form/Resources/Private/Language/locallang.xlf');
            $view->assign('message', $message);
            return $view->render('Finishers/Error');
        }
    }

    /**
     * This method is called in the concrete finisher whenever self::execute() is called.
     *
     * Override and fill with your own implementation!
     *
     * @throws FinisherException
     * @return string|void|null
     */
    abstract protected function executeInternal();

    /**
     * Same as parseOption(), except that {<elementIdentifier>} resolves to the
     * display representation of a submitted value - the translated label of a
     * select option instead of the option key that was submitted.
     *
     * Use it for options that are read by a human, never for options that end
     * up in a query, a stored record or a URL.
     *
     * @return string|array|int|bool|\Closure|callable|null
     */
    protected function parseOptionAsDisplayValue(string $optionName)
    {
        return $this->parseOptionValue($optionName, true);
    }

    /**
     * Read the option called $optionName from $this->options, and parse {...}
     * as object accessors.
     *
     * Then translate the value.
     *
     * If $optionName was not found, the corresponding default option is returned (from $this->defaultOptions)
     *
     * @param string $optionName
     * @return string|array|int|bool|\Closure|callable|null
     */
    protected function parseOption(string $optionName)
    {
        return $this->parseOptionValue($optionName, false);
    }

    /**
     * @return string|array|int|bool|\Closure|callable|null
     */
    private function parseOptionValue(string $optionName, bool $resolveDisplayValues)
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
        $optionValue = $this->substituteReferences($optionValue, $formRuntime, $resolveDisplayValues);

        if (is_string($optionValue)) {
            $translationOptions = is_array($this->options['translation'] ?? null)
                                ? $this->options['translation']
                                : [];

            $optionValue = $this->translateFinisherOption(
                $optionValue,
                $formRuntime,
                $optionName,
                $optionValue,
                $translationOptions
            );

            $optionValue = $this->substituteReferences($optionValue, $formRuntime, $resolveDisplayValues);
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
     * @param string|array $optionValue
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

        return $this->translationService->translateFinisherOption(
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
        return $this->substituteReferences($needle, $formRuntime, false);
    }

    /**
     * @param string|array $needle
     * @return mixed
     */
    private function substituteReferences($needle, FormRuntime $formRuntime, bool $resolveDisplayValues)
    {
        // neither array nor string, directly return
        if (!is_array($needle) && !is_string($needle)) {
            return $needle;
        }

        // resolve (recursively) all array items
        if (is_array($needle)) {
            $substitutedNeedle = [];
            foreach ($needle as $key => $item) {
                $key = $this->substituteReferences($key, $formRuntime, $resolveDisplayValues);
                $item = $this->substituteReferences($item, $formRuntime, $resolveDisplayValues);
                $substitutedNeedle[$key] = $item;
            }
            return $substitutedNeedle;
        }

        // substitute one(!) variable in string which either could result
        // again in a string or an array representing multiple values
        if (preg_match('/^{([^}]+)}$/', $needle, $matches)) {
            return $this->resolveReference(
                $matches[1],
                $formRuntime,
                $resolveDisplayValues
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
            function ($matches) use ($formRuntime, $resolveDisplayValues) {
                $value = $this->resolveReference(
                    $matches[1],
                    $formRuntime,
                    $resolveDisplayValues
                );

                // substitute each match by returning the resolved value
                if (!is_array($value)) {
                    return $value;
                }

                return $this->arrayToString($value);
            },
            $needle
        );
    }

    /**
     * Resolving property by name from submitted form data.
     *
     * @return int|string|array
     */
    protected function resolveRuntimeReference(string $property, FormRuntime $formRuntime)
    {
        if ($property === '__currentTimestamp') {
            return time();
        }

        // try to resolve the path '{...}' within the FormRuntime
        $value = ObjectAccess::getPropertyPath($formRuntime, $property);
        if ($value !== null) {
            $element = $this->resolveFormElementByProperty($property, $formRuntime);
            if (is_object($value) && $element instanceof StringableFormElementInterface) {
                $value = $element->valueToString($value);
            }
        } else {
            // try to resolve the path '{...}' within the FinisherVariableProvider
            $value = ObjectAccess::getPropertyPath(
                $this->finisherContext->getFinisherVariableProvider(),
                $property
            );
        }

        if ($value !== null) {
            if (is_object($value) && !method_exists($value, '__toString')) {
                throw new FinisherException(
                    sprintf('Cannot convert object value of "%s" to string', $property),
                    1574362327
                );
            }

            return $value;
        }

        // in case no value could be resolved
        return '{' . $property . '}';
    }

    /**
     * Resolves a reference the way resolveRuntimeReference() does, and maps the
     * result to the display representation the form element provides for it.
     *
     * @return mixed
     */
    private function resolveReference(string $property, FormRuntime $formRuntime, bool $resolveDisplayValues)
    {
        $value = $this->resolveRuntimeReference($property, $formRuntime);
        if (!$resolveDisplayValues) {
            return $value;
        }

        $element = $this->resolveFormElementByProperty($property, $formRuntime);
        if (!$element instanceof FormElementInterface) {
            return $value;
        }

        return $this->formValueResolver->resolveDisplayValue($element, $value, $formRuntime);
    }

    private function resolveFormElementByProperty(string $property, FormRuntime $formRuntime): ?object
    {
        $elementIdentifier = $this->resolveElementIdentifierFromProperty($property);
        if ($elementIdentifier === null) {
            return null;
        }

        return $formRuntime->getFormDefinition()->getElementByIdentifier($elementIdentifier);
    }

    private function resolveElementIdentifierFromProperty(string $property): ?string
    {
        if (!str_contains($property, '.')) {
            return $property;
        }

        $prefixes = [
            'formState.formValues.',
            'formValues.',
        ];
        foreach ($prefixes as $prefix) {
            if (str_starts_with($property, $prefix)) {
                return substr($property, strlen($prefix));
            }
        }

        return null;
    }

    private function arrayToString(array $value): string
    {
        $flatValues = [];
        array_walk_recursive($value, static function (mixed $item) use (&$flatValues): void {
            if (is_object($item) && !method_exists($item, '__toString')) {
                throw new FinisherException(
                    sprintf('Cannot convert object value of type "%s" to string', get_debug_type($item)),
                    1787754756
                );
            }
            $flatValues[] = (string)$item;
        });
        return implode(', ', $flatValues);
    }

    /**
     * Returns whether this finisher is enabled
     */
    public function isEnabled(): bool
    {
        return !isset($this->options['renderingOptions']['enabled']) || (bool)$this->parseOption('renderingOptions.enabled') === true;
    }
}
