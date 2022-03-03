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

use ExtbaseTeam\ActionControllerArgumentTest\Controller\ArgumentTestController;
use ExtbaseTeam\ActionControllerArgumentTest\Domain\Model\Model;
use ExtbaseTeam\ActionControllerArgumentTest\Domain\Model\ModelDto;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Dispatcher;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class ActionControllerArgumentTest extends FunctionalTestCase
{
    private string $pluginName = 'Pi1';
    private string $extensionName = 'ActionControllerArgumentTest';
    private ?string $pluginNamespacePrefix = null;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Mvc/Controller/Fixture/Extension/action_controller_argument_test',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->pluginNamespacePrefix = strtolower('tx_' . $this->extensionName . '_' . $this->pluginName);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
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
    public function validationErrorReturnsToForwardedPreviousAction(string $forwardTargetAction, array $forwardTargetArguments, string $validateAction, array $expectations): void
    {
        // trigger action to forward to some `input*` action
        $controller = $this->buildController();
        $controller->declareForwardTargetAction($forwardTargetAction);
        $controller->declareForwardTargetArguments($forwardTargetArguments);

        $inputRequest = $this->buildRequest('forward');
        $inputResponse = $this->dispatch($controller, $inputRequest);

        $body = $inputResponse->getBody();
        $body->rewind();
        $inputDocument = $this->createDocument($body->getContents());
        $parsedInputData = $this->parseDataFromResponseDocument($inputDocument);
        self::assertNotEmpty($parsedInputData['form'] ?? null);
        unset($inputRequest, $controller);

        // trigger `validate*` action with generated arguments from FormViewHelper (see template)
        $controller = $this->buildController();
        $validateRequest = $this->buildRequest($validateAction, $parsedInputData['form']);

        // dispatch request to `validate*` action
        $validateResponse = $this->dispatch($controller, $validateRequest);
        $body = $validateResponse->getBody();
        $body->rewind();
        $validateDocument = $this->createDocument($body->getContents());
        $parsedValidateData = $this->parseDataFromResponseDocument($validateDocument);
        foreach ($expectations ?? [] as $bodyPath => $bodyValue) {
            self::assertSame($bodyValue, ArrayUtility::getValueByPath($parsedValidateData, $bodyPath));
        }
    }

    private function dispatch(ArgumentTestController $controller, RequestInterface $request): ResponseInterface
    {
        $isDispatched = false;
        while (!$isDispatched) {
            $response = $controller->processRequest($request);
            if ($response instanceof ForwardResponse) {
                $request = Dispatcher::buildRequestFromCurrentRequestAndForwardResponse($request, $response);
                $controller = $this->buildController();
                return $controller->processRequest($request);
            }
            $isDispatched = true;
        }
        return $response;
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
        $request = new Request();
        $request->setPluginName($this->pluginName);
        $request->setControllerExtensionName($this->extensionName);
        $request->setControllerName('ArgumentTest');
        $request->setFormat('html');
        $request->setControllerActionName($actionName);
        if ($arguments !== null) {
            $request->setArguments($arguments);
        }
        return $request;
    }

    private function buildController(): ArgumentTestController
    {
        return $this->get(ArgumentTestController::class);
    }
}
