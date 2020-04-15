<?php

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

namespace TYPO3\CMS\Core\Tests\Functional\TypoScript\Parser;

use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional test for TypoScriptParser
 */
class TypoScriptParserTest extends FunctionalTestCase
{
    /**
     * This tests triggers an error if the serialize(unserialize())) call
     * within TypoScriptParser is removed. See forge issue #76919
     *
     * @test
     */
    public function hasFlakyReferences()
    {
        $typoScript = implode(LF, [
            '',
            '[GLOBAL]',
            'RTE.default.proc.entryHTMLparser_db = 1',
            'RTE.default.proc.entryHTMLparser_db {',
                'tags {',
                '}',
            '}',

            'RTE.default.FE < RTE.default',

            'RTE.default.enableWordClean = 1',
            'RTE.default.enableWordClean.HTMLparser < RTE.default.proc.entryHTMLparser_db',
            '',
        ]);

        $typoScriptParser = new TypoScriptParser();
        $typoScriptParser->parse($typoScript);
        $res = ['TSconfig' => $typoScriptParser->setup];
        // The issue only pops up if the TS was cached. This call simulates the unserialize(serialize())
        // call done by the cache framework to trigger the issue.
        $res = unserialize(serialize($res));
        $res['TSconfig']['RTE.']['default.']['FE.']['proc.']['entryHTMLparser_db.']['tags.'] = 'This';
        self::assertEquals([], $res['TSconfig']['RTE.']['default.']['proc.']['entryHTMLparser_db.']['tags.']);
    }
}
