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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extended controller for link browser
 */
class LinkBrowserController extends AbstractLinkBrowserController
{
    public function __construct(
        protected readonly LinkService $linkService,
        protected readonly TypoLinkCodecService $typoLinkCodecService,
        protected readonly FlashMessageService $flashMessageService,
    ) {}

    public function getConfiguration(): array
    {
        $tsConfig = BackendUtility::getPagesTSconfig($this->getCurrentPageId());
        return $tsConfig['TCEMAIN.']['linkHandler.']['page.']['configuration.'] ?? [];
    }

    /**
     * Encode a typolink via ajax.
     * This avoids implementing the encoding functionality again in JS for the browser.
     */
    public function encodeTypoLink(ServerRequestInterface $request): ResponseInterface
    {
        $typoLinkParts = $request->getQueryParams();
        if (isset($typoLinkParts['params'])) {
            $typoLinkParts['additionalParams'] = $typoLinkParts['params'];
            unset($typoLinkParts['params']);
        }
        $typoLink = $this->typoLinkCodecService->encode($typoLinkParts);
        return new JsonResponse(['typoLink' => $typoLink]);
    }

    protected function initDocumentTemplate(): void
    {
        if (!$this->areFieldChangeFunctionsValid() && !$this->areFieldChangeFunctionsValid(true)) {
            $this->parameters['fieldChangeFunc'] = [];
        }
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/backend/form-engine-link-browser-adapter.js')
                // @todo use a proper constructor when migrating to TypeScript
                ->invoke('setOnFieldChangeItems', $this->parameters['fieldChangeFunc'])
        );
    }

    protected function getCurrentPageId(): int
    {
        $pageId = 0;
        $browserParameters = $this->parameters;
        if (isset($browserParameters['pid'])) {
            $pageId = $browserParameters['pid'];
        } elseif (isset($browserParameters['itemName'])) {
            // parse data[<table>][<uid>]
            if (preg_match('~data\[([^]]*)\]\[([^]]*)\]~', $browserParameters['itemName'], $matches)) {
                $recordArray = BackendUtility::getRecord($matches['1'], $matches['2']);
                if (is_array($recordArray)) {
                    $pageId = $recordArray['pid'];
                }
            }
        }
        return (int)BackendUtility::getTSCpidCached($browserParameters['table'], $browserParameters['uid'], $pageId)[0];
    }

    protected function initCurrentUrl(): void
    {
        $currentLink = isset($this->parameters['currentValue']) ? trim($this->parameters['currentValue']) : '';
        /** @var array<string, string> $currentLinkParts */
        $currentLinkParts = $this->typoLinkCodecService->decode($currentLink);
        $currentLinkParts['params'] = $currentLinkParts['additionalParams'];
        unset($currentLinkParts['additionalParams']);

        if (!empty($currentLinkParts['url'])) {
            try {
                $data = $this->linkService->resolve($currentLinkParts['url']);
                $currentLinkParts['type'] = $data['type'];
                unset($data['type']);
                $currentLinkParts['url'] = $data;
            } catch (UnknownLinkHandlerException $e) {
                $this->flashMessageService->getMessageQueueByIdentifier()->enqueue(
                    new FlashMessage(message: $e->getMessage(), severity: ContextualFeedbackSeverity::ERROR)
                );
            }
        }

        $this->currentLinkParts = $currentLinkParts;

        parent::initCurrentUrl();
    }

    /**
     * Determines whether submitted field change functions are valid
     * and are coming from the system and not from an external abuse.
     *
     * @param bool $handleFlexformSections Whether to handle flexform sections differently
     * @return bool Whether the submitted field change functions are valid
     */
    protected function areFieldChangeFunctionsValid(bool $handleFlexformSections = false): bool
    {
        $result = false;
        if (isset($this->parameters['fieldChangeFunc']) && is_array($this->parameters['fieldChangeFunc']) && isset($this->parameters['fieldChangeFuncHash'])) {
            $matches = [];
            $pattern = '#\\[el\\]\\[(([^]-]+-[^]-]+-)(idx\\d+-)([^]]+))\\]#i';
            $fieldChangeFunctions = $this->parameters['fieldChangeFunc'];
            // Special handling of flexform sections:
            // Field change functions are modified in JavaScript, thus the hash is always invalid
            if ($handleFlexformSections && preg_match($pattern, $this->parameters['itemName'], $matches)) {
                $originalName = $matches[1];
                $cleanedName = $matches[2] . $matches[4];
                $fieldChangeFunctions = $this->strReplaceRecursively(
                    $originalName,
                    $cleanedName,
                    $fieldChangeFunctions
                );
            }
            $result = hash_equals(GeneralUtility::hmac(serialize($fieldChangeFunctions), 'backend-link-browser'), $this->parameters['fieldChangeFuncHash']);
        }
        return $result;
    }

    protected function strReplaceRecursively(string $search, string $replace, array $array): array
    {
        foreach ($array as &$item) {
            if (is_array($item)) {
                $item = $this->strReplaceRecursively($search, $replace, $item);
            } else {
                $item = str_replace($search, $replace, $item);
            }
        }
        return $array;
    }
}
