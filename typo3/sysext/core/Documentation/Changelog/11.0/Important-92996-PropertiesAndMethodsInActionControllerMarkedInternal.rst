.. include:: /Includes.rst.txt

==============================================================================
Important: #92996 - Properties and methods in ActionController marked internal
==============================================================================

See :issue:`92996`

Description
===========

Several properties and methods of class :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController` are marked internal
since they are meant to be helper methods for the initialization of the controller and to be called action.
All mentioned properties and methods remain as is until TYPO3 12.0. From then on, they may vanish without deprecation and/or replacement.

Injected services that will be removed from the ActionController can then be manually injected by the user if needed.

The following `properties` are marked `@internal`.

- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::$reflectionService`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::$cacheService`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::$hashService`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::$viewResolver`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::$actionMethodName`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::$signalSlotDispatcher`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::$objectManager`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::$validatorResolver`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::$controllerContext`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::$configurationManager`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController::$propertyMapper`


The following `methods` are marked `@internal`.

- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->injectConfigurationManager()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->injectObjectManager()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->injectSignalSlotDispatcher()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->injectValidatorResolver()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->injectViewResolver()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->injectReflectionService()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->injectCacheService()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->injectHashService()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->injectPropertyMapper()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->initializeActionMethodArguments()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->initializeActionMethodValidators()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->initializeControllerArgumentsBaseValidators()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->processRequest()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->renderAssetsForRequest()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->resolveActionMethodName()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->callActionMethod()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->resolveView()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->setViewConfiguration()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->getViewProperty()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->clearCacheOnError()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->addErrorFlashMessage()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->getErrorFlashMessage()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->forwardToReferringRequest()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->getFlattenedValidationErrorMessage()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->buildControllerContext()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->addBaseUriIfNecessary()`
- :php:`TYPO3\CMS\Extbase\Mvc\Controller\ActionController->mapRequestArgumentsToControllerArguments()`

.. index:: PHP-API, ext:extbase
