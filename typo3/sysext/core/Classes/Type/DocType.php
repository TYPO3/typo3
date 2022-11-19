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

namespace TYPO3\CMS\Core\Type;

/**
 * Enum defining all kinds of values needed to decide on how render HTML or XML-compliant code
 * parts of the Page Rendering.
 *
 * Set to HTML5 by default in Frontend and Backend.
 */
enum DocType
{
    case html5;
    // XHTML 1.0 Strict doctype
    case xhtmlStrict;
    // XHTML 1.1 doctype
    case xhtml11;
    // XHTML 1.0 Transitional doctype
    case xhtmlTransitional;
    // XHTML basic doctype
    case xhtmlBasic;
    // XHTML+RDFa 1.0 doctype
    case xhtmlRdfa10;
    case none;

    /**
     * @return bool true if the specified doctype requires XML Compliance (needed for e.g. self-closing tags,
     * or for the xml:ns attribute).
     */
    public function isXmlCompliant(): bool
    {
        return match ($this) {
            self::xhtmlRdfa10, self::xhtml11, self::xhtmlStrict, self::xhtmlBasic, self::xhtmlTransitional => true,
            default => false,
        };
    }

    public function getDoctypeDeclaration(): string
    {
        return match ($this) {
            self::xhtmlTransitional => '<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
            self::xhtmlStrict => '<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
            self::xhtmlBasic => '<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML Basic 1.0//EN"
    "http://www.w3.org/TR/xhtml-basic/xhtml-basic10.dtd">',
            self::xhtml11 => '<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
            self::xhtmlRdfa10 => '<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN"
    "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">',
            self::html5 => '<!DOCTYPE html>',
            default => ''
        };
    }

    /**
     * @internal only used for backwards-compatibility, and will be removed in TYPO3 v13.0.
     */
    public function getXhtmlDocType(): string
    {
        return match ($this) {
            self::xhtmlTransitional => 'xhtml_trans',
            self::xhtmlStrict => 'xhtml_strict',
            self::xhtmlBasic => 'xhtml_basic',
            self::xhtml11 => 'xhtml_11',
            self::xhtmlRdfa10 => 'xhtml+rdfa_10',
            default => ''
        };
    }
    public function getXmlPrologue(): string
    {
        if ($this->getXhtmlVersion() === 110) {
            return '<?xml version="1.1" encoding="utf-8"?>';
        }
        if ($this->getXhtmlVersion()) {
            return '<?xml version="1.0" encoding="utf-8"?>';
        }
        return '';
    }

    /**
     * @internal only used for backwards-compatibility, and will be set to private in TYPO3 v13.0.
     */
    public function getXhtmlVersion(): ?int
    {
        return match ($this) {
            self::xhtmlTransitional, self::xhtmlStrict => 100,
            self::xhtmlBasic => 105,
            self::xhtml11, self::xhtmlRdfa10 => 110,
            default => null
        };
    }

    public function getMetaCharsetTag(): string
    {
        if ($this->isXmlCompliant()) {
            return '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        }
        if ($this === DocType::html5) {
            // see https://www.w3.org/International/questions/qa-html-encoding-declarations.en.html
            return '<meta charset="utf-8">';
        }
        return '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    }

    /**
     * HTML5 deprecated the "frameborder" attribute as everything should be done via styling.
     */
    public function shouldIncludeFrameBorderAttribute(): bool
    {
        return $this !== self::html5;
    }

    public static function createFromConfigurationKey(?string $key): self
    {
        return match ($key) {
            // config.doctype options and config.xhtmlDoctype
            'xhtml_trans' => self::xhtmlTransitional,
            'xhtml_strict' => self::xhtmlStrict,
            'xhtml_basic' => self::xhtmlBasic,
            'xhtml_11' => self::xhtml11,
            'xhtml+rdfa_10' => self::xhtmlRdfa10,
            'html5' => self::html5,
            'none' => self::none,
            default => self::html5,
        };
    }
}
