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

namespace TYPO3\CMS\IndexedSearch\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\IndexedSearch\FileContentParser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FileContentParserTest extends UnitTestCase
{
    #[Test]
    public function splitPdfInfoDoesNotOverrideValues(): void
    {
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $subject = new FileContentParser();
        $input = '
Title:          BAA010718_Broschüre_Chancen_bieten_V2.indd
Creator:        Adobe InDesign CC 13.0 (Macintosh)
Producer:       Adobe PDF Library 15.0
CreationDate:   Thu Feb 22 15:51:27 2018 CET
ModDate:        Mon Mar 12 12:12:12 2018 CET
Tagged:         no
UserProperties: no
Suspects:       no
Form:           none
JavaScript:     no
Pages:          20
Encrypted:      no
Page size:      595.276 x 841.89 pts (A4)
Page rot:       0
File size:      2292621 bytes
Optimized:      yes
PDF version:    1.3
PDF subtype:    PDF/X-3:2002
    Title:         ISO 15930 - Electronic document file format for prepress digital data exchange (PDF/X)
    Abbreviation:  PDF/X-3:2002
    Subtitle:      Part 3: Complete exchange suitable for colour-managed workflows (PDF/X-3)
    Standard:      ISO 15930-3';
        $input = explode(LF, $input);
        $result = $subject->splitPdfInfo($input);
        self::assertEquals('BAA010718_Broschüre_Chancen_bieten_V2.indd', $result['title']);
    }
}
