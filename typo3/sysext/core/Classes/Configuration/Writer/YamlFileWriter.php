<?php
namespace TYPO3\CMS\Core\Configuration\Writer;

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

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Configuration\Writer\Exception\FileWriteException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A YAML file writer that allows to write YAML files, based on the Symfony/Yaml component
 */
class YamlFileWriter
{

    /**
     * Write a YAML file
     *
     * @param FILE|string $fileName either relative to PATH_site or prefixed with EXT:... or FILE object
     * @param array $content The content
     * @param int $inlineLevel The level where you switch to inline YAML
     * @param int $indent The amount of spaces to use for indentation of nested nodes
     * @param int $flags A bit field of Yaml::DUMP_* constants to customize the dumped YAML string
     * @throws FileWriteException if the file could not be written
     */
    public function save(
        $fileName,
        array $content,
        int $inlineLevel = 99,
        int $indent = 2,
        int $flags = 0
    ) {
        $content = Yaml::dump($content, $inlineLevel, $indent, $flags);

        if ($fileName instanceof File) {
            try {
                $fileName->setContents($content);
            } catch (\RuntimeException $e) {
                throw new FileWriteException($e->getMessage(), 1512582753, $e);
            }
        } else {
            $streamlinedFileName = GeneralUtility::getFileAbsFileName($fileName);
            if (!$streamlinedFileName) {
                throw new \FileWriteException('YAML File "' . $fileName . '" could not be loaded', 1485784248);
            }
            if (!GeneralUtility::writeFile($streamlinedFileName, $content)) {
                $error = error_get_last();
                throw new FileWriteException($error['message'], 1512582929);
            }
        }
    }
}
