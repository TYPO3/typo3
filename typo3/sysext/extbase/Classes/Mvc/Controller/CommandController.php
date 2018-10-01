<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition;
use TYPO3\CMS\Extbase\Mvc\Cli\ConsoleOutput;
use TYPO3\CMS\Extbase\Mvc\Cli\Request;
use TYPO3\CMS\Extbase\Mvc\Cli\Response;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchCommandException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Reflection\ReflectionService;

/**
 * A controller which processes requests from the command line
 *
 * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Use symfony/console commands instead.
 */
class CommandController implements CommandControllerInterface
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Arguments
     */
    protected $arguments;

    /**
     * Name of the command method
     *
     * @var string
     */
    protected $commandMethodName = '';

    /**
     * Whether the command needs admin access to perform its job
     *
     * @var bool
     */
    protected $requestAdminPermissions = false;

    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    public function __construct()
    {
        trigger_error('Extbase Command Controllers will be removed in TYPO3 v10.0. Migrate to symfony/console commands instead.', E_USER_DEPRECATED);
    }

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
     */
    public function injectReflectionService(ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Checks if the current request type is supported by the controller.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The current request
     * @return bool TRUE if this request type is supported, otherwise FALSE
     */
    public function canProcessRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request)
    {
        return $request instanceof Request;
    }

    /**
     * Processes a command line request.
     *
     * @param RequestInterface $request The request object
     * @param ResponseInterface $response The response, modified by this handler
     * @throws UnsupportedRequestTypeException if the controller doesn't support the current request type
     */
    public function processRequest(RequestInterface $request, ResponseInterface $response)
    {
        if (!$this->canProcessRequest($request)) {
            throw new UnsupportedRequestTypeException(sprintf('%s only supports command line requests – requests of type "%s" given.', static::class, get_class($request)), 1300787096);
        }

        $this->request = $request;
        $this->request->setDispatched(true);
        $this->response = $response;

        $this->commandMethodName = $this->resolveCommandMethodName();
        $this->output = $this->objectManager->get(ConsoleOutput::class);
        $this->arguments = $this->objectManager->get(Arguments::class);
        $this->initializeCommandMethodArguments();
        $this->mapRequestArgumentsToControllerArguments();
        $this->initializeBackendAuthentication();
        $this->callCommandMethod();
    }

    /**
     * Resolves and checks the current command method name
     *
     * Note: The resulting command method name might not have the correct case, which isn't a problem because PHP is
     * case insensitive regarding method names.
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchCommandException
     * @return string Method name of the current command
     * @throws NoSuchCommandException
     */
    protected function resolveCommandMethodName()
    {
        $commandMethodName = $this->request->getControllerCommandName() . 'Command';
        if (!is_callable([$this, $commandMethodName])) {
            throw new NoSuchCommandException(sprintf('A command method "%s()" does not exist in controller "%s".', $commandMethodName, static::class), 1300902143);
        }
        return $commandMethodName;
    }

    /**
     * Initializes the arguments array of this controller by creating an empty argument object for each of the
     * method arguments found in the designated command method.
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException
     * @throws InvalidArgumentTypeException
     */
    protected function initializeCommandMethodArguments()
    {
        $methodParameters = $this->reflectionService
            ->getClassSchema(static::class)
            ->getMethod($this->commandMethodName)['params'] ?? [];

        foreach ($methodParameters as $parameterName => $parameterInfo) {
            $dataType = null;
            if (isset($parameterInfo['type'])) {
                $dataType = $parameterInfo['type'];
            } elseif ($parameterInfo['array']) {
                $dataType = 'array';
            }
            if ($dataType === null) {
                throw new InvalidArgumentTypeException(sprintf('The argument type for parameter $%s of method %s->%s() could not be detected.', $parameterName, static::class, $this->commandMethodName), 1306755296);
            }
            $defaultValue = ($parameterInfo['defaultValue'] ?? null);
            $this->arguments->addNewArgument($parameterName, $dataType, $parameterInfo['optional'] === false, $defaultValue);
        }
    }

    /**
     * Maps arguments delivered by the request object to the local controller arguments.
     */
    protected function mapRequestArgumentsToControllerArguments()
    {
        /** @var Argument $argument */
        foreach ($this->arguments as $argument) {
            $argumentName = $argument->getName();
            if ($this->request->hasArgument($argumentName)) {
                $argument->setValue($this->request->getArgument($argumentName));
                continue;
            }
            if (!$argument->isRequired()) {
                continue;
            }
            $argumentValue = null;
            $commandArgumentDefinition = $this->objectManager->get(CommandArgumentDefinition::class, $argumentName, true, null);
            while ($argumentValue === null) {
                $argumentValue = $this->output->ask(sprintf('<comment>Please specify the required argument "%s":</comment> ', $commandArgumentDefinition->getDashedName()));
            }
            $argument->setValue($argumentValue);
        }
    }

    /**
     * Initializes and ensures authenticated backend access
     */
    protected function initializeBackendAuthentication()
    {
        $backendUserAuthentication = $this->getBackendUserAuthentication();
        if ($backendUserAuthentication !== null) {
            $backendUserAuthentication->backendCheckLogin();
        }
    }

    /**
     * Forwards the request to another command and / or CommandController.
     *
     * Request is directly transferred to the other command / controller
     * without the need for a new request.
     *
     * @param string $commandName
     * @param string $controllerObjectName
     * @param array $arguments
     * @throws StopActionException
     */
    protected function forward($commandName, $controllerObjectName = null, array $arguments = [])
    {
        $this->request->setDispatched(false);
        $this->request->setControllerCommandName($commandName);
        if ($controllerObjectName !== null) {
            $this->request->setControllerObjectName($controllerObjectName);
        }
        $this->request->setArguments($arguments);

        $this->arguments->removeAll();
        throw new StopActionException('forward', 1476107661);
    }

    /**
     * Calls the specified command method and passes the arguments.
     *
     * If the command returns a string, it is appended to the content in the
     * response object. If the command doesn't return anything and a valid
     * view exists, the view is rendered automatically.
     */
    protected function callCommandMethod()
    {
        $preparedArguments = [];
        /** @var Argument $argument */
        foreach ($this->arguments as $argument) {
            $preparedArguments[] = $argument->getValue();
        }
        $commandResult = call_user_func_array([$this, $this->commandMethodName], $preparedArguments);
        if (is_string($commandResult) && $commandResult !== '') {
            $this->response->appendContent($commandResult);
        } elseif (is_object($commandResult) && method_exists($commandResult, '__toString')) {
            $this->response->appendContent((string)$commandResult);
        }
    }

    /**
     * Outputs specified text to the console window
     * You can specify arguments that will be passed to the text via sprintf
     *
     * @see http://www.php.net/sprintf
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     */
    protected function output($text, array $arguments = [])
    {
        $this->output->output($text, $arguments);
    }

    /**
     * Outputs specified text to the console window and appends a line break
     *
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     * @see output()
     */
    protected function outputLine($text = '', array $arguments = [])
    {
        $this->output->outputLine($text, $arguments);
    }

    /**
     * Formats the given text to fit into MAXIMUM_LINE_LENGTH and outputs it to the
     * console window
     *
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     * @param int $leftPadding The number of spaces to use for indentation
     * @see outputLine()
     */
    protected function outputFormatted($text = '', array $arguments = [], $leftPadding = 0)
    {
        $this->output->outputFormatted($text, $arguments, $leftPadding);
    }

    /**
     * Exits the CLI through the dispatcher
     * An exit status code can be specified @see http://www.php.net/exit
     *
     * @param int $exitCode Exit code to return on exit
     * @throws StopActionException
     */
    protected function quit($exitCode = 0)
    {
        $this->response->setExitCode($exitCode);
        throw new StopActionException('quit', 1476107681);
    }

    /**
     * Sends the response and exits the CLI without any further code execution
     * Should be used for commands that flush code caches.
     *
     * @param int $exitCode Exit code to return on exit
     */
    protected function sendAndExit($exitCode = 0)
    {
        $this->response->send();
        exit($exitCode);
    }

    /**
     * Returns the global BackendUserAuthentication object.
     *
     * @return BackendUserAuthentication|null
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
