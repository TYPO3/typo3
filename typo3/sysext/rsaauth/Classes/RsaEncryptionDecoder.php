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

/**
 * This class decodes rsa protected data
 */
class RsaEncryptionDecoder implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var Backend\AbstractBackend
	 */
	protected $backend = NULL;

	/**
	 * @var Storage\AbstractStorage
	 */
	protected $storage = NULL;

	/**
	 * @var string
	 */
	protected $key = NULL;

	/**
	 * @param string|array $data
	 * @return string|array
	 */
	public function decrypt($data) {
		if ($this->getKey() === '' || !$this->isAvailable()) {
			return $data;
		}

		$decryptedData = is_array($data) ? $data : array($data);

		foreach ($decryptedData as $key => $value) {
			if (substr($value, 0, 4) !== 'rsa:') {
				continue;
			}

			$decryptedValue = $this->getBackend()->decrypt($this->getKey(), substr($value, 4));
			if ($decryptedValue !== NULL) {
				$decryptedData[$key] = $decryptedValue;
			}
		}
		$this->getStorage()->put(NULL);

		return is_array($data) ? $decryptedData : $decryptedData[0];
	}

	/**
	 * @return bool
	 */
	public function isAvailable() {
		return $this->getBackend() instanceof Backend\AbstractBackend;
	}

	/**
	 * @return string
	 */
	protected function getKey() {
		if ($this->key === NULL) {
			$this->key = $this->getStorage()->get();

			if ($this->key === NULL) {
				$this->key = '';
			}
		}

		return $this->key;
	}

	/**
	 * @return Backend\AbstractBackend|NULL
	 */
	protected function getBackend() {
		if ($this->backend === NULL) {
			$this->backend = Backend\BackendFactory::getBackend();
		}

		return $this->backend;
	}

	/**
	 * @return Storage\AbstractStorage
	 */
	protected function getStorage() {
		if ($this->storage === NULL) {
			$this->storage = Storage\StorageFactory::getStorage();
		}

		return $this->storage;
	}

}
