<?php
namespace TYPO3\CMS\Rsaauth;

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

use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * This class decodes rsa protected data
 */
class RsaEncryptionDecoder implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var Backend\AbstractBackend
     */
    protected $backend = null;

    /**
     * @var Storage\AbstractStorage
     */
    protected $storage = null;

    /**
     * @var string
     */
    protected $key = null;

    /**
     * @param string|array $data
     * @return string|array
     */
    public function decrypt($data)
    {
        if ($this->getKey() === '' || !$this->isAvailable()) {
            return $data;
        }

        $decryptedData = is_array($data) ? $data : [$data];
        $decryptedData = $this->decryptDataArray($decryptedData);
        $this->getStorage()->put(null);

        return is_array($data) ? $decryptedData : $decryptedData[0];
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return $this->getBackend() instanceof Backend\AbstractBackend;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function decryptDataArray(array $data)
    {
        foreach ($data as $key => $value) {
            if (empty($value)) {
                continue;
            }
            if (is_array($value)) {
                $data[$key] = $this->decryptDataArray($value);
                continue;
            }

            if (!StringUtility::beginsWith($value, 'rsa:')) {
                continue;
            }

            $decryptedValue = $this->getBackend()->decrypt($this->getKey(), substr($value, 4));
            if ($decryptedValue !== null) {
                $data[$key] = $decryptedValue;
            }
        }

        return $data;
    }

    /**
     * @return string
     */
    protected function getKey()
    {
        if ($this->key === null) {
            $this->key = $this->getStorage()->get();

            if ($this->key === null) {
                $this->key = '';
            }
        }

        return $this->key;
    }

    /**
     * @return Backend\AbstractBackend|NULL
     */
    protected function getBackend()
    {
        if ($this->backend === null) {
            $this->backend = Backend\BackendFactory::getBackend();
        }

        return $this->backend;
    }

    /**
     * @return Storage\AbstractStorage
     */
    protected function getStorage()
    {
        if ($this->storage === null) {
            $this->storage = Storage\StorageFactory::getStorage();
        }

        return $this->storage;
    }
}
