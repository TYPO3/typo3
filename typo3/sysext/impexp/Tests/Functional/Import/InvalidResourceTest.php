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

namespace TYPO3\CMS\Impexp\Tests\Functional\Import;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Impexp\Tests\Functional\AbstractImportExportTestCase;

final class InvalidResourceTest extends AbstractImportExportTestCase
{
    #[Test]
    public function importRejectsPathTraversalInSha1(): void
    {
        // using '.php' to force FileNameValidator to fail, without
        // executing the FAL addFile operation during the import
        $identifier = '/user_uploads/content.php';
        $identifierHash = sha1($identifier);
        $folderHash = sha1(dirname($identifier));
        $fileName = basename($identifier);
        $content = '<?php phpinfo();';
        $contentB64 = base64_encode($content);
        $contentSha1 = sha1($content);
        $fileId = md5('1:' . $identifierHash);

        $xml = <<<XML
<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3RecordDocument>
    <header type="array">
        <records type="array">
            <table index="sys_file" type="array">
                <rec index="1" type="array">
                    <uid>1</uid>
                </rec>
            </table>
        </records>
    </header>
    <records type="array">
        <tablerow index="sys_file:1" type="array">
            <fieldlist index="data" type="array">
                <field index="uid">1</field>
                <field index="pid">0</field>
                <field index="storage">1</field>
                <field index="identifier">{$identifier}</field>
                <field index="identifier_hash">{$identifierHash}</field>
                <field index="folder_hash">{$folderHash}</field>
                <field index="mime_type">text/plain</field>
                <field index="name">{$fileName}</field>
                <field index="sha1">../traversal.php</field>
                <field index="size">15</field>
            </fieldlist>
            <related index="rels" type="array"></related>
        </tablerow>
    </records>
    <files_fal type="array">
        <file index="{$fileId}" type="array">
            <filename>{$fileName}</filename>
            <filemtime>1782486095</filemtime>
            <content base64="1">{$contentB64}</content>
            <content_sha1>{$contentSha1}</content_sha1>
        </file>
    </files_fal>
</T3RecordDocument>
XML;

        $xmlFile = GeneralUtility::tempnam('import', '.xml');
        GeneralUtility::writeFile($xmlFile, $xml);

        $subject = $this->get(Import::class);
        $importPath = $subject->getOrCreateTemporaryFolderName();
        $traversedPath = dirname($importPath) . '/traversal.php';

        try {
            $subject->setPid(0);
            $subject->loadFile($xmlFile);
            $subject->importData();
        } catch (\Throwable $t) {
            // Any exception from a crafted import is expected.
        }

        // error triggered by invalid SHA1 hash
        self::assertContains(
            'Error: Invalid SHA1 hash format for sys_file record "1"',
            $subject->getErrorLog()
        );

        self::assertFileDoesNotExist($traversedPath, 'Import must not write a file to the path-traversal destination.');
    }
}
