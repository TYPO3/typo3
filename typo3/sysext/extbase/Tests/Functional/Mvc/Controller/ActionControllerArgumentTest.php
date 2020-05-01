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

namespace TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Response;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;
use TYPO3\CMS\Extbase\Object\Container\Container;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Controller\ArgumentTestController;
use TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Domain\Model\Model;
use TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Domain\Model\ModelDto;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class ActionControllerArgumentTest extends FunctionalTestCase
{
    private const ENCRYPTION_KEY = '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6';

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Container
     */
    protected $objectContainer;

    private $pluginName;
    private $extensionName;
    private $pluginNamespacePrefix;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginName = 'Pi1';
        $this->extensionName = 'Extbase\\Tests\\Functional\\Mvc\\Controller\\Fixture';
        $this->pluginNamespacePrefix = strtolower('tx_' . $this->extensionName . '_' . $this->pluginName);
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->objectContainer = $this->objectManager->get(Container::class);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = self::ENCRYPTION_KEY;
    }

    protected function tearDown(): void
    {
        unset($this->objectManager, $this->objectContainer);
        unset($this->extensionName, $this->pluginName, $this->pluginNamespacePrefix);
        unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']);
        parent::tearDown();
    }

    public function validationErrorReturnsToForwardedPreviousActionDataProvider(): array
    {
        return [
            // regular models
            'preset model' => [
                'inputPresetModel',
                ['preset' => (new Model())->setValue('preset')],
                'validateModel',
                [
                    'form/model/value' => 'preset',
                    'validationResults/model' => [[]],
                ],
            ],
            'preset DTO' => [
                'inputPresetDto',
                ['preset' => (new ModelDto())->setValue('preset')],
                'validateDto',
                [
                    'form/dto/value' => 'preset',
                    'validationResults/dto' => [[]],
                ],
            ],
        ];
    }

    /**
     * @param string $forwardTargetAction
     * @param array $forwardTargetArguments
     * @param string $validateAction
     * @param array $expectations
     *
     * @test
     * @dataProvider validationErrorReturnsToForwardedPreviousActionDataProvider
     */
    public function validationErrorReturnsToForwardedPreviousAction(string $forwardTargetAction, array $forwardTargetArguments, string $validateAction, array $expectations)
    {
        // trigger action to forward to some `input*` action
        $controller = $this->buildController();
        $controller->declareForwardTargetAction($forwardTargetAction);
        $controller->declareForwardTargetArguments($forwardTargetArguments);

        $inputRequest = $this->buildRequest('forward');
        $inputResponse = $this->buildResponse();
        $this->dispatch($controller, $inputRequest, $inputResponse);

        $inputDocument = $this->createDocument($inputResponse->getContent());
        $parsedInputData = $this->parseDataFromResponseDocument($inputDocument);
        self::assertNotEmpty($parsedInputData['form'] ?? null);
        unset($inputRequest, $controller);

        // trigger `validate*` action with generated arguments from FormViewHelper (see template)
        $controller = $this->buildController();
        $validateRequest = $this->buildRequest($validateAction, $parsedInputData['form']);
        $validateResponse = $this->buildResponse();

        // dispatch request to `validate*` action
        $this->dispatch($controller, $validateRequest, $validateResponse);

        $validateDocument = $this->createDocument($validateResponse->getContent());
        $parsedValidateData = $this->parseDataFromResponseDocument($validateDocument);
        foreach ($expectations ?? [] as $bodyPath => $bodyValue) {
            self::assertSame($bodyValue, ArrayUtility::getValueByPath($parsedValidateData, $bodyPath));
        }
    }

    private function dispatch(ArgumentTestController $controller, RequestInterface $request, ResponseInterface $response): void
    {
        while (!$request->isDispatched()) {
            try {
                $controller->processRequest($request, $response);
            } catch (StopActionException $exception) {
                // simulate Dispatcher::resolveController() using a new controller instance
                $controller = $this->buildController();
            }
        }
    }

    /**
     * Parses result HTML, extracts inflated name/value pairs of `<form>` and validation errors, e.g.
     * `['validationResults' => ..., 'form' => ['value' => ..., '__referrer' => [...]]]`
     *
     * @param \DOMDocument $document
     * @return array
     */
    private function parseDataFromResponseDocument(\DOMDocument $document): array
    {
        $results = [];
        $xpath = new \DOMXPath($document);

        $elements = $xpath->query('//div[@id="validationResults"]');
        if ($elements->count() !== 0) {
            $results['validationResults'] = json_decode(
                trim($elements->item(0)->textContent),
                true
            );
        }

        $elements = $xpath->query('//input[@type="text" or @type="hidden"]');
        foreach ($elements as $element) {
            if (!$element instanceof \DOMElement) {
                continue;
            }
            $results['form'][$element->getAttribute('name')] = $element->getAttribute('value');
        }
        if (!empty($results['form'])) {
            $results['form'] = $this->inflateFormValues($results['form']);
        }
        return $results;
    }

    /**
     * Inflates form values for plugin arguments.
     * `['tx_ext_pi1[aaa][bbb]' => 'value'] --> ['aaa' => ['bbb' => 'value']]`
     *
     * @param array $formValues
     * @return array
     */
    private function inflateFormValues(array $formValues): array
    {
        $inflatedFormValues = [];
        $normalizedFormPaths = array_map(
            function (string $formName) {
                $formName = substr($formName, strlen($this->pluginNamespacePrefix));
                $formName = str_replace('][', '/', trim($formName, '[]'));
                return $formName;
            },
            array_keys($formValues)
        );
        $normalizedFormValues = array_combine($normalizedFormPaths, $formValues);
        foreach ($normalizedFormValues as $formPath => $formValue) {
            $inflatedFormValues = ArrayUtility::setValueByPath($inflatedFormValues, $formPath, $formValue, '/');
        }
        return $inflatedFormValues;
    }

    private function createDocument(string $content): \DOMDocument
    {
        $document = new \DOMDocument();
        $document->loadHTML(
            $content,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
                | LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NONET | LIBXML_NOWARNING
        );
        $document->preserveWhiteSpace = false;
        return $document;
    }

    private function buildRequest(string $actionName, array $arguments = null): Request
    {
        $request = $this->objectManager->get(Request::class);
        $request->setPluginName($this->pluginName);
        $request->setControllerExtensionName($this->extensionName);
        $request->setControllerName('ArgumentTest');
        $request->setMethod('GET');
        $request->setFormat('html');
        $request->setControllerActionName($actionName);
        if ($arguments !== null) {
            $request->setArguments($arguments);
        }
        return $request;
    }

    private function buildResponse(): Response
    {
        return $this->objectManager->get(Response::class);
    }

    private function buildController(): ArgumentTestController
    {
        return $this->objectManager->get(ArgumentTestController::class);
    }
}
