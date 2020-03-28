<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Scheduler\Task;

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

use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Console\UnknownCommandException;
use TYPO3\CMS\Core\Localization\LanguageService;
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
     * @param string $commandIdentifier
     */
    public function setCommandIdentifier(string $commandIdentifier)
    {
        $this->commandIdentifier = $commandIdentifier;
    }

    /**
     * @return string
     */
    public function getCommandIdentifier(): string
    {
        return $this->commandIdentifier;
    }

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
            $schedulableCommand = $commandRegistry->getCommandByIdentifier($this->commandIdentifier);
        } catch (UnknownCommandException $e) {
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
        $label = $this->commandIdentifier;

        try {
            $commandRegistry = GeneralUtility::makeInstance(CommandRegistry::class);
            $schedulableCommand = $commandRegistry->getCommandByIdentifier($this->commandIdentifier);
        } catch (UnknownCommandException $e) {
            return sprintf(
                $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.unregisteredCommand'),
                $this->commandIdentifier
            );
        }

        try {
            $input = new ArrayInput($this->getParameters(true), $schedulableCommand->getDefinition());
            $arguments = $input->__toString();
        } catch (\Symfony\Component\Console\Exception\RuntimeException|\Symfony\Component\Console\Exception\InvalidArgumentException $e) {
            return $label . "\n"
                . sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.errorParsingArguments'),
                    $e->getMessage()
                );
        } catch (InvalidOptionException $e) {
            return $label . "\n"
                . sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.errorParsingOptions'),
                    $e->getMessage()
                );
        }
        if ($arguments !== '') {
            $label .= ' ' . $arguments;
        }

        return $label;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function getOptionValues(): array
    {
        return $this->optionValues;
    }

    public function setOptionValues(array $optionValues)
    {
        $this->optionValues = $optionValues;
    }

    /**
     * @param string $argumentName
     * @param mixed $argumentValue
     */
    public function addDefaultValue(string $argumentName, $argumentValue)
    {
        if (is_bool($argumentValue)) {
            $argumentValue = (int)$argumentValue;
        }
        $this->defaults[$argumentName] = $argumentValue;
    }

    /**
     * @return LanguageService
     */
    public function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
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
}
