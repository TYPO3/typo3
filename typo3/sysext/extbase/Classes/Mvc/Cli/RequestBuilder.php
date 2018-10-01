<?php
namespace TYPO3\CMS\Extbase\Mvc\Cli;

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

/**
 * Builds a CLI request object from the raw command call
 *
 * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Use symfony/console commands instead.
 */
class RequestBuilder implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager
     */
    protected $commandManager;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
     */
    public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager $commandManager
     */
    public function injectCommandManager(\TYPO3\CMS\Extbase\Mvc\Cli\CommandManager $commandManager)
    {
        $this->commandManager = $commandManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Builds a CLI request object from a command line.
     *
     * The given command line may be a string (e.g. "myextension:foo do-that-thing --force") or
     * an array consisting of the individual parts. The array must not include the script
     * name (like in $argv) but start with command right away.
     *
     * @param mixed $commandLine The command line, either as a string or as an array
     * @param string $callingScript The calling script (usually ./typo3/sysext/core/bin/typo3)
     * @return \TYPO3\CMS\Extbase\Mvc\Cli\Request The CLI request as an object
     */
    public function build($commandLine = '', $callingScript = './typo3/sysext/core/bin/typo3')
    {
        $request = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Cli\Request::class);
        $request->setCallingScript($callingScript);
        $request->setControllerObjectName(\TYPO3\CMS\Extbase\Command\HelpCommandController::class);
        $rawCommandLineArguments = is_array($commandLine) ? $commandLine : explode(' ', $commandLine);
        if (empty($rawCommandLineArguments)) {
            $request->setControllerCommandName('helpStub');
            return $request;
        }
        $commandIdentifier = trim(array_shift($rawCommandLineArguments));
        try {
            $command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
            $this->configurationManager->setConfiguration(['extensionName' => $command->getExtensionName()]);
        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\CommandException $exception) {
            $request->setArgument('exception', $exception);
            $request->setControllerCommandName('error');
            return $request;
        }
        $controllerObjectName = $command->getControllerClassName();
        $controllerCommandName = $command->getControllerCommandName();
        $request->setControllerObjectName($controllerObjectName);
        $request->setControllerCommandName($controllerCommandName);
        list($commandLineArguments, $exceedingCommandLineArguments) = $this->parseRawCommandLineArguments($rawCommandLineArguments, $controllerObjectName, $controllerCommandName);
        $request->setArguments($commandLineArguments);
        $request->setExceedingArguments($exceedingCommandLineArguments);
        return $request;
    }

    /**
     * Takes an array of unparsed command line arguments and options and converts it separated
     * by named arguments, options and unnamed arguments.
     *
     * @param array $rawCommandLineArguments The unparsed command parts (such as "--foo") as an array
     * @param string $controllerObjectName Object name of the designated command controller
     * @param string $controllerCommandName Command name of the recognized command (ie. method name without "Command" suffix)
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentMixingException
     * @return array All and exceeding command line arguments
     */
    protected function parseRawCommandLineArguments(array $rawCommandLineArguments, $controllerObjectName, $controllerCommandName)
    {
        $commandLineArguments = [];
        $exceedingArguments = [];
        $commandMethodName = $controllerCommandName . 'Command';
        $commandMethodParameters = $this->reflectionService
            ->getClassSchema($controllerObjectName)
            ->getMethod($commandMethodName)['params'] ?? [];
        $requiredArguments = [];
        $optionalArguments = [];
        $argumentNames = [];
        foreach ($commandMethodParameters as $parameterName => $parameterInfo) {
            $argumentNames[] = $parameterName;
            if ($parameterInfo['optional'] === false) {
                $requiredArguments[strtolower($parameterName)] = ['parameterName' => $parameterName, 'type' => $parameterInfo['type']];
            } else {
                $optionalArguments[strtolower($parameterName)] = ['parameterName' => $parameterName, 'type' => $parameterInfo['type']];
            }
        }
        $decidedToUseNamedArguments = false;
        $decidedToUseUnnamedArguments = false;
        $argumentIndex = 0;
        while (!empty($rawCommandLineArguments)) {
            $rawArgument = array_shift($rawCommandLineArguments);
            if ($rawArgument[0] === '-') {
                if ($rawArgument[1] === '-') {
                    $rawArgument = substr($rawArgument, 2);
                } else {
                    $rawArgument = substr($rawArgument, 1);
                }
                $argumentName = $this->extractArgumentNameFromCommandLinePart($rawArgument);
                if (isset($optionalArguments[$argumentName])) {
                    $argumentValue = $this->getValueOfCurrentCommandLineOption($rawArgument, $rawCommandLineArguments, $optionalArguments[$argumentName]['type']);
                    $commandLineArguments[$optionalArguments[$argumentName]['parameterName']] = $argumentValue;
                } elseif (isset($requiredArguments[$argumentName])) {
                    if ($decidedToUseUnnamedArguments) {
                        throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentMixingException(sprintf('Unexpected named argument "%s". If you use unnamed arguments, all required arguments must be passed without a name.', $argumentName), 1309971821);
                    }
                    $decidedToUseNamedArguments = true;
                    $argumentValue = $this->getValueOfCurrentCommandLineOption($rawArgument, $rawCommandLineArguments, $requiredArguments[$argumentName]['type']);
                    $commandLineArguments[$requiredArguments[$argumentName]['parameterName']] = $argumentValue;
                    unset($requiredArguments[$argumentName]);
                }
            } else {
                if (!empty($requiredArguments)) {
                    if ($decidedToUseNamedArguments) {
                        throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentMixingException(sprintf('Unexpected unnamed argument "%s". If you use named arguments, all required arguments must be passed named.', $rawArgument), 1309971820);
                    }
                    $argument = array_shift($requiredArguments);
                    $commandLineArguments[$argument['parameterName']] = $rawArgument;
                    $decidedToUseUnnamedArguments = true;
                } else {
                    if ($argumentIndex < count($argumentNames)) {
                        $commandLineArguments[$argumentNames[$argumentIndex]] = $rawArgument;
                    } else {
                        $exceedingArguments[] = $rawArgument;
                    }
                }
            }
            $argumentIndex++;
        }
        return [$commandLineArguments, $exceedingArguments];
    }

    /**
     * Extracts the option or argument name from the name / value pair of a command line.
     *
     * @param string $commandLinePart Part of the command line, e.g. "my-important-option=SomeInterestingValue
     * @return string The lowercased argument name, e.g. "myimportantoption
     */
    protected function extractArgumentNameFromCommandLinePart($commandLinePart)
    {
        $nameAndValue = explode('=', $commandLinePart, 2);
        return strtolower(str_replace('-', '', $nameAndValue[0]));
    }

    /**
     * Returns the value of the first argument of the given input array. Shifts the parsed argument off the array.
     *
     * @param string $currentArgument The current argument
     * @param array &$rawCommandLineArguments Array of the remaining command line arguments
     * @param string $expectedArgumentType The expected type of the current argument, because booleans get special attention
     * @return string The value of the first argument
     */
    protected function getValueOfCurrentCommandLineOption($currentArgument, array &$rawCommandLineArguments, $expectedArgumentType)
    {
        if (!isset($rawCommandLineArguments[0]) && strpos($currentArgument, '=') === false || isset($rawCommandLineArguments[0]) && $rawCommandLineArguments[0][0] === '-' && strpos($currentArgument, '=') === false) {
            return true;
        }
        if (strpos($currentArgument, '=') === false) {
            $possibleValue = trim(array_shift($rawCommandLineArguments));
            if (strpos($possibleValue, '=') === false) {
                if ($expectedArgumentType !== 'boolean') {
                    return $possibleValue;
                }
                if (in_array($possibleValue, ['on', '1', 'y', 'yes', 'true', 'TRUE'], true)) {
                    return true;
                }
                if (in_array($possibleValue, ['off', '0', 'n', 'no', 'false', 'FALSE'], true)) {
                    return false;
                }
                array_unshift($rawCommandLineArguments, $possibleValue);
                return true;
            }
            $currentArgument .= $possibleValue;
        }
        $splitArgument = explode('=', $currentArgument, 2);
        while ((!isset($splitArgument[1]) || trim($splitArgument[1]) === '') && !empty($rawCommandLineArguments)) {
            $currentArgument .= array_shift($rawCommandLineArguments);
            $splitArgument = explode('=', $currentArgument);
        }
        $value = $splitArgument[1] ?? '';
        return $value;
    }
}
