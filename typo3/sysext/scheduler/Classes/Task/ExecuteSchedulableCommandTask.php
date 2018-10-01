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

        $input = new ArrayInput($this->getArguments(), $schedulableCommand->getDefinition());
        $output = new NullOutput();

        return $schedulableCommand->run($input, $output) === 0;
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     */
    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
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
}
