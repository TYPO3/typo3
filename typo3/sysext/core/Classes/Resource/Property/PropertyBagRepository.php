<?php
namespace TYPO3\CMS\Core\Resource\Property;
use TYPO3\CMS\Core\Resource;


/**
 * Created by JetBrains PhpStorm.
 * User: helmut
 * Date: 09.10.12
 * Time: 14:08
 * To change this template use File | Settings | File Templates.
 */
class PropertyBagRepository implements \TYPO3\CMS\Core\SingletonInterface {


	/**
	 * Context Object which holds information about BE/FE Versioning, Language
	 *
	 * @var
	 */
	protected $context;

	/**
	 * @param  $context
	 */
	public function setContext($context) {
		$this->context = $context;
	}


	public function findByFile(Resource\AbstractFile $file) {
		return $this->createPropertyBagObjects(
			$this->getMetaDataForFile($file)
		);
	}

	public function update(array $propertyBags) {
		foreach ($propertyBags as $propertyBag) {
			if ($propertyBag->getIsDirty()) {
				if ($propertyBag->getName() === 'meta') {

					//TODO: handle version/ langugage overlays

					$uid = $propertyBag['uid'];
					unset($propertyBag['uid']);

					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file_data', 'uid=' . intval($uid), $propertyBag);
				} else {
					//TODO: Handle other bag names
				}
			}
		}
	}

	/**
	 * Retrieves all meta data for a file.
	 * Should respect version and language overlay depending on the context
	 *
	 * @param Resource\AbstractFile $file
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	protected function getMetaDataForFile(Resource\AbstractFile $file) {
		$propertyBagsData = array();
		$fileMetaData = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'sys_file_data', 'uid=' . intval($file->getUid()) . ' AND deleted=0');


		//TODO: Do language and version overlay here!
		//TODO: Signal/Slot to enable extensions to add bags

		if (!empty($fileMetaData)) {
			$propertyBagsData['meta'] = $fileMetaData;
		}
		return $propertyBagsData;
	}

	protected function createPropertyBagObjects(array $fileMetaData) {
		$propertyBags = array();
		foreach ($fileMetaData as $name => $properties) {
			$propertyBags[$name] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Property\\PropertyBag', $name, $properties);
		}

		return $propertyBags;
	}
}
