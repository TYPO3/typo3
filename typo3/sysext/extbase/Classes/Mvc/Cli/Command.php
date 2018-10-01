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

use TYPO3\CMS\Extbase\Reflection\ClassSchema;

/**
 * Represents a Command
 *
 * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Use symfony/console commands instead.
 */
class Command
{
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
     * Name of the extension to which this command belongs
     *
     * @var string
     */
    protected $extensionName;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * @var ClassSchema
     */
    protected $classSchema;

    /**
     * @var string
     */
    protected $controllerCommandMethod;

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
     * Constructor
     *
     * @param string $controllerClassName Class name of the controller providing the command
     * @param string $controllerCommandName Command name, i.e. the method name of the command, without the "Command" suffix
     * @throws \InvalidArgumentException
     */
    public function __construct($controllerClassName, $controllerCommandName)
    {
        $this->controllerClassName = $controllerClassName;
        $this->controllerCommandName = $controllerCommandName;
        $this->controllerCommandMethod = $this->controllerCommandName . 'Command';
        $classNameParts = explode('\\', $controllerClassName);
        if (isset($classNameParts[0]) && $classNameParts[0] === 'TYPO3' && isset($classNameParts[1]) && $classNameParts[1] === 'CMS') {
            $classNameParts[0] .= '\\' . $classNameParts[1];
            unset($classNameParts[1]);
            $classNameParts = array_values($classNameParts);
        }
        $numberOfClassNameParts = count($classNameParts);
        if ($numberOfClassNameParts < 3) {
            throw new \InvalidArgumentException(
                'Controller class names must at least consist of three parts: vendor, extension name and path.',
                1438782187
            );
        }
        if (strpos($classNameParts[$numberOfClassNameParts - 1], 'CommandController') === false) {
            throw new \InvalidArgumentException(
                'Invalid controller class name "' . $controllerClassName . '". Class name must end with "CommandController".',
                1305100019
            );
        }

        $this->extensionName = $classNameParts[1];
        $extensionKey = \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($this->extensionName);
        $this->commandIdentifier = strtolower($extensionKey . ':' . substr($classNameParts[$numberOfClassNameParts - 1], 0, -17) . ':' . $controllerCommandName);
    }

    public function initializeObject()
    {
        $this->classSchema = $this->reflectionService->getClassSchema($this->controllerClassName);
    }

    /**
     * @return string
     */
    public function getControllerClassName()
    {
        return $this->controllerClassName;
    }

    /**
     * @return string
     */
    public function getControllerCommandName()
    {
        return $this->controllerCommandName;
    }

    /**
     * Returns the command identifier for this command
     *
     * @return string The command identifier for this command, following the pattern extensionname:controllername:commandname
     */
    public function getCommandIdentifier()
    {
        return $this->commandIdentifier;
    }

    /**
     * Returns the name of the extension to which this command belongs
     *
     * @return string
     */
    public function getExtensionName()
    {
        return $this->extensionName;
    }

    /**
     * Returns a short description of this command
     *
     * @return string A short description
     */
    public function getShortDescription()
    {
        $lines = explode(LF, $this->classSchema->getMethod($this->controllerCommandMethod)['description']);
        return !empty($lines) ? trim($lines[0]) : '<no description available>';
    }

    /**
     * Returns a longer description of this command
     * This is the complete method description except for the first line which can be retrieved via getShortDescription()
     * If The command description only consists of one line, an empty string is returned
     *
     * @return string A longer description of this command
     */
    public function getDescription()
    {
        $lines = explode(LF, $this->classSchema->getMethod($this->controllerCommandMethod)['description']);
        array_shift($lines);
        $descriptionLines = [];
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($descriptionLines !== [] || $trimmedLine !== '') {
                $descriptionLines[] = $trimmedLine;
            }
        }
        return implode(LF, $descriptionLines);
    }

    /**
     * Returns TRUE if this command expects required and/or optional arguments, otherwise FALSE
     *
     * @return bool
     */
    public function hasArguments()
    {
        return !empty($this->classSchema->getMethod($this->controllerCommandMethod)['params']);
    }

    /**
     * Returns an array of \TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition that contains
     * information about required/optional arguments of this command.
     * If the command does not expect any arguments, an empty array is returned
     *
     * @return array<\TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition>
     */
    public function getArgumentDefinitions()
    {
        if (!$this->hasArguments()) {
            return [];
        }
        $commandArgumentDefinitions = [];
        $commandParameters = $this->classSchema->getMethod($this->controllerCommandMethod)['params'];
        $commandParameterTags = $this->classSchema->getMethod($this->controllerCommandMethod)['tags']['param'];
        $i = 0;
        foreach ($commandParameters as $commandParameterName => $commandParameterDefinition) {
            $explodedAnnotation = preg_split('/\s+/', $commandParameterTags[$i], 3);
            $description = !empty($explodedAnnotation[2]) ? $explodedAnnotation[2] : '';
            $required = $commandParameterDefinition['optional'] !== true;
            $commandArgumentDefinitions[] = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition::class, $commandParameterName, $required, $description);
            $i++;
        }
        return $commandArgumentDefinitions;
    }

    /**
     * Tells if this command is internal and thus should not be exposed through help texts, user documentation etc.
     * Internall commands are still accessible through the regular command line interface, but should not be used
     * by users.
     *
     * @return bool
     */
    public function isInternal()
    {
        return isset($this->classSchema->getMethod($this->controllerCommandMethod)['tags']['internal']);
    }

    /**
     * Tells if this command is meant to be used on CLI only.
     *
     * @return bool
     */
    public function isCliOnly()
    {
        return isset($this->classSchema->getMethod($this->controllerCommandMethod)['tags']['cli']);
    }

    /**
     * Tells if this command flushes all caches and thus needs special attention in the interactive shell.
     *
     * Note that neither this method nor the @flushesCaches annotation is currently part of the official API.
     *
     * @return bool
     *
     * @deprecated will be removed in TYPO3 v10.0.
     */
    public function isFlushingCaches()
    {
        trigger_error(
            'Method isFlushingCaches() will be removed in TYPO3 v10.0. Do not call from other extension.',
            E_USER_DEPRECATED
        );
        return isset($this->classSchema->getMethod($this->controllerCommandMethod)['tags']['flushesCaches']);
    }

    /**
     * Returns an array of command identifiers which were specified in the "@see"
     * annotation of a command method.
     *
     * @return array
     */
    public function getRelatedCommandIdentifiers()
    {
        if (!isset($this->classSchema->getMethod($this->controllerCommandMethod)['tags']['see'])) {
            return [];
        }
        $relatedCommandIdentifiers = [];
        foreach ($this->classSchema->getMethod($this->controllerCommandMethod)['tags']['see'] as $tagValue) {
            if (preg_match('/^[\\w\\d\\.]+:[\\w\\d]+:[\\w\\d]+$/', $tagValue) === 1) {
                $relatedCommandIdentifiers[] = $tagValue;
            }
        }
        return $relatedCommandIdentifiers;
    }
}
