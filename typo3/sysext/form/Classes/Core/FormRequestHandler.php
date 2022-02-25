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

namespace TYPO3\CMS\Form\Core;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Factory\FormFactoryInterface;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * This is the actual entry point which takes care of creating and rendering the actual `FormRuntime`.
 * Independent of the current application dispatcher (either the default `FormFrontendController`, any
 * custom Extbase controller or a TypoScript `FLUIDTEMPLATE` cObject), any processing will end up here.
 *
 * Possible scenarios are shown below - the term "uncached" refers to using cObject `USER_INT` instead of `USER`.
 *
 * ## Called as HTTP GET, without having any plugin-specific parameters
 *    e.g. /content-examples/form-elements/form
 *    e.g. /content-examples/form-elements/form?tx_otherplugin=payload&cHash=...
 *    Expected to be cached, since no session state details were provided.
 *
 * ## Called as HTTP GET, but having plugin-specific parameters
 *    e.g. /content-examples/form-elements/form?tx_form_formframework[action]=perform&...&cHash=...
 *    Expected to be uncached, since session state details were provided, which
 *    however were not expected in this scenario.
 *
 * ## Called as HTTP POST, with having plugin-specific parameters
 *    e.g. /content-examples/form-elements/form?tx_form_formframework[action]=perform&...&cHash=...
 *    Expected to be uncached and only requested as HTTP POST - thus, the only action handling the
 *    form sessions and state, as well as finishing the form process by e.g. sending out mail.
 *
 * Scope: frontend
 *
 * @internal
 */
final class FormRequestHandler
{
    private ContainerInterface $container;
    private FormPersistenceManagerInterface $formPersistenceManager;
    private RequestBuilder $extbaseRequestBuilder;
    private ConfigurationManagerInterface $configurationManager;

    /**
     * Back reference to the parent content object
     */
    private ?ContentObjectRenderer $cObj = null;

    public function __construct(
        ContainerInterface $container,
        FormPersistenceManagerInterface $formPersistenceManager,
        RequestBuilder $extbaseRequestBuilder,
        ConfigurationManagerInterface $configurationManager
    ) {
        $this->container = $container;
        $this->formPersistenceManager = $formPersistenceManager;
        $this->extbaseRequestBuilder = $extbaseRequestBuilder;
        $this->configurationManager = $configurationManager;
    }

    /**
     * The userFunc called by ContentObjectRenderer::callUserFunction
     *
     * @param array{
     *     'factoryClass': ?string,
     *     'persistenceIdentifier': ?string,
     *     'configuration': ?array,
     *     'prototypeName': ?string,
     *     'extensionName': ?string,
     *     'pluginName': ?string
     * } $configuration
     * @throws \TYPO3\CMS\Form\Domain\Exception\RenderingException
     */
    public function process(string $content, array $configuration, ?ServerRequestInterface $request = null): string
    {
        $request = $this->buildRequest($request);
        if ($this->cObj === null) {
            $this->cObj = $this->container->get(ContentObjectRenderer::class);
            $this->cObj->setRequest($request);
        }

        $configuration = $this->processConfiguration($configuration);
        $factoryClass = $configuration['factoryClass'];
        $persistenceIdentifier = $configuration['persistenceIdentifier'];
        $rawFormDefinition = $configuration['rawFormDefinition'];
        $prototypeName = $configuration['prototypeName'];
        $extensionName = $configuration['extensionName'];
        $pluginName = $configuration['pluginName'];

        /** @var \TYPO3\CMS\Extbase\Mvc\Request $extbaseRequest */
        $extbaseRequest = $this->buildExtbaseRequest($extensionName, $pluginName, $request);
        $formDefinition = $this->buildFormDefinition(
            $this->buildRawFormDefinition($rawFormDefinition, $persistenceIdentifier),
            $prototypeName,
            $factoryClass
        );

        // Force uncached forms in case of a POST request.
        if ($this->shouldRenderUncached($extbaseRequest)
            && $this->cObj->getUserObjectType() !== ContentObjectRenderer::OBJECTTYPE_USER_INT
        ) {
            // After this return, `$this->cObj->convertToUserIntObject()` will force
            // `$this->cObj` to recreate this cObj as type USER_INT which then
            // outputs a `<!--INT_SCRIPT.123456-->'` placeholder.
            $this->cObj->convertToUserIntObject();
            return '';
        }

        // If the controller context does not contain a response object, this invocation is used in a
        // fluid template rendered by the `FluidTemplateContentObject`. Handle the `StopActionException`
        // as there is no extbase dispatcher involved that catches that.
        try {
            return $formDefinition->bind($extbaseRequest)->render();
        } catch (StopActionException $exception) {
            // @deprecated since v11, will be removed in v12: StopActionException is deprecated, drop this catch block.
            // RedirectFinisher for throws a PropagateResponseException instead which bubbles up into Middleware.
            trigger_error(
                'Throwing StopActionException is deprecated. If really needed, throw a (internal)'
                    . ' PropagateResponseException instead, for now. Note this is subject to change.',
                E_USER_DEPRECATED
            );
            return $exception->getResponse()->getBody()->getContents();
        }
    }

    /**
     * Public setter used by ContentObjectRenderer::callUserFunction to set cObj
     */
    public function setContentObjectRenderer(ContentObjectRenderer $contentObjectRenderer): void
    {
        $this->cObj = $contentObjectRenderer;
    }

    /**
     * Determines, whether to render uncached/cached by checking request method being POST
     */
    private function shouldRenderUncached(ServerRequestInterface $extbaseRequest): bool
    {
        $extbaseParameters = $extbaseRequest->getAttribute('extbase');

        // "not GET" covers "POST", but any other HTTP methods
        return $extbaseRequest->getMethod() !== 'GET'
            // HTTP GET with having arguments for domain triggers uncached state as well
            || $extbaseRequest->getMethod() === 'GET' && !empty($extbaseParameters->getArguments());
    }

    /**
     * @param array{
     *     'factoryClass': ?string,
     *     'persistenceIdentifier': ?string,
     *     'configuration': ?array,
     *     'prototypeName': ?string,
     *     'extensionName': ?string,
     *     'pluginName': ?string
     * } $configuration
     * @return array{
     *     'factoryClass': string,
     *     'persistenceIdentifier': string,
     *     'rawFormDefinition': array,
     *     'prototypeName': string,
     *     'extensionName': string,
     *     'pluginName': string
     * }
     */
    private function processConfiguration(array $configuration): array
    {
        // $configuration is generated by \TYPO3\CMS\Form\ViewHelpers\RenderViewHelper
        // Note that the empty() check is required because of the $configuration properties
        // can be empty strings instead of null, so `??` can't be used here.
        $factoryClass = empty($configuration['factoryClass']) ? ArrayFormFactory::class : $configuration['factoryClass'];
        $persistenceIdentifier = empty($configuration['persistenceIdentifier']) ? null : $configuration['persistenceIdentifier'];
        $rawFormDefinition = empty($configuration['configuration']) ? [] : $configuration['configuration'];
        $prototypeName = empty($configuration['prototypeName']) ? null : $configuration['prototypeName'];
        $extensionName = empty($configuration['extensionName']) ? 'Form' : $configuration['extensionName'];
        $pluginName = empty($configuration['pluginName']) ? 'Formframework' : $configuration['pluginName'];

        if (empty($prototypeName)) {
            $prototypeName = $rawFormDefinition['prototypeName'] ?? 'standard';
        }

        return [
            'factoryClass' => $factoryClass,
            'persistenceIdentifier' => $persistenceIdentifier,
            'rawFormDefinition' => $rawFormDefinition,
            'prototypeName' => $prototypeName,
            'extensionName' => $extensionName,
            'pluginName' => $pluginName,
        ];
    }

    private function buildFormDefinition(
        array $rawFormDefinition,
        string $prototypeName,
        string $factoryClass
    ): FormDefinition {
        if ($this->container->has($factoryClass)) {
            $factory = $this->container->get($factoryClass);
        } else {
            // @deprecated since TYPO3 v11, will be removed in TYPO3 v12.0
            $factory = GeneralUtility::makeInstance($factoryClass);
        }
        /** @var FormFactoryInterface $factory */
        return $factory->build($rawFormDefinition, $prototypeName);
    }

    private function buildRawFormDefinition(
        array $rawFormDefinitionOverride,
        ?string $persistenceIdentifier
    ): array {
        if (empty($persistenceIdentifier)) {
            $rawFormDefinition = $rawFormDefinitionOverride;
        } else {
            $rawFormDefinition = $this->formPersistenceManager->load($persistenceIdentifier);
            ArrayUtility::mergeRecursiveWithOverrule(
                $rawFormDefinition,
                $rawFormDefinitionOverride
            );
            $rawFormDefinition['persistenceIdentifier'] = $persistenceIdentifier;
        }
        return $rawFormDefinition;
    }

    private function buildExtbaseRequest(
        string $extensionName,
        string $pluginName,
        ServerRequestInterface $request
    ): ServerRequestInterface {
        $this->configurationManager->setContentObject($this->cObj);
        $this->configurationManager->setConfiguration([
            'extensionName' => $extensionName,
            'pluginName' => $pluginName,
        ]);

        $extbaseRequest = $this->extbaseRequestBuilder->build($request);
        $extbaseParameters = $extbaseRequest->getAttribute('extbase');
        if (!$extbaseParameters instanceof ExtbaseRequestParameters) {
            throw new \LogicException('Could not resolve Extbase request parameters', 1645721824);
        }

        return $extbaseRequest;
    }

    private function buildRequest(?ServerRequestInterface $request = null): ServerRequestInterface
    {
        $request = $request ?? $GLOBALS['TYPO3_REQUEST'] ?? null;
        if (!$request instanceof ServerRequestInterface) {
            throw new \LogicException('Could not resolve request object', 1645700473);
        }
        return $request;
    }
}
