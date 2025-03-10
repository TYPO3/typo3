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

namespace TYPO3\CMS\Fluid\ViewHelpers\Form;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extbase\Service\FileHandlingService;
use TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * ViewHelper which renders a checkbox field used for file upload deletion in Extbase forms.
 *
 * ```
 *   <f:form.uploadDeleteCheckbox id="file" property="file" fileReference="{myModel.file}" />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-form-uploaddeletecheckbox
 */
final class UploadDeleteCheckboxViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'input';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('id', 'string', 'ID of the generated checkbox element');
        $this->registerArgument('property', 'string', 'Name of object property', true);
        $this->registerArgument('fileReference', 'TYPO3\CMS\Extbase\Domain\Model\FileReference', 'The file reference object', true);
    }

    public function render(): string
    {
        /** @var ?FileReference $fileReference */
        $fileReference = $this->arguments['fileReference'];
        $property = $this->arguments['property'];
        $idAttribute = $this->arguments['id'] ?? '';

        // Early return, if no file reference given
        if (!$fileReference instanceof FileReference) {
            return '';
        }

        /** @var HashService $hashService */
        $hashService = GeneralUtility::makeInstance(HashService::class);
        $extensionService = GeneralUtility::makeInstance(ExtensionService::class);

        $this->tag->addAttribute('type', 'checkbox');
        $request = ($this->renderingContext->getAttribute(ServerRequestInterface::class));
        $extbaseRequestParams = $request->getAttribute('extbase');

        $extensionName = $extbaseRequestParams->getControllerExtensionName();
        $pluginName = $extbaseRequestParams->getPluginName();
        if ($extensionName === '' || $pluginName === '') {
            throw new Exception('ExtensionName or PluginName not set in Extbase request', 1719660837);
        }

        $deleteData = [
            'property' => $property,
            'fileReference' => $fileReference->getUid(),
        ];

        $pluginNamespace = $extensionService->getPluginNamespace($extensionName, $pluginName);
        $formObjectName = $this->getFormObjectName();
        $fileReferenceIdentifier = $hashService->hmac($property . $fileReference->getUid(), self::class);
        $nameAttribute = $pluginNamespace . '[' . FileHandlingService::DELETE_IDENTIFIER . ']' .
            '[' . $formObjectName . ']' . '[' . $fileReferenceIdentifier . ']';
        $valueAttribute = $hashService->appendHmac(
            json_encode($deleteData, JSON_THROW_ON_ERROR),
            FileHandlingService::DELETE_IDENTIFIER
        );

        $checked = false;
        if ($this->hasMappingErrorOccurred($extbaseRequestParams)) {
            $checked = $this->getCheckedState($request, $pluginNamespace, $formObjectName, $fileReferenceIdentifier);
        }

        $this->tag->addAttribute('id', $idAttribute);
        $this->tag->addAttribute('name', $nameAttribute);
        $this->tag->addAttribute('value', $valueAttribute);
        if ($checked === true) {
            $this->tag->addAttribute('checked', 'checked');
        }

        return $this->tag->render();
    }

    /**
     * Returns the boolean checked state for the given identifier evaluated from POST data.
     */
    private function getCheckedState(
        ServerRequestInterface $request,
        string $fieldNamePrefix,
        string $formObjectName,
        mixed $identifier
    ): bool {
        return (bool)($request->getParsedBody()[$fieldNamePrefix][FileHandlingService::DELETE_IDENTIFIER][$formObjectName][$identifier] ?? false);
    }

    private function getFormObjectName(): string
    {
        $formObjectName = $this->renderingContext->getViewHelperVariableContainer()->get(
            FormViewHelper::class,
            'formObjectName'
        );

        if (empty($formObjectName)) {
            throw new \RuntimeException('UploadDeleteCheckboxViewHelper can only be used on Fluid form context', 1719655880);
        }

        return $formObjectName;
    }

    /**
     * Checks if a property mapping error has occurred in the last request.
     */
    private function hasMappingErrorOccurred(ExtbaseRequestParameters $extbaseRequest): bool
    {
        return $extbaseRequest->getOriginalRequest() !== null;
    }
}
