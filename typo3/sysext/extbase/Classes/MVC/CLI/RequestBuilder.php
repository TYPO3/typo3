<?php
/***************************************************************
*  Copyright notice
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Builds a CLI request object from the raw command call
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Extbase_MVC_CLI_RequestBuilder {

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var Tx_Extbase_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * @var Tx_Extbase_MVC_CLI_CommandManager
	 */
	protected $commandManager;

	/**
	 * @param Tx_Extbase_Object_ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param Tx_Extbase_Reflection_Service $reflectionService
	 * @return void
	 */
	public function injectReflectionService(Tx_Extbase_Reflection_Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param Tx_Extbase_MVC_CLI_CommandManager $commandManager
	 * @return void
	 */
	public function injectCommandManager(Tx_Extbase_MVC_CLI_CommandManager $commandManager) {
		$this->commandManager = $commandManager;
	}

	/**
	 * Builds a CLI request object from a command line.
	 *
	 * The given command line may be a string (e.g. "myextension:foo do-that-thing --force") or
	 * an array consisting of the individual parts. The array must not include the script
	 * name (like in $argv) but start with command right away.
	 *
	 * @param mixed $commandLine The command line, either as a string or as an array
	 * @return Tx_Extbase_MVC_CLI_Request The CLI request as an object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function build($commandLine = '') {
		$request = $this->objectManager->get('Tx_Extbase_MVC_CLI_Request');
		$request->setControllerObjectName('Tx_Extbase_Command_HelpCommandController');

		$rawCommandLineArguments = is_array($commandLine) ? $commandLine : explode(' ', $commandLine);
		if (count($rawCommandLineArguments) === 0) {
			$request->setControllerCommandName('helpStub');
			return $request;
		}
		$commandIdentifier = trim(array_shift($rawCommandLineArguments));
		try {
			$command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
		} catch (Tx_Extbase_MVC_Exception_Command $exception) {
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
	 * @return array All and exceeding command line arguments
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseRawCommandLineArguments(array $rawCommandLineArguments, $controllerObjectName, $controllerCommandName) {
		$commandLineArguments = array();
		$exceedingArguments = array();
		$commandMethodName = $controllerCommandName . 'Command';
		$commandMethodParameters = $this->reflectionService->getMethodParameters($controllerObjectName, $commandMethodName);

		$requiredArguments = array();
		$optionalArguments = array();
		$argumentNames = array();
		foreach ($commandMethodParameters as $parameterName => $parameterInfo) {
			$argumentNames[] = $parameterName;
			if ($parameterInfo['optional'] === FALSE) {
				$requiredArguments[strtolower($parameterName)] = array('parameterName' => $parameterName, 'type' => $parameterInfo['type']);
			} else {
				$optionalArguments[strtolower($parameterName)] = array('parameterName' => $parameterName, 'type' => $parameterInfo['type']);
			}
		}

		$decidedToUseNamedArguments = FALSE;
		$decidedToUseUnnamedArguments = FALSE;
		$argumentIndex = 0;
		while (count($rawCommandLineArguments) > 0) {

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
				} elseif(isset($requiredArguments[$argumentName])) {
					if ($decidedToUseUnnamedArguments) {
						throw new Tx_Extbase_MVC_Exception_InvalidArgumentMixing(sprintf('Unexpected named argument "%s". If you use unnamed arguments, all required arguments must be passed without a name.', $argumentName), 1309971821);
					}
					$decidedToUseNamedArguments = TRUE;
					$argumentValue = $this->getValueOfCurrentCommandLineOption($rawArgument, $rawCommandLineArguments, $requiredArguments[$argumentName]['type']);
					$commandLineArguments[$requiredArguments[$argumentName]['parameterName']] = $argumentValue;
					unset($requiredArguments[$argumentName]);
				}
			} else {
				if (count($requiredArguments) > 0) {
					if ($decidedToUseNamedArguments) {
						throw new Tx_Extbase_MVC_Exception_InvalidArgumentMixing(sprintf('Unexpected unnamed argument "%s". If you use named arguments, all required arguments must be passed named.', $rawArgument), 1309971820);
					}
					$argument = array_shift($requiredArguments);
					$commandLineArguments[$argument['parameterName']] = $rawArgument;
					$decidedToUseUnnamedArguments = TRUE;
				} else {
					if ($argumentIndex < count($argumentNames)) {
						$commandLineArguments[$argumentNames[$argumentIndex]] = $rawArgument;
					} else {
						$exceedingArguments[] = $rawArgument;
					}
				}
			}
			$argumentIndex ++;
		}

		return array($commandLineArguments, $exceedingArguments);
	}

	/**
	 * Extracts the option or argument name from the name / value pair of a command line.
	 *
	 * @param string $commandLinePart Part of the command line, e.g. "my-important-option=SomeInterestingValue"
	 * @return string The lowercased argument name, e.g. "myimportantoption"
	 */
	protected function extractArgumentNameFromCommandLinePart($commandLinePart) {
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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getValueOfCurrentCommandLineOption($currentArgument, array &$rawCommandLineArguments, $expectedArgumentType) {
		if ((!isset($rawCommandLineArguments[0]) && (strpos($currentArgument, '=') === FALSE)) || (isset($rawCommandLineArguments[0]) && $rawCommandLineArguments[0][0] === '-' && (strpos($currentArgument, '=') === FALSE))) {
			return TRUE;
		}

		if (strpos($currentArgument, '=') === FALSE) {
			$possibleValue = trim(array_shift($rawCommandLineArguments));
			if (strpos($possibleValue, '=') === FALSE) {
				if ($expectedArgumentType !== 'boolean') {
					return $possibleValue;
				}
				if (array_search($possibleValue, array('on', '1', 'y', 'yes', 'true', 'TRUE')) !== FALSE) {
					return TRUE;
				}
				if (array_search($possibleValue, array('off', '0', 'n', 'no', 'false', 'FALSE')) !== FALSE) {
					return FALSE;
				}
				array_unshift($rawCommandLineArguments, $possibleValue);
				return TRUE;
			}
			$currentArgument .= $possibleValue;
		}

		$splitArgument = explode('=', $currentArgument, 2);
		while ((!isset($splitArgument[1]) || trim($splitArgument[1]) === '') && count($rawCommandLineArguments) > 0) {
			$currentArgument .= array_shift($rawCommandLineArguments);
			$splitArgument = explode('=', $currentArgument);
		}

		$value = (isset($splitArgument[1])) ? $splitArgument[1] : '';
		return $value;
	}
}
?>