<?php
namespace TYPO3\CMS\Extbase\Mvc\Cli;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Represents a Command
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Command {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var string
	 */
	protected $controllerClassName;

	/**
	 * @var string
	 */
	protected $controllerCommandName;

	/**
	 * @var string
	 */
	protected $commandIdentifier;

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\MethodReflection
	 */
	protected $commandMethodReflection;

	/**
	 * Reflection service
	 *
	 * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 */
	private $reflectionService;

	/**
	 * Constructor
	 *
	 * @param string $controllerClassName Class name of the controller providing the command
	 * @param string $controllerCommandName Command name, i.e. the method name of the command, without the "Command" suffix
	 * @throws \InvalidArgumentException
	 */
	public function __construct($controllerClassName, $controllerCommandName) {
		$this->controllerClassName = $controllerClassName;
		$this->controllerCommandName = $controllerCommandName;
		$delimiter = strpos($controllerClassName, '\\') !== FALSE ? '\\' : '_';
		$classNameParts = explode($delimiter, $controllerClassName);
		if (isset($classNameParts[0]) && $classNameParts[0] === 'TYPO3' && isset($classNameParts[1]) && $classNameParts[1] === 'CMS') {
			$classNameParts[0] .= '\\' . $classNameParts[1];
			unset($classNameParts[1]);
			$classNameParts = array_values($classNameParts);
		}
		if (count($classNameParts) !== 4 || strpos($classNameParts[3], 'CommandController') === FALSE) {
			throw new \InvalidArgumentException('Invalid controller class name "' . $controllerClassName . '"', 1305100019);
		}
		$extensionKey = \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($classNameParts[1]);
		$this->commandIdentifier = strtolower($extensionKey . ':' . substr($classNameParts[3], 0, -17) . ':' . $controllerCommandName);
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService Reflection service
	 */
	public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager A reference to the object manager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @return string
	 */
	public function getControllerClassName() {
		return $this->controllerClassName;
	}

	/**
	 * @return string
	 */
	public function getControllerCommandName() {
		return $this->controllerCommandName;
	}

	/**
	 * Returns the command identifier for this command
	 *
	 * @return string The command identifier for this command, following the pattern extensionname:controllername:commandname
	 */
	public function getCommandIdentifier() {
		return $this->commandIdentifier;
	}

	/**
	 * Returns a short description of this command
	 *
	 * @return string A short description
	 */
	public function getShortDescription() {
		$lines = explode(chr(10), $this->getCommandMethodReflection()->getDescription());
		return count($lines) > 0 ? trim($lines[0]) : '<no description available>';
	}

	/**
	 * Returns a longer description of this command
	 * This is the complete method description except for the first line which can be retrieved via getShortDescription()
	 * If The command description only consists of one line, an empty string is returned
	 *
	 * @return string A longer description of this command
	 */
	public function getDescription() {
		$lines = explode(chr(10), $this->getCommandMethodReflection()->getDescription());
		array_pop($lines);
		$descriptionLines = array();
		foreach ($lines as $line) {
			$trimmedLine = trim($line);
			if ($descriptionLines !== array() || $trimmedLine !== '') {
				$descriptionLines[] = $trimmedLine;
			}
		}
		return implode(chr(10), $descriptionLines);
	}

	/**
	 * Returns TRUE if this command expects required and/or optional arguments, otherwise FALSE
	 *
	 * @return boolean
	 */
	public function hasArguments() {
		return count($this->getCommandMethodReflection()->getParameters()) > 0;
	}

	/**
	 * Returns an array of \TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition that contains
	 * information about required/optional arguments of this command.
	 * If the command does not expect any arguments, an empty array is returned
	 *
	 * @return array<\TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition>
	 */
	public function getArgumentDefinitions() {
		if (!$this->hasArguments()) {
			return array();
		}
		$commandArgumentDefinitions = array();
		$commandMethodReflection = $this->getCommandMethodReflection();
		$annotations = $commandMethodReflection->getTagsValues();
		$commandParameters = $this->reflectionService->getMethodParameters($this->controllerClassName, $this->controllerCommandName . 'Command');
		$i = 0;
		foreach ($commandParameters as $commandParameterName => $commandParameterDefinition) {
			$explodedAnnotation = explode(' ', $annotations['param'][$i]);
			array_shift($explodedAnnotation);
			array_shift($explodedAnnotation);
			$description = implode(' ', $explodedAnnotation);
			$required = $commandParameterDefinition['optional'] !== TRUE;
			$commandArgumentDefinitions[] = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\CommandArgumentDefinition', $commandParameterName, $required, $description);
			$i++;
		}
		return $commandArgumentDefinitions;
	}

	/**
	 * Tells if this command is internal and thus should not be exposed through help texts, user documentation etc.
	 * Internall commands are still accessible through the regular command line interface, but should not be used
	 * by users.
	 *
	 * @return boolean
	 */
	public function isInternal() {
		return $this->getCommandMethodReflection()->isTaggedWith('internal');
	}

	/**
	 * Tells if this command flushes all caches and thus needs special attention in the interactive shell.
	 *
	 * Note that neither this method nor the @flushesCaches annotation is currently part of the official API.
	 *
	 * @return boolean
	 */
	public function isFlushingCaches() {
		return $this->getCommandMethodReflection()->isTaggedWith('flushesCaches');
	}

	/**
	 * Returns an array of command identifiers which were specified in the "@see"
	 * annotation of a command method.
	 *
	 * @return array
	 */
	public function getRelatedCommandIdentifiers() {
		$commandMethodReflection = $this->getCommandMethodReflection();
		if (!$commandMethodReflection->isTaggedWith('see')) {
			return array();
		}
		$relatedCommandIdentifiers = array();
		foreach ($commandMethodReflection->getTagValues('see') as $tagValue) {
			if (preg_match('/^[\\w\\d\\.]+:[\\w\\d]+:[\\w\\d]+$/', $tagValue) === 1) {
				$relatedCommandIdentifiers[] = $tagValue;
			}
		}
		return $relatedCommandIdentifiers;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Reflection\MethodReflection
	 */
	protected function getCommandMethodReflection() {
		if ($this->commandMethodReflection === NULL) {
			$this->commandMethodReflection = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Reflection\\MethodReflection', $this->controllerClassName, $this->controllerCommandName . 'Command');
		}
		return $this->commandMethodReflection;
	}
}

?>