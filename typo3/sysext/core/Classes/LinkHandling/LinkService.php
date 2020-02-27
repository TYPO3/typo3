<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\LinkHandling;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LinkService, responsible to find what kind of resource (type) is used
 * to link to (email, external url, file, page etc)
 * with the possibility to get a system-wide understandable "urn" to identify
 * what type it actually is, based on the scheme or prefix.
 */
class LinkService implements SingletonInterface
{
    const TYPE_PAGE = 'page';
    const TYPE_URL = 'url';
    const TYPE_EMAIL = 'email';
    const TYPE_FILE = 'file';
    const TYPE_FOLDER = 'folder';
    const TYPE_RECORD = 'record';
    const TYPE_UNKNOWN = 'unknown';

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
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['linkHandler'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['linkHandler'] as $type => $handler) {
                if (!isset($this->handlers[$type]) || !is_object($this->handlers[$type])) {
                    $this->handlers[$type] = GeneralUtility::makeInstance($handler);
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
     *  - "page" a page (integer or alias)
     *
     * Does NOT check if the page exists or the file exists.
     *
     * @param string $linkParameter could be "fileadmin/myfile.jpg", "info@typo3.org", "13" or "http://www.typo3.org"
     * @return array
     */
    public function resolve(string $linkParameter): array
    {
        try {
            // Check if the new syntax with "t3://" is used
            return $this->resolveByStringRepresentation($linkParameter);
        } catch (Exception\UnknownUrnException $e) {
            $legacyLinkNotationConverter = GeneralUtility::makeInstance(LegacyLinkNotationConverter::class);
            return $legacyLinkNotationConverter->resolve($linkParameter);
        }
    }

    /**
     * Returns an array with data interpretation of the link target, something like t3://page?uid=23.
     *
     * @param string $urn
     * @return array
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

            if (is_object($this->handlers[$type])) {
                $result = $this->handlers[$type]->resolveHandlerData($data);
                $result['type'] = $type;
            } else {
                throw new Exception\UnknownLinkHandlerException('LinkHandler for ' . $type . ' was not registered', 1460581769);
            }
            // this was historically named "section"
            if ($fragment) {
                $result['fragment'] = $fragment;
            }
        } elseif (stripos($urn, '://') && $this->handlers[self::TYPE_URL]) {
            $result = $this->handlers[self::TYPE_URL]->resolveHandlerData(['url' => $urn]);
            $result['type'] = self::TYPE_URL;
        } elseif (stripos($urn, 'mailto:') === 0 && $this->handlers[self::TYPE_EMAIL]) {
            $result = $this->handlers[self::TYPE_EMAIL]->resolveHandlerData(['email' => $urn]);
            $result['type'] = self::TYPE_EMAIL;
        } else {
            $result = [];
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Link']['resolveByStringRepresentation'] ?? null)) {
                $params = ['urn' => $urn, 'result' => &$result];
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Link']['resolveByStringRepresentation'] as $hookMethod) {
                    $fakeThis = false;
                    GeneralUtility::callUserFunction($hookMethod, $params, $fakeThis);
                }
            }
            if (empty($result) || empty($result['type'])) {
                throw new Exception\UnknownUrnException('No valid URN to resolve found', 1457177667);
            }
        }

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
     * @return string
     * @throws Exception\UnknownLinkHandlerException
     */
    public function asString(array $parameters): string
    {
        if (is_object($this->handlers[$parameters['type']])) {
            return $this->handlers[$parameters['type']]->asString($parameters);
        }
        if (isset($parameters['url']) && !empty($parameters['url'])) {
            // This usually happens for tel: or other types where a URL is available and the
            // legacy link service could resolve at least something
            return $parameters['url'];
        }
        throw new Exception\UnknownLinkHandlerException('No valid handlers found for type: ' . $parameters['type'], 1460629247);
    }
}
