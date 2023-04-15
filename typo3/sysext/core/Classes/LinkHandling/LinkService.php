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

namespace TYPO3\CMS\Core\LinkHandling;

use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownUrnException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class LinkService, responsible to find what kind of resource (type) is used
 * to link to (email, external url, file, page etc)
 * with the possibility to get a system-wide understandable "urn" to identify
 * what type it actually is, based on the scheme or prefix.
 */
class LinkService implements SingletonInterface
{
    public const TYPE_PAGE = 'page';
    public const TYPE_INPAGE = 'inpage';
    public const TYPE_URL = 'url';
    public const TYPE_EMAIL = 'email';
    public const TYPE_TELEPHONE = 'telephone';
    public const TYPE_FILE = 'file';
    public const TYPE_FOLDER = 'folder';
    public const TYPE_RECORD = 'record';
    public const TYPE_UNKNOWN = 'unknown';

    /**
     * All registered LinkHandlers
     *
     * @var LinkHandlingInterface[]
     */
    protected $handlers;

    /**
     * LinkService constructor initializes the registered handlers.
     */
    public function __construct()
    {
        $registeredLinkHandlers = $GLOBALS['TYPO3_CONF_VARS']['SYS']['linkHandler'] ?? [];
        $registeredLinkHandlers = is_array($registeredLinkHandlers) ? $registeredLinkHandlers : [];
        /** @var array<string,class-string> $registeredLinkHandlers */
        if ($registeredLinkHandlers !== []) {
            foreach ($registeredLinkHandlers as $type => $handlerClassName) {
                if (!isset($this->handlers[$type]) || !is_object($this->handlers[$type])) {
                    $handler = GeneralUtility::makeInstance($handlerClassName);
                    if ($handler instanceof LinkHandlingInterface) {
                        $this->handlers[$type] = $handler;
                    }
                }
            }
        }
    }

    /**
     * Part of the typolink construction functionality, called by typoLink()
     * Used to resolve "legacy"-based typolinks and URNs.
     *
     * Tries to get the type of the link from the link parameter
     * could be
     *  - "mailto" an email address
     *  - "url" external URL
     *  - "file" a local file (checked AFTER getPublicUrl() is called)
     *  - "page" a page (integer)
     *
     * Does NOT check if the page exists or the file exists.
     *
     * @param string $linkParameter could be "fileadmin/myfile.jpg", "info@typo3.org", "13" or "http://www.typo3.org"
     * @throws UnknownLinkHandlerException
     */
    public function resolve(string $linkParameter): array
    {
        try {
            // Check if the new syntax with "t3://" is used
            return $this->resolveByStringRepresentation($linkParameter);
        } catch (UnknownUrnException $e) {
            $legacyLinkNotationConverter = GeneralUtility::makeInstance(LegacyLinkNotationConverter::class);
            return $legacyLinkNotationConverter->resolve($linkParameter);
        }
    }

    /**
     * Returns an array with data interpretation of the link target, something like t3://page?uid=23.
     *
     * @throws Exception\UnknownLinkHandlerException
     * @throws Exception\UnknownUrnException
     */
    public function resolveByStringRepresentation(string $urn): array
    {
        // linking to any t3:// syntax
        if (stripos($urn, 't3://') === 0) {
            // lets parse the urn
            $urnParsed = parse_url($urn);
            $type = $urnParsed['host'];
            if (isset($urnParsed['query'])) {
                parse_str(htmlspecialchars_decode($urnParsed['query']), $data);
            } else {
                $data = [];
            }
            $fragment = $urnParsed['fragment'] ?? null;

            if (isset($this->handlers[$type])) {
                $result = $this->handlers[$type]->resolveHandlerData($data);
                $result['type'] = $type;
            } else {
                throw new UnknownLinkHandlerException('LinkHandler for ' . $type . ' was not registered', 1460581769);
            }
            // this was historically named "section"
            if ($fragment) {
                $result['fragment'] = $fragment;
            }
        } elseif (($this->handlers[self::TYPE_URL] ?? false) && PathUtility::hasProtocolAndScheme($urn)) {
            $result = $this->handlers[self::TYPE_URL]->resolveHandlerData(['url' => $urn]);
            $result['type'] = self::TYPE_URL;
        } elseif (($this->handlers[self::TYPE_EMAIL] ?? false) && str_starts_with(strtolower($urn), 'mailto:')) {
            $result = $this->handlers[self::TYPE_EMAIL]->resolveHandlerData(['email' => $urn]);
            $result['type'] = self::TYPE_EMAIL;
        } elseif (($this->handlers[self::TYPE_TELEPHONE] ?? false) && str_starts_with(strtolower($urn), 'tel:')) {
            $result = $this->handlers[self::TYPE_TELEPHONE]->resolveHandlerData(['telephone' => $urn]);
            $result['type'] = self::TYPE_TELEPHONE;
        } else {
            $result = [];
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Link']['resolveByStringRepresentation'] ?? null)) {
                $params = ['urn' => $urn, 'result' => &$result];
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Link']['resolveByStringRepresentation'] as $hookMethod) {
                    $fakeThis = null;
                    GeneralUtility::callUserFunction($hookMethod, $params, $fakeThis);
                }
            }
            if (empty($result) || empty($result['type'])) {
                throw new UnknownUrnException('No valid URN to resolve found', 1457177667);
            }
        }

        // @todo If resolved result (linkDetails) are later used to build an uri using LinkBuilder->build(), it's needed
        //       to have the original $linkParameter in the result array. Otherwise, places may break like e.g. the
        //       DatabaseRecordLinkBuilder. Can we safely set this here directly and avoiding calls before build like
        //       "$linkDetails['typoLinkParameter'] = $redirectTarget;" - e.g. like in the ext:redirects
        //       TYPO3\CMS\Redirects\Service\RedirectService::resolveLinkDetailsFromLinkTarget() and other places.

        return $result;
    }

    /**
     * Returns a string interpretation of the link target, something like
     *
     *  - t3://page?uid=23&my=value#cool
     *  - https://www.typo3.org/
     *  - t3://file?uid=13
     *  - t3://folder?storage=2&identifier=/my/folder/
     *  - mailto:mac@safe.com
     *
     * @param array $parameters
     * @throws Exception\UnknownLinkHandlerException
     */
    public function asString(array $parameters): string
    {
        $linkHandler = $this->handlers[$parameters['type']] ?? null;
        if ($linkHandler !== null) {
            return $this->handlers[$parameters['type']]->asString($parameters);
        }
        if (isset($parameters['url']) && !empty($parameters['url'])) {
            // This usually happens for tel: or other types where a URL is available and the
            // legacy link service could resolve at least something
            return $parameters['url'];
        }
        throw new UnknownLinkHandlerException('No valid handlers found for type: ' . $parameters['type'], 1460629247);
    }
}
