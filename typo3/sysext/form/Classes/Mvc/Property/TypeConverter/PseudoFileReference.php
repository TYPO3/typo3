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

namespace TYPO3\CMS\Form\Mvc\Property\TypeConverter;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

/**
 * Use in `UploadedFileReferenceConverter` handling file uploads.
 * `PseudoFile` and `PseudoFileReference` are independent and not associated.
 *
 * This facade hides (potential) internal properties from being exposed to the
 * public during serialization. `sys_file_reference.uid` or `sys_file.uid` are
 * the only aspects used externally. In case of missing integrity checks during
 * deserialization, both properties would allow direct object reference (IDOR).
 *
 * @internal
 */
class PseudoFileReference extends FileReference
{
    /**
     * @var int|null
     */
    private $_uid;

    /**
     * @var int|null
     */
    private $_uidLocal;

    public function __sleep(): array
    {
        // in case this is a persisted file reference, use it directly as reference
        // as a consequence, in-memory changes are lost and have to be persisted first
        // (it seems that in ext:form a `FileReference` was never persisted)
        if ($this->uid > 0) {
            $this->_uid = (int)$this->uid;
            return ['_uid'];
        }
        if ($this->getOriginalResource()->getUid() > 0) {
            $this->_uid = (int)$this->getOriginalResource()->getUid();
            return ['_uid'];
        }
        // in case this is a transient file reference, just expose the associated `sys_file.uid`
        // (based on previous comments, this is the most probably case in ext:form)
        $this->_uidLocal = (int)$this->getOriginalResource()->getOriginalFile()->getUid();
        return ['_uidLocal'];
    }

    public function __wakeup(): void
    {
        $factory = GeneralUtility::makeInstance(ResourceFactory::class);
        if ($this->_uid > 0) {
            $this->originalResource = $factory->getFileReferenceObject($this->_uid);
        } elseif ($this->_uidLocal > 0) {
            $this->originalResource = $factory->createFileReferenceObject([
                'uid_local' => $this->_uidLocal,
                'uid_foreign' => 0,
                'uid' => 0,
                'crop' => null,
            ]);
        } else {
            throw new \LogicException(
                sprintf('Cannot unserialize %s', static::class),
                1613216548
            );
        }
        unset($this->_uid, $this->_uidLocal);
    }
}
