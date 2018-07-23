<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Slot;

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

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;

/**
 * @internal
 */
class FilePersistenceSlot implements SingletonInterface
{
    const COMMAND_FILE_ADD = 'fileAdd';
    const COMMAND_FILE_CREATE = 'fileCreate';
    const COMMAND_FILE_MOVE = 'fileMove';
    const COMMAND_FILE_RENAME = 'fileRename';
    const COMMAND_FILE_REPLACE = 'fileReplace';
    const COMMAND_FILE_SET_CONTENTS = 'fileSetContents';

    /**
     * @var array
     */
    protected $definedInvocations = [];

    /**
     * @var array
     */
    protected $allowedInvocations = [];

    /**
     * @param string $content
     * @return string
     */
    public function getContentSignature(string $content): string
    {
        return GeneralUtility::hmac($content);
    }

    /**
     * Defines invocations on command level only depending on the type:
     *
     * + true: whitelist command, takes precedence over $allowedInvocations
     * + false: blacklist command, takes precedence over $allowedInvocations
     * + removes previously definition for particular command
     *
     * @param string $command
     * @param bool|null $type
     */
    public function defineInvocation(string $command, bool $type = null)
    {
        $this->definedInvocations[$command] = $type;
        if ($type === null) {
            unset($this->definedInvocations[$command]);
        }
    }

    /**
     * Allows invocation for a particular combination of command and file
     * identifier. Commands providing new content have have to submit a HMAC
     * signature on the content as well.
     *
     * @param string $command
     * @param string $combinedFileIdentifier
     * @param string $contentSignature
     * @return bool
     * @see getContentSignature
     */
    public function allowInvocation(
        string $command,
        string $combinedFileIdentifier,
        string $contentSignature = null
    ): bool {
        $index = $this->searchAllowedInvocation(
            $command,
            $combinedFileIdentifier,
            $contentSignature
        );

        if ($index !== null) {
            return false;
        }

        $this->allowedInvocations[] = [
            'command' => $command,
            'combinedFileIdentifier' => $combinedFileIdentifier,
            'contentSignature' => $contentSignature,
        ];

        return true;
    }

    /**
     * @param string $fileName
     * @param FolderInterface $targetFolder
     */
    public function onPreFileCreate(string $fileName, FolderInterface $targetFolder): void
    {
        $combinedFileIdentifier = $this->buildCombinedIdentifier(
            $targetFolder,
            $fileName
        );

        $this->assertFileName(
            self::COMMAND_FILE_CREATE,
            $combinedFileIdentifier
        );
    }

    /**
     * @param string $targetFileName
     * @param FolderInterface $targetFolder
     * @param string $sourceFilePath
     */
    public function onPreFileAdd(
        string $targetFileName,
        FolderInterface $targetFolder,
        string $sourceFilePath
    ): void {
        $combinedFileIdentifier = $this->buildCombinedIdentifier(
            $targetFolder,
            $targetFileName
        );
        $this->assertFileName(
            self::COMMAND_FILE_ADD,
            $combinedFileIdentifier,
            file_get_contents($sourceFilePath)
        );
    }

    /**
     * @param FileInterface $file
     * @param string $targetFileName
     */
    public function onPreFileRename(FileInterface $file, string $targetFileName): void
    {
        $combinedFileIdentifier = $this->buildCombinedIdentifier(
            $file->getParentFolder(),
            $targetFileName
        );

        $this->assertFileName(
            self::COMMAND_FILE_RENAME,
            $combinedFileIdentifier
        );
    }

    /**
     * @param FileInterface $file
     * @param string $localFilePath
     */
    public function onPreFileReplace(FileInterface $file, string $localFilePath): void
    {
        $combinedFileIdentifier = $this->buildCombinedIdentifier(
            $file->getParentFolder(),
            $file->getName()
        );

        $this->assertFileName(
            self::COMMAND_FILE_REPLACE,
            $combinedFileIdentifier
        );
    }

    /**
     * @param FileInterface $file
     * @param FolderInterface $targetFolder
     * @param string $targetFileName
     */
    public function onPreFileMove(FileInterface $file, FolderInterface $targetFolder, string $targetFileName): void
    {
        // Skip check, in case file extension would not change during this
        // command. In case e.g. "file.txt" shall be renamed to "file.form.yaml"
        // the invocation still has to be granted.
        // Any file moved to a recycle folder is accepted as well.
        if ($this->isFormDefinition($file->getIdentifier())
            && $this->isFormDefinition($targetFileName)
            || $this->isRecycleFolder($targetFolder)) {
            return;
        }

        $combinedFileIdentifier = $this->buildCombinedIdentifier(
            $targetFolder,
            $targetFileName
        );

        $this->assertFileName(
            self::COMMAND_FILE_MOVE,
            $combinedFileIdentifier
        );
    }

    /**
     * @param FileInterface $file
     * @param mixed $content
     */
    public function onPreFileSetContents(FileInterface $file, $content = null): void
    {
        $combinedFileIdentifier = $this->buildCombinedIdentifier(
            $file->getParentFolder(),
            $file->getName()
        );

        $this->assertFileName(
            self::COMMAND_FILE_SET_CONTENTS,
            $combinedFileIdentifier,
            $content
        );
    }

    /**
     * @param string $command
     * @param string $combinedFileIdentifier
     * @param string $content
     * @throws FormDefinitionPersistenceException
     */
    protected function assertFileName(
        string $command,
        string $combinedFileIdentifier,
        string $content = null
    ): void {
        if (!$this->isFormDefinition($combinedFileIdentifier)) {
            return;
        }

        $definedInvocation = $this->definedInvocations[$command] ?? null;
        // whitelisted command
        if ($definedInvocation === true) {
            return;
        }
        // blacklisted command
        if ($definedInvocation === false) {
            throw new FormDefinitionPersistenceException(
                sprintf(
                    'Persisting form definition "%s" is denied',
                    $combinedFileIdentifier
                ),
                1530281201
            );
        }

        $contentSignature = null;
        if ($content !== null) {
            $contentSignature = $this->getContentSignature((string)$content);
        }
        $allowedInvocationIndex = $this->searchAllowedInvocation(
            $command,
            $combinedFileIdentifier,
            $contentSignature
        );

        if ($allowedInvocationIndex === null) {
            throw new FormDefinitionPersistenceException(
                sprintf(
                    'Persisting form definition "%s" is denied',
                    $combinedFileIdentifier
                ),
                1530281202
            );
        }
        unset($this->allowedInvocations[$allowedInvocationIndex]);
    }

    /**
     * @param string $command
     * @param string $combinedFileIdentifier
     * @param string|null $contentSignature
     * @return int|null
     */
    protected function searchAllowedInvocation(
        string $command,
        string $combinedFileIdentifier,
        string $contentSignature = null
    ): ?int {
        foreach ($this->allowedInvocations as $index => $allowedInvocation) {
            if (
                $command === $allowedInvocation['command']
                && $combinedFileIdentifier === $allowedInvocation['combinedFileIdentifier']
                && $contentSignature === $allowedInvocation['contentSignature']
            ) {
                return $index;
            }
        }
        return null;
    }

    /**
     * @param FolderInterface $folder
     * @param string $fileName
     * @return string
     */
    protected function buildCombinedIdentifier(FolderInterface $folder, string $fileName): string
    {
        return sprintf(
            '%d:%s%s',
            $folder->getStorage()->getUid(),
            $folder->getIdentifier(),
            $fileName
        );
    }

    /**
     * @param string $identifier
     * @return bool
     */
    protected function isFormDefinition(string $identifier): bool
    {
        return StringUtility::endsWith(
            $identifier,
            FormPersistenceManager::FORM_DEFINITION_FILE_EXTENSION
        );
    }

    /**
     * @param FolderInterface $folder
     * @return bool
     */
    protected function isRecycleFolder(FolderInterface $folder): bool
    {
        $role = $folder->getStorage()->getRole($folder);
        return $role === FolderInterface::ROLE_RECYCLER;
    }
}
