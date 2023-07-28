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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy;

/**
 * Representation of Content-Security-Policy directives
 * see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy#directives
 */
enum Directive: string
{
    private const STAND_ALONE = [
        self::Sandbox,
        self::TrustedTypes,
        self::UpgradeInsecureRequests,
    ];

    case DefaultSrc = 'default-src';
    case BaseUri = 'base-uri';
    case ChildSrc = 'child-src';
    case ConnectSrc = 'connect-src';
    case FontSrc = 'font-src';
    case FormAction = 'form-action';
    case FrameAncestors = 'frame-ancestors';
    case FrameSrc = 'frame-src';
    case ImgSrc = 'img-src';
    case ManifestSrc = 'manifest-src';
    case MediaSrc = 'media-src';
    case ObjectSrc = 'object-src';
    // @deprecated (used for Safari, see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/plugin-types)
    case PluginTypes = 'plugin-types';
    case ReportTo = 'report-to';
    // @deprecated (see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/report-uri)
    // but `report-uri` is still used for being compatible other older browsers
    case ReportUri = 'report-uri';
    case RequireTrustedTypesFor = 'require-trusted-types-for';
    case Sandbox = 'sandbox';
    case ScriptSrc = 'script-src';
    case ScriptSrcAttr = 'script-src-attr';
    case ScriptSrcElem = 'script-src-elem';
    case StyleSrc = 'style-src';
    case StyleSrcAttr = 'style-src-attr';
    case StyleSrcElem = 'style-src-elem';
    case TrustedTypes = 'trusted-types';
    case UpgradeInsecureRequests = 'upgrade-insecure-requests';
    case WorkerSrc = 'worker-src';

    /**
     * @return list<self>
     */
    public function getAncestors(): array
    {
        return self::ancestorMap()[$this] ?? [];
    }

    /**
     * Determines whether a mutation for the current directive would be reasonable.
     * For instance, changing the `default-src` or `report-uri` would not qualify.
     */
    public function isMutationReasonable(): bool
    {
        return in_array($this, self::reasonableMutationItems(), true);
    }

    /**
     * Determines whether the current directive can be used without any values,
     * like for instance `sandbox`, `trusted-types` or `upgrade-insecure-requests`.
     */
    public function isStandAlone(): bool
    {
        return in_array($this, self::STAND_ALONE, true);
    }

    /**
     * @return \WeakMap<self, list<self>>
     */
    private static function ancestorMap(): \WeakMap
    {
        /** @var \WeakMap<self, list<self>> $map temporary, internal \WeakMap */
        $map = new \WeakMap();
        $map[self::ChildSrc] = [self::DefaultSrc];
        $map[self::ConnectSrc] = [self::DefaultSrc];
        $map[self::FontSrc] = [self::DefaultSrc];
        $map[self::FrameSrc] = [self::ChildSrc, self::DefaultSrc];
        $map[self::ImgSrc] = [self::DefaultSrc];
        $map[self::ManifestSrc] = [self::DefaultSrc];
        $map[self::MediaSrc] = [self::DefaultSrc];
        $map[self::ObjectSrc] = [self::DefaultSrc];
        $map[self::ScriptSrc] = [self::DefaultSrc];
        $map[self::ScriptSrcAttr] = [self::ScriptSrc, self::DefaultSrc];
        $map[self::ScriptSrcElem] = [self::ScriptSrc, self::DefaultSrc];
        $map[self::StyleSrc] = [self::DefaultSrc];
        $map[self::StyleSrcAttr] = [self::StyleSrc, self::DefaultSrc];
        $map[self::StyleSrcElem] = [self::StyleSrc, self::DefaultSrc];
        $map[self::WorkerSrc] = [self::ChildSrc, self::ScriptSrc, self::DefaultSrc];
        return $map;
    }

    /**
     * @return list<self>
     */
    private static function reasonableMutationItems(): array
    {
        return [
            self::ConnectSrc,
            self::FontSrc,
            self::FrameSrc,
            self::ImgSrc,
            self::MediaSrc,
            self::ScriptSrcElem,
            self::StyleSrcElem,
        ];
    }
}
