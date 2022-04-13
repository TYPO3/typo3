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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree;

use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\AtImportInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\ConditionElseInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\ConditionInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\ConditionIncludeTyposcriptInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\DefaultTypoScriptMagicKeyInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeTyposcriptInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\SegmentInclude;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ConditionElseLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ConditionLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ConditionStopLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ImportLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\ImportOldLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\LineInterface;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\LineStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;
use TYPO3\CMS\Core\TypoScript\Tokenizer\TokenizerInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper class of TreeBuilder: This class gets a node with a LineStream - a node
 * created from a sys_template 'constants' or 'setup' field, or created from a
 * file import. It then looks for conditions and imports in the attached LineStream
 * and splits the node into child nodes if needed.
 *
 * So while "TreeBuilder" is all about creating includes from sys_template records
 * in correct order, this class takes care of conditions and @import within single
 * source streams.
 *
 * This class has no cache-implementation itself: The higher level class caches
 * include trees of token streams.
 *
 * @internal: Internal tree structure.
 */
final class TreeFromLineStreamBuilder
{
    // 'constants' or 'setup'.
    private string $type;

    public function __construct(
        private readonly FileNameValidator $fileNameValidator,
        private TokenizerInterface $tokenizer,
    ) {
    }

    /**
     * Setting a different Tokenizer than the default injected LossyTokenizer
     * disables caching to ensure backend TypoScript IncludeTrees are never cached!
     */
    public function setTokenizer(TokenizerInterface $tokenizer): void
    {
        $this->tokenizer = $tokenizer;
    }

    public function buildTree(IncludeInterface $node, string $type): void
    {
        if (!in_array($type, ['constants', 'setup'])) {
            throw new \RuntimeException('type must be either constants or setup', 1652741356);
        }
        $previousNode = $node;
        $this->type = $type;
        $givenTokenLineStream = $node->getLineStream();
        $lineStream = new LineStream();
        $childNode = new SegmentInclude();
        $childNode->setIdentifier($node->getIdentifier() . '-segment');
        $childNode->setName($node->getName() . ' Segment');

        foreach ($givenTokenLineStream->getNextLine() as $line) {
            if ($line instanceof ConditionLine && $node instanceof ConditionInclude) {
                // Finish current condition when this line is another condition
                $node->setSplit();
                if (!$lineStream->isEmpty()) {
                    $childNode->setLineStream($lineStream);
                    $node->addChild($childNode);
                    $lineStream = new LineStream();
                }
                $node = $previousNode;
            }

            if ($line instanceof ConditionLine) {
                // A new condition not yet in condition context
                $node->setSplit();
                $conditionValueToken = $line->getTokenValue();
                if (!$lineStream->isEmpty()) {
                    $childNode->setLineStream($lineStream);
                    $node->addChild($childNode);
                    $lineStream = new LineStream();
                }
                $childNode = new ConditionInclude();
                $childNode->setSplit();
                $childNode->setIdentifier($node->getIdentifier() . '-condition');
                $childNode->setName($node->getName() . ' Condition');
                $childNode->setConditionToken($conditionValueToken);
                $lineStream->append($line);
                $childNode->setLineStream($lineStream);
                $node->addChild($childNode);
                $previousNode = $node;
                $node = $childNode;
                $childNode = new SegmentInclude();
                $childNode->setIdentifier($node->getIdentifier() . '-segment');
                $childNode->setName($node->getName() . ' Segment');
                $lineStream = new LineStream();
                continue;
            }

            if (($node instanceof ConditionInclude || $node instanceof ConditionElseInclude)
                && $line instanceof ConditionStopLine
            ) {
                // Finish condition segment due to [end] or [global] line
                $node->setSplit();
                if (!$lineStream->isEmpty()) {
                    $childNode->setLineStream($lineStream);
                    $node->addChild($childNode);
                }
                $node = $previousNode;
                $childNode = new SegmentInclude();
                $childNode->setIdentifier($node->getIdentifier() . '-segment');
                $childNode->setName($node->getName() . ' Segment');
                $lineStream = new LineStream();
                $lineStream->append($line);
                continue;
            }

            if ($node instanceof ConditionInclude && $line instanceof ConditionElseLine) {
                // Active condition into [else] condition
                $node->setSplit();
                if (!$lineStream->isEmpty()) {
                    $childNode->setLineStream($lineStream);
                    $node->addChild($childNode);
                }
                $conditionToken = $node->getConditionToken();
                $node = $previousNode;
                $childNode = new ConditionElseInclude();
                $childNode->setSplit();
                $childNode->setIdentifier($node->getIdentifier() . '-condition-else');
                $childNode->setName($node->getName() . ' Condition Else');
                $childNode->setConditionToken($conditionToken);
                $lineStream = new LineStream();
                $lineStream->append($line);
                $childNode->setLineStream($lineStream);
                $node->addChild($childNode);
                $previousNode = $node;
                $node = $childNode;
                $childNode = new SegmentInclude();
                $childNode->setIdentifier($node->getIdentifier() . '-segment');
                $childNode->setName($node->getName() . ' Segment');
                $lineStream = new LineStream();
                continue;
            }

            if ($line instanceof ImportLine) {
                $node->setSplit();
                $atImportValueToken = $line->getValueToken();
                if (!$lineStream->isEmpty()) {
                    $childNode->setLineStream($lineStream);
                    $node->addChild($childNode);
                    $lineStream = new LineStream();
                }
                $childNode = new SegmentInclude();
                $childNode->setIdentifier($node->getIdentifier() . '-segment');
                $childNode->setName($node->getName() . ' Segment');
                $this->processAtImport($node, $atImportValueToken, $line);
                continue;
            }

            if ($line instanceof ImportOldLine) {
                $node->setSplit();
                $includeTypoScriptValueToken = $line->getValueToken();
                if (!$lineStream->isEmpty()) {
                    $childNode->setLineStream($lineStream);
                    $node->addChild($childNode);
                    $lineStream = new LineStream();
                }
                $childNode = new SegmentInclude();
                $childNode->setIdentifier($node->getIdentifier() . '-segment');
                $childNode->setName($node->getName() . ' Segment');
                $this->processIncludeTyposcript($node, $includeTypoScriptValueToken, $line);
                continue;
            }

            $lineStream->append($line);
        }

        if ($node->isSplit() && !$lineStream->isEmpty()) {
            $childNode->setLineStream($lineStream);
            $node->addChild($childNode);
        }
    }

    /**
     * Process a single '@import'. May add multiple children when '*' wildcards are involved.
     * Warning: Calls buildTree() recursive for each included file.
     * Warning: Calls itself recursive for 'relative' lookups.
     */
    private function processAtImport(IncludeInterface $node, Token $atImportValueToken, LineInterface $atImportLine, string $parentPath = ''): void
    {
        $atImportValue = $atImportValueToken->getValue();
        $triedRelative = false;
        if ($parentPath) {
            $atImportValue = ltrim($atImportValue, './');
            $atImportValue = $parentPath . $atImportValue;
            $triedRelative = true;
        }
        $absoluteFileName = rtrim(GeneralUtility::getFileAbsFileName($atImportValue), '/');
        if ($absoluteFileName === '') {
            return;
        }
        if (is_file($absoluteFileName)) {
            // Simple file
            if ($this->fileNameValidator->isValid($absoluteFileName)) {
                $this->addSingleAtImportFile($node, $absoluteFileName, $atImportValue, $atImportLine);
                $this->addStaticMagicFromGlobals($node, $atImportValue);
            }
        } elseif (is_dir($absoluteFileName)) {
            // Directories with and without ending /
            $filesAndDirs = scandir($absoluteFileName);
            foreach ($filesAndDirs as $potentialInclude) {
                if (!str_ends_with($potentialInclude, '.typoscript')
                    || is_dir($absoluteFileName . '/' . $potentialInclude)
                    || !$this->fileNameValidator->isValid($absoluteFileName . '/' . $potentialInclude)
                ) {
                    continue;
                }
                $singleAbsoluteFileName = $absoluteFileName . '/' . $potentialInclude;
                $identifier = rtrim($atImportValue, '/') . '/' . $potentialInclude;
                $this->addSingleAtImportFile($node, $singleAbsoluteFileName, $identifier, $atImportLine);
                $this->addStaticMagicFromGlobals($node, $identifier);
            }
        } elseif (is_file($absoluteFileName . '.typoscript')) {
            // Simple file without .typoscript ending
            if ($this->fileNameValidator->isValid($absoluteFileName . '.typoscript')) {
                $singleAbsoluteFileName = $absoluteFileName . '.typoscript';
                $identifier = $atImportValue . '.typoscript';
                $this->addSingleAtImportFile($node, $singleAbsoluteFileName, $identifier, $atImportLine);
                $this->addStaticMagicFromGlobals($node, $identifier);
            }
        } elseif (str_contains($absoluteFileName, '*')) {
            // Something with * in file part
            $directory = rtrim(dirname($absoluteFileName) . '/');
            $filePattern = basename($absoluteFileName);
            if (!is_dir($directory) || !str_contains($filePattern, '*')) {
                return;
            }
            if (str_ends_with($absoluteFileName, '*')) {
                $filePattern = basename($absoluteFileName, '*');
            } elseif (str_ends_with($filePattern, '*typoscript')) {
                $filePattern = mb_substr($filePattern, 0, -11);
            } elseif (str_ends_with($filePattern, '*.typoscript')) {
                $filePattern = mb_substr($filePattern, 0, -12);
            }
            $filesAndDirs = scandir($directory);
            foreach ($filesAndDirs as $potentialInclude) {
                if (!str_starts_with($potentialInclude, $filePattern)
                    || !str_ends_with($potentialInclude, '.typoscript')
                    || is_dir($directory . $potentialInclude)
                    || !$this->fileNameValidator->isValid($directory . $potentialInclude)
                ) {
                    continue;
                }
                $singleAbsoluteFileName = $directory . $potentialInclude;
                $identifier = rtrim(dirname($atImportValue), '/') . '/' . $potentialInclude;
                $this->addSingleAtImportFile($node, $singleAbsoluteFileName, $identifier, $atImportLine);
                $this->addStaticMagicFromGlobals($node, $identifier);
            }
        } elseif (!$triedRelative) {
            // See if we can import relative "./foo.typoscript" or "foo.typoscript"
            $parentPath = rtrim(dirname($node->getIdentifier()), '/') . '/';
            $this->processAtImport($node, $atImportValueToken, $atImportLine, $parentPath);
        }
    }

    /**
     * Get content of a single @import file and add to current node as child.
     *
     * Warning: Recursively calls buildTree() to process includes of included content.
     */
    private function addSingleAtImportFile(IncludeInterface $node, string $absoluteFileName, string $identifier, LineInterface $atImportLine): void
    {
        $content = file_get_contents($absoluteFileName);
        $newNode = new AtImportInclude();
        $newNode->setIdentifier($identifier);
        $newNode->setName($identifier);
        $newNode->setLineStream($this->tokenizer->tokenize($content));
        $newNode->setOriginalLine($atImportLine);
        $this->buildTree($newNode, $this->type);
        $node->addChild($newNode);
    }

    private function processIncludeTyposcript(IncludeInterface $node, Token $includeTyposcriptValueToken, LineInterface $importKeywordOldLine): void
    {
        $fullString = $includeTyposcriptValueToken->getValue();
        $potentialSourceArray = preg_split('#.*(source="[^"]*").*|>#', $fullString, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
        $source = '';
        foreach ($potentialSourceArray as $candidate) {
            $candidate = trim($candidate);
            if (str_starts_with($candidate, 'source="')) {
                $source = rtrim(substr($candidate, 8), '"');
                $source = str_replace([' ', "\t"], '', $source);
                break;
            }
        }
        if (empty($source)) {
            // No 'source="..."'
            return;
        }
        $potentialConditionArray = preg_split('#.*(condition="(?:\\\\\\\\|\\\\"|[^\"])*").*|>#', $fullString, 2, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
        $condition = '';
        foreach ($potentialConditionArray as $candidate) {
            $candidate = trim($candidate);
            if (str_starts_with($candidate, 'condition="')) {
                $candidate = trim(substr($candidate, 10), '"');
                if (str_starts_with($candidate, '[') && str_ends_with($candidate, ']')) {
                    // Cut off '[' and ']' if exist.
                    $candidate = mb_substr($candidate, 1, -1);
                }
                $condition = stripslashes($candidate);
                break;
            }
        }
        $potentialExtensionsArray = preg_split('#.*(extensions*="[^"]*").*|>#', $fullString, 2, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
        $extensions = [];
        foreach ($potentialExtensionsArray as $candidate) {
            $candidate = trim($candidate);
            if (str_starts_with($candidate, 'extensions="')) {
                $extensions = GeneralUtility::trimExplode(',', rtrim(substr($candidate, 12), '"'), true);
                break;
            }
        }

        if (str_starts_with($source, 'FILE:./')) {
            // Single relative file include
            $fileName = dirname($node->getIdentifier()) . '/' . substr($source, 7);
            $absoluteFileName = rtrim(GeneralUtility::getFileAbsFileName($fileName), '/');
            if ($absoluteFileName === '') {
                return;
            }
            if ($this->fileNameValidator->isValid($absoluteFileName) && is_file($absoluteFileName)) {
                $nodeToAddTo = $this->processConditionalIncludeTyposcript($node, $condition, $fileName);
                $this->addSingleIncludeTyposcriptFile($nodeToAddTo, $absoluteFileName, $fileName, $importKeywordOldLine);
            }
        } elseif (str_starts_with($source, 'FILE:')) {
            // Single file include, either prefixed with EXT:, or relative to public dir
            // Throw away FILE:, then resolve EXT: or public dir relative
            $fileName = substr($source, 5);
            $absoluteFileName = rtrim(GeneralUtility::getFileAbsFileName($fileName), '/');
            if ($absoluteFileName === '') {
                return;
            }
            if ($this->fileNameValidator->isValid($absoluteFileName) && is_file($absoluteFileName)) {
                $nodeToAddTo = $this->processConditionalIncludeTyposcript($node, $condition, $fileName);
                $this->addSingleIncludeTyposcriptFile($nodeToAddTo, $absoluteFileName, $fileName, $importKeywordOldLine);
                $this->addStaticMagicFromGlobals($nodeToAddTo, $fileName);
            }
        } elseif (str_starts_with($source, 'DIR:')) {
            // Single file include, either prefixed with EXT:, or relative to public dir
            // Throw away FILE:, then resolve EXT: or public dir relative
            $dirName = substr($source, 4);
            $absoluteDirName = rtrim(GeneralUtility::getFileAbsFileName($dirName), '/');
            if ($absoluteDirName === '' || !is_dir($absoluteDirName)) {
                return;
            }
            $filesAndDirs = scandir($absoluteDirName);
            $subDirs = [];
            $nodeToAddTo = $this->processConditionalIncludeTyposcript($node, $condition, $dirName);
            foreach ($filesAndDirs as $potentialInclude) {
                // Handle files in this dir and remember possible sub-dirs
                if ($potentialInclude === '.' || $potentialInclude === '..') {
                    continue;
                }
                if (is_dir($absoluteDirName . '/' . $potentialInclude)) {
                    $subDirs[] = $potentialInclude;
                    continue;
                }
                if (!$this->fileNameValidator->isValid($absoluteDirName . '/' . $potentialInclude)) {
                    continue;
                }
                if (!empty($extensions)) {
                    // Check if file is allowed by allowed 'extensions' setting if given
                    $fileEnding = GeneralUtility::revExplode('.', $potentialInclude, 2)[1] ?? null;
                    if (!$fileEnding || !in_array($fileEnding, $extensions)) {
                        continue;
                    }
                }
                $identifier = rtrim($dirName, '/') . '/' . $potentialInclude;
                $absoluteFileName = $absoluteDirName . '/' . $potentialInclude;
                $this->addSingleIncludeTyposcriptFile($nodeToAddTo, $absoluteFileName, $identifier, $importKeywordOldLine);
            }
            foreach ($subDirs as $subDir) {
                // Handle found subdirectories and include for these, too.
                $filesAndDirs = scandir($absoluteDirName . '/' . $subDir);
                foreach ($filesAndDirs as $potentialInclude) {
                    if ($potentialInclude === '.' || $potentialInclude === '..') {
                        continue;
                    }
                    if (!is_file($absoluteDirName . '/' . $subDir . '/' . $potentialInclude)
                        || !$this->fileNameValidator->isValid($absoluteDirName . '/' . $subDir . $potentialInclude)
                    ) {
                        continue;
                    }
                    $absoluteFileName = $absoluteDirName . '/' . $subDir . '/' . $potentialInclude;
                    $identifier = rtrim($dirName, '/') . '/' . $subDir . '/' . $potentialInclude;
                    $this->addSingleIncludeTyposcriptFile($nodeToAddTo, $absoluteFileName, $identifier, $importKeywordOldLine);
                }
            }
        }
    }

    /**
     * When 'INCLUDE_TYPOSCRIPT' has a 'condition="..."' attribute, we create an additional
     * ConditionIncludeTyposcriptInclude node the included file is added as child to.
     * The method either returns current parent node if there is no condition, or the new
     * conditional sub node, if there is one.
     */
    private function processConditionalIncludeTyposcript(IncludeInterface $node, ?string $condition, string $fileName): IncludeInterface
    {
        $nodeToAddTo = $node;
        if ($condition) {
            $conditionNode = new ConditionIncludeTyposcriptInclude();
            $conditionNode->setIdentifier($fileName . '-condition');
            $conditionNode->setName($fileName . ' Condition');
            $conditionNode->setConditionToken(new Token(TokenType::T_VALUE, $condition, 0, 0));
            $conditionNode->setSplit();
            $nodeToAddTo->addChild($conditionNode);
            $nodeToAddTo = $conditionNode;
        }
        return $nodeToAddTo;
    }

    /**
     * Get content of a single INCLUDE_TYPOSCRIPT file and add to current node as child.
     *
     * Warning: Recursively calls buildTree() to process includes of included content.
     */
    private function addSingleIncludeTyposcriptFile(IncludeInterface $node, string $absoluteFileName, string $identifier, LineInterface $importKeywordOldLine): void
    {
        $content = file_get_contents($absoluteFileName);
        $newNode = new IncludeTyposcriptInclude();
        $newNode->setIdentifier($identifier);
        $newNode->setName($identifier);
        $newNode->setLineStream($this->tokenizer->tokenize($content));
        $newNode->setOriginalLine($importKeywordOldLine);
        $this->buildTree($newNode, $this->type);
        $node->addChild($newNode);
    }

    /**
     * A rather weird lookup in $GLOBALS['TYPO3_CONF_VARS']['FE'] for magic includes.
     * See ExtensionManagementUtility::addTypoScript() for more details on this.
     * Warning: Yes, this is recursive again.
     */
    private function addStaticMagicFromGlobals(IncludeInterface $parentNode, string $identifier): void
    {
        if (!str_starts_with($identifier, 'EXT:')) {
            return;
        }
        $includeStaticFileWithoutExt = substr($identifier, 4);
        $includeStaticFileExtKeyAndPath = GeneralUtility::trimExplode('/', $includeStaticFileWithoutExt, true, 2);
        $extensionKey = $includeStaticFileExtKeyAndPath[0];
        $extensionKeyWithoutUnderscores = str_replace('_', '', $extensionKey);
        if (!$extensionKeyWithoutUnderscores || !ExtensionManagementUtility::isLoaded($extensionKey)) {
            return;
        }
        // example: 'Configuration/TypoScript/MyStaticInclude/'
        $pathSegmentWithAppendedSlash = rtrim(dirname($includeStaticFileExtKeyAndPath[1])) . '/';
        $file = basename($identifier);
        $type = GeneralUtility::trimExplode('.', $file, false, 2)[0] ?? '';
        if ($type !== $this->type) {
            return;
        }
        $globalsLookup = $extensionKeyWithoutUnderscores . '/' . $pathSegmentWithAppendedSlash;
        // If this is a template of type "default content rendering", see if other extensions have added their TypoScript that should be included.
        if (in_array($globalsLookup, $GLOBALS['TYPO3_CONF_VARS']['FE']['contentRenderingTemplates'], true)) {
            $source = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_' . $type . '.']['defaultContentRendering'] ?? null;
            if (!empty($source)) {
                $node = new DefaultTypoScriptMagicKeyInclude();
                $node->setIdentifier('globals-defaultTypoScript-' . $type . '-defaultContentRendering-' . $identifier);
                $node->setName('TYPO3_CONF_VARS defaultContentRendering for ' . $identifier);
                $node->setLineStream($this->tokenizer->tokenize($source));
                $this->buildTree($node, $this->type);
                $parentNode->addChild($node);
            }
        }
    }
}
