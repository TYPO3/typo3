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

namespace TYPO3\CMS\Scheduler\Task;

use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class ExecuteSchedulableCommandTask extends AbstractTask
{
    /**
     * @var string
     */
    protected $commandIdentifier = '';

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $optionValues = [];

    /**
     * @var array
     */
    protected $defaults = [];

    /**
     * This is the main method that is called when a task is executed
     * It MUST be implemented by all classes inheriting from this one
     * Note that there is no error handling, errors and failures are expected
     * to be handled and logged by the client implementations.
     * Should return TRUE on successful execution, FALSE on error.
     *
     * @throws \Exception
     *
     * @return bool Returns TRUE on successful execution, FALSE on error
     */
    public function execute(): bool
    {
        try {
            $commandRegistry = GeneralUtility::makeInstance(CommandRegistry::class);
            $schedulableCommand = $commandRegistry->get($this->commandIdentifier);
        } catch (CommandNotFoundException $e) {
            throw new \RuntimeException(
                sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.unregisteredCommand'),
                    $this->commandIdentifier
                ),
                1505055445,
                $e
            );
        }

        $input = new ArrayInput($this->getParameters(false));
        $input->setInteractive(false);

        $output = new NullOutput();

        return $schedulableCommand->run($input, $output) === 0;
    }

    /**
     * Return a text representation of the selected command and arguments
     *
     * @return string Information to display
     */
    public function getAdditionalInformation(): string
    {
        try {
            $commandRegistry = GeneralUtility::makeInstance(CommandRegistry::class);
            $schedulableCommand = $commandRegistry->get($this->commandIdentifier);
        } catch (CommandNotFoundException $e) {
            return sprintf(
                $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.unregisteredCommand'),
                $this->commandIdentifier
            );
        }

        try {
            $input = new ArrayInput($this->getParameters(true), $schedulableCommand->getDefinition());
            $arguments = $input->__toString();
        } catch (\Symfony\Component\Console\Exception\RuntimeException|InvalidArgumentException $e) {
            return $this->commandIdentifier . "\n"
                . sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.errorParsingArguments'),
                    $e->getMessage()
                );
        } catch (InvalidOptionException $e) {
            return $this->commandIdentifier . "\n"
                . sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.errorParsingOptions'),
                    $e->getMessage()
                );
        }
        if ($arguments !== '') {
            return $this->commandIdentifier . ' ' . $arguments;
        }

        return '';
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOptionValues(): array
    {
        return $this->optionValues;
    }

    public function addDefaultValue(string $argumentName, mixed $argumentValue): void
    {
        if (is_bool($argumentValue)) {
            $argumentValue = (int)$argumentValue;
        }
        $this->defaults[$argumentName] = $argumentValue;
    }

    private function getParameters(bool $forDisplay): array
    {
        $options = [];
        foreach ($this->options as $name => $enabled) {
            if ($enabled) {
                $value = $this->optionValues[$name] ?? null;
                $options['--' . $name] = ($forDisplay && $value === true) ? '' : $value;
            }
        }
        return array_merge($this->arguments, $options);
    }

    public function getTaskType(): string
    {
        return $this->commandIdentifier;
    }

    public function setTaskType(string $taskType): void
    {
        $this->commandIdentifier = $taskType;
    }

    public function getTaskParameters(): array
    {
        return [
            'commandIdentifier' => $this->commandIdentifier,
            'arguments' => $this->arguments,
            'options' => $this->options,
            'optionValues' => $this->optionValues,
        ];
    }
    public function setTaskParameters(array $parameters): void
    {
        $this->commandIdentifier = $parameters['commandIdentifier'] ?? $this->commandIdentifier;
        $this->arguments = $this->processArguments($parameters);
        $processedOptions = $this->processOptions($parameters);
        $this->options = $processedOptions['options'] ?? [];
        $this->optionValues = $processedOptions['optionValues'] ?? [];
    }

    public function validateTaskParameters(array $parameters): bool
    {
        $result = true;
        $commandRegistry = GeneralUtility::makeInstance(CommandRegistry::class);
        $flashMessageQueue = GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier();
        if ($commandRegistry->has($this->getTaskType())
            && (is_array($parameters['arguments'] ?? false) || is_array($parameters['options'] ?? false))
        ) {
            // If this is a registered console command, validate given arguments / options
            $command = $commandRegistry->get($this->getTaskType());
            foreach ($command->getDefinition()->getArguments() as $argument) {
                foreach (($parameters['arguments'] ?? []) as $argumentName => $argumentValue) {
                    if ($argument->getName() !== $argumentName) {
                        continue;
                    }
                    if ($argument->isRequired() && trim($argumentValue) === '') {
                        $flashMessageQueue->addMessage(
                            GeneralUtility::makeInstance(FlashMessage::class, sprintf(
                                $this->getLanguageService()?->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.mandatoryArgumentMissing'),
                                $argumentName
                            ), '', ContextualFeedbackSeverity::ERROR)
                        );
                        $result = false;
                    }
                }
            }
            foreach ($command->getDefinition()->getOptions() as $optionDefinition) {
                $optionEnabled = $parameters['options'][$optionDefinition->getName()] ?? false;
                $optionValue = $parameters['optionValues'][$optionDefinition->getName()] ?? $optionDefinition->getDefault();
                if ($optionEnabled && $optionDefinition->isValueRequired()) {
                    if ($optionDefinition->isArray()) {
                        $testValues = is_array($optionValue) ? $optionValue : GeneralUtility::trimExplode(',', $optionValue, false);
                    } else {
                        $testValues = [$optionValue];
                    }
                    foreach ($testValues as $testValue) {
                        if ($testValue === null || trim($testValue) === '') {
                            // An option that requires a value is used with an empty value
                            $flashMessageQueue->addMessage(
                                GeneralUtility::makeInstance(FlashMessage::class, sprintf(
                                    $this->getLanguageService()?->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.mandatoryArgumentMissing'),
                                    $optionDefinition->getName()
                                ), '', ContextualFeedbackSeverity::ERROR)
                            );
                            $result = false;
                        }
                    }
                }
            }
        }
        return $result;
    }

    protected function processArguments(array $paremeters): array
    {
        if (!is_array($paremeters['arguments'] ?? false)) {
            return [];
        }
        try {
            $commandRegistry = GeneralUtility::makeInstance(CommandRegistry::class);
            $command = $commandRegistry->get($this->commandIdentifier);
        } catch (CommandNotFoundException) {
            return [];
        }
        $arguments = [];
        foreach ($paremeters['arguments'] as $argumentName => $argumentValue) {
            try {
                $argumentDefinition = $command->getDefinition()->getArgument($argumentName);
            } catch (InvalidArgumentException) {
                continue;
            }
            if ($argumentDefinition->isArray() && is_string($argumentValue)) {
                $argumentValue = GeneralUtility::trimExplode(',', $argumentValue, true);
            }
            $arguments[$argumentName] = $argumentValue;
        }
        return $arguments;
    }

    protected function processOptions(array $parameters): array
    {
        if (!is_array($parameters['options'] ?? false)) {
            return [];
        }
        try {
            $commandRegistry = GeneralUtility::makeInstance(CommandRegistry::class);
            $command = $commandRegistry->get($this->commandIdentifier);
        } catch (CommandNotFoundException) {
            return [];
        }
        $options = [];
        $optionValues = [];
        foreach ($command->getDefinition()->getOptions() as $optionDefinition) {
            $optionEnabled = $parameters['options'][$optionDefinition->getName()] ?? false;
            $options[$optionDefinition->getName()] = (bool)$optionEnabled;
            if ($optionDefinition->isValueRequired() || $optionDefinition->isValueOptional() || $optionDefinition->isArray()) {
                $optionValue = $parameters['optionValues'][$optionDefinition->getName()] ?? $optionDefinition->getDefault();
                if ($optionDefinition->isArray() && is_string($optionValue)) {
                    // Do not remove empty array values.
                    // One empty array element indicates the existence of one occurrence of an array option (InputOption::VALUE_IS_ARRAY) without a value.
                    // Empty array elements are also required for command options like "-vvv" (can be entered as ",,").
                    $optionValue = GeneralUtility::trimExplode(',', $optionValue);
                }
            } else {
                // boolean flag: option value must be true if option is added or false otherwise
                $optionValue = (bool)$optionEnabled;
            }
            $optionValues[$optionDefinition->getName()] = $optionValue;
        }
        return ['options' => $options, 'optionValues' => $optionValues];
    }
}
