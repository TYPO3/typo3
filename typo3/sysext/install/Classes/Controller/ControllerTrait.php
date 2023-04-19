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

namespace TYPO3\CMS\Install\Controller;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Policy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This class is a specific implementation and is not considered part of the Public TYPO3 API.
 */
trait ControllerTrait
{
    /**
     * Using fixed Content-Security-Policy for Admin Tool (extensions and database might not be available)
     */
    protected function createContentSecurityPolicy(): Policy
    {
        return GeneralUtility::makeInstance(Policy::class)
            ->default(SourceKeyword::self)
            // script-src 'nonce-...' required for importmaps
            ->extend(Directive::ScriptSrc, SourceKeyword::nonceProxy)
            // `style-src 'unsafe-inline'` required for lit in safari and firefox to allow inline <style> tags
            // (for browsers that do not support https://caniuse.com/mdn-api_shadowroot_adoptedstylesheets)
            ->extend(Directive::StyleSrc, SourceKeyword::unsafeInline)
            ->set(Directive::StyleSrcAttr, SourceKeyword::unsafeInline)
            ->extend(Directive::ImgSrc, SourceScheme::data)
            // `frame-src blob:` required for es-module-shims blob: URLs
            ->extend(Directive::FrameSrc, SourceScheme::blob);
    }
}
