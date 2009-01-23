<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/TX_EXTMVC_Persistence_ObjectStorage.php');

/**
 * The FLOW3 Persistence Manager
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Manager implements TX_EXTMVC_Persistence_ManagerInterface {

	/**
	 * The reflection service
	 *
	 * @var F3_FLOW3_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * The class schema builder
	 *
	 * @var TX_EXTMVC_Persistence_ClassSchemataBuilder
	 */
	protected $classSchemataBuilder;

	/**
	 * @var TX_EXTMVC_Persistence_BackendInterface
	 */
	protected $backend;

	/**
	 * @var TX_EXTMVC_Persistence_Session
	 */
	protected $session;

	/**
	 * @var F3_FLOW3_Object_ManagerInterface
	 */
	protected $objectManager;

	/**
	 * Schemata of all classes which need to be persisted
	 *
	 * @var array of TX_EXTMVC_Persistence_ClassSchema
	 */
	protected $classSchemata = array();

	/**
	 * Constructor
	 *
	 * @param TX_EXTMVC_Persistence_BackendInterface $backend the backend to use for persistence
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(TX_EXTMVC_Persistence_BackendInterface $backend) {
		$this->backend = $backend;
	}

	/**
	 * Injects the reflection service
	 *
	 * @param F3_FLOW3_Reflection_Service $reflectionService The reflection service
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectReflectionService(F3_FLOW3_Reflection_Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the class schemata builder
	 *
	 * @param TX_EXTMVC_Persistence_ClassSchemataBuilder $classSchemataBuilder The class schemata builder
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectClassSchemataBuilder(TX_EXTMVC_Persistence_ClassSchemataBuilder $classSchemataBuilder) {
		$this->classSchemataBuilder = $classSchemataBuilder;
	}

	/**
	 * Injects the persistence session
	 *
	 * @param TX_EXTMVC_Persistence_Session $session The persistence session
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSession(TX_EXTMVC_Persistence_Session $session) {
		$this->session = $session;
	}

	/**
	 * Injects the object manager
	 *
	 * @param F3_FLOW3_Object_ManagerInterface $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(F3_FLOW3_Object_ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Initializes the persistence manager
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize() {
		if (!$this->backend instanceof TX_EXTMVC_Persistence_BackendInterface) throw new TX_EXTMVC_Persistence_Exception_MissingBackend('A persistence backend must be set prior to initializing the persistence manager.', 1215508456);
		$classNames = array_merge($this->reflectionService->getClassNamesByTag('entity'),
			$this->reflectionService->getClassNamesByTag('valueobject'));

		$this->classSchemata = $this->classSchemataBuilder->build($classNames);
		$this->backend->initialize($this->classSchemata);
	}

	/**
	 * Returns the current persistence session
	 *
	 * @return TX_EXTMVC_Persistence_Session
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * Returns the persistence backend
	 *
	 * @return TX_EXTMVC_Persistence_BackendInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getBackend() {
		return $this->backend;
	}

	/**
	 * Returns the class schema for the given class
	 *
	 * @param string $className
	 * @return TX_EXTMVC_Persistence_ClassSchema
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getClassSchema($className) {
		return $this->classSchemata[$className];
	}

	/**
	 * Commits new objects and changes to objects in the current persistence
	 * session into the backend
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo eventually replace foreach/attach with a merge method if added to PHP
	 */
	public function persistAll() {
		$aggregateRootObjects = new TX_EXTMVC_Persistence_ObjectStorage();
		$removedObjects = new TX_EXTMVC_Persistence_ObjectStorage();

			// fetch and inspect objects from all known repositories
		$repositoryClassNames = $this->reflectionService->getAllImplementationClassNamesForInterface('TX_EXTMVC_Persistence_RepositoryInterface');
		foreach ($repositoryClassNames as $repositoryClassName) {
			$repository = $this->objectManager->getObject($repositoryClassName);
			$objects = $repository->getObjects();
			foreach ($objects as $object) {
				$aggregateRootObjects->attach($object);
			}
			$removedObjects = $repository->getRemovedObjects();
			foreach ($removedObjects as $removedObject) {
				$removedObjects->attach($removedObject);
			}
		}
		$reconstitutedObjects = $this->session->getReconstitutedObjects();
		foreach ($reconstitutedObjects as $reconstitutedObject) {
			$aggregateRootObjects->attach($reconstitutedObject);
		}

			// hand in only aggregate roots, leaving handling of subobjects to
			// the underlying storage layer
		$this->backend->setAggregateRootObjects($aggregateRootObjects);
		$this->backend->setDeletedObjects($removedObjects);
		$this->backend->commit();

			// this needs to unregister more than just those, as at least some of
			// the subobjects are supposed to go away as well...
			// OTOH those do no harm, changes to the unused ones should not happen,
			// so all they do is eat some memory.
		foreach($removedObjects as $removedObject) {
			$this->session->unregisterReconstitutedObject($removedObject);
		}
	}
}
?>