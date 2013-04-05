<?php
namespace TYPO3\CMS\Install\CoreUpdates\PackageStatesUpdate;

use TYPO3\CMS\Core\Package\PackageFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\Flow\Annotations as Flow;

/**
 * The default TYPO3 Package Manager
 *
 * @api
 * @Flow\Scope("singleton")
 */
class UpdatePackageManager extends \TYPO3\CMS\Core\Package\PackageManager {

	/**
	 * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @param \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager
	 */
	public function __construct(\TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
		parent::__construct();
	}


	/**
	 * Initializes the package manager
	 *
	 * @param \TYPO3\CMS\Core\Core\Bootstrap $bootstrap The current bootstrap
	 * @param string $packagesBasePath Absolute path of the Packages directory
	 * @param string $packageStatesPathAndFilename
	 * @return void
	 */
	public function createPackageStatesFile(\TYPO3\CMS\Core\Core\Bootstrap $bootstrap, $packagesBasePath = PATH_site, $packageStatesPathAndFilename = '') {

		$this->bootstrap = $bootstrap;
		$this->packagesBasePath = $packagesBasePath;
		$this->packageStatesPathAndFilename = ($packageStatesPathAndFilename === '') ? PATH_typo3conf . 'PackageStates.php' : $packageStatesPathAndFilename;
		$this->packageFactory = new PackageFactory($this);

		$this->loadPackageStates();
		$this->activateProtectedPackagesAndLegacyExtensions();
		$this->sortAndSavePackageStates();
		$this->removeExtensionListsFromConfiguration();
	}

	/**
	 *
	 */
	protected function activateProtectedPackagesAndLegacyExtensions() {
		$packagesToActivate = array();
		// Activate protected/required packages
		foreach ($this->packages as $packageKey => $package) {
			if ($package->isProtected() || (isset($this->packageStatesConfiguration['packages'][$packageKey]['state']) && $this->packageStatesConfiguration['packages'][$packageKey]['state'] === 'active')) {
				$packagesToActivate[$package->getPackageKey()] = $package;
			}
		}
		// Activate legacy extensions
		foreach ($this->getLoadedExtensionKeys() as $loadedExtensionKey) {
			$package = $this->getPackage($loadedExtensionKey);
			$packagesToActivate[$package->getPackageKey()] = $package;
		}
		// Activate dependant packages
		$this->resolvePackageDependencies();
		foreach ($packagesToActivate as $packageKey => $package) {
			foreach ($this->packageStatesConfiguration['packages'][$packageKey]['dependencies'] as $dependantPackageKey) {
				if (!isset($packagesToActivate[$dependantPackageKey])) {
					$dependantPackage = $this->getPackage($dependantPackageKey);
					$packagesToActivate[$dependantPackage->getPackageKey()] = $dependantPackage;
				}
			}
		}
		// Make all active
		foreach ($packagesToActivate as $packageKey => $package) {
			$this->packageStatesConfiguration['packages'][$packageKey]['state'] = 'active';
			$this->activePackages[$packageKey] = $package;
		}
	}

	/**
	 * Loads the states of available packages from the PackageStates.php file.
	 * The result is stored in $this->packageStatesConfiguration.
	 *
	 * @return void
	 */
	protected function loadPackageStates() {
		$this->packageStatesConfiguration = array();
		$this->scanAvailablePackages();
	}

	/**
	 * @return array
	 */
	protected function getLoadedExtensionKeys() {
		try {
			// Extensions in extListArray
			$loadedExtensions = $this->configurationManager->getLocalConfigurationValueByPath('EXT/extListArray');
		} catch (\RuntimeException $exception) {
			// Fallback handling if extlist is still a string and not an array
			// @deprecated since 6.0, will be removed in 6.2
			$loadedExtensions = GeneralUtility::trimExplode(',', $this->configurationManager->getLocalConfigurationValueByPath('EXT/extList'));
		}
		return $loadedExtensions;
	}

	/**
	 *
	 */
	protected function removeExtensionListsFromConfiguration() {
		$this->configurationManager->updateLocalConfiguration(array(
			'EXT' => array(
				'extListArray' => '__UNSET',
				'extList' => '__UNSET',
				'requiredExt' => '__UNSET',
			),
		));
	}

}

?>