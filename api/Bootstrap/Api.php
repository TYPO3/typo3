<?php

class Typo3_Bootstrap_Api {
	
	public static function includeRequiredClasses() {
		require_once PATH_t3lib.'webservice'.DIRECTORY_SEPARATOR.'interface.t3lib_webservice_webserviceinterface.php';
		require_once PATH_t3lib.'interfaces'.DIRECTORY_SEPARATOR.'interface.t3lib_singleton.php';
		require_once PATH_t3lib.'webservice'.DIRECTORY_SEPARATOR.'class.t3lib_webservice_dispatcher.php';
		require_once PATH_t3lib.'webservice'.DIRECTORY_SEPARATOR.'class.t3lib_webservice_request.php';
		require_once PATH_t3lib.'webservice'.DIRECTORY_SEPARATOR.'class.t3lib_webservice_requestbuilder.php';
		require_once PATH_t3lib.'webservice'.DIRECTORY_SEPARATOR.'class.t3lib_webservice_response.php';
		require_once PATH_t3lib.'webservice'.DIRECTORY_SEPARATOR.'class.t3lib_webservice_router.php';
		require_once PATH_t3lib.'webservice'.DIRECTORY_SEPARATOR.'class.t3lib_webservice_uri.php';
		require_once PATH_t3lib.'webservice'.DIRECTORY_SEPARATOR.'class.t3lib_webservice_app.php';
		
		require_once(PATH_t3lib . 'class.t3lib_div.php');
		require_once(PATH_t3lib . 'class.t3lib_extmgm.php');

		require_once(PATH_t3lib.'class.t3lib_cache.php');
		require_once(PATH_t3lib.'cache/class.t3lib_cache_exception.php');
		require_once(PATH_t3lib.'cache/exception/class.t3lib_cache_exception_nosuchcache.php');
		require_once(PATH_t3lib.'cache/exception/class.t3lib_cache_exception_invaliddata.php');
		require_once(PATH_t3lib.'interfaces/interface.t3lib_singleton.php');
		require_once(PATH_t3lib.'cache/class.t3lib_cache_factory.php');
		require_once(PATH_t3lib.'cache/class.t3lib_cache_manager.php');
		require_once(PATH_t3lib.'cache/frontend/interfaces/interface.t3lib_cache_frontend_frontend.php');
		require_once(PATH_t3lib.'cache/frontend/class.t3lib_cache_frontend_abstractfrontend.php');
		require_once(PATH_t3lib.'cache/frontend/class.t3lib_cache_frontend_stringfrontend.php');
		require_once(PATH_t3lib.'cache/frontend/class.t3lib_cache_frontend_phpfrontend.php');
		require_once(PATH_t3lib.'cache/backend/interfaces/interface.t3lib_cache_backend_backend.php');
		require_once(PATH_t3lib.'cache/backend/class.t3lib_cache_backend_abstractbackend.php');
		require_once(PATH_t3lib.'cache/backend/interfaces/interface.t3lib_cache_backend_phpcapablebackend.php');
		require_once(PATH_t3lib.'cache/backend/class.t3lib_cache_backend_filebackend.php');
		require_once(PATH_t3lib.'cache/backend/class.t3lib_cache_backend_nullbackend.php');
		require_once(PATH_typo3.'sysext/extbase/Classes/MVC/View/ViewInterface.php');
		require_once(PATH_typo3.'sysext/extbase/Classes/Core/BootstrapInterface.php');
		require_once(PATH_typo3.'sysext/extbase/Classes/Core/Bootstrap.php');
		require_once(PATH_typo3.'sysext/extbase/Classes/Object/ObjectManagerInterface.php');
		require_once(PATH_typo3.'sysext/extbase/Classes/Object/ObjectManager.php');
		require_once(PATH_typo3.'sysext/extbase/Classes/Object/Container/Container.php');
		require_once(PATH_typo3.'sysext/extbase/Classes/Object/Container/ClassInfoCache.php');
		require_once(PATH_typo3.'sysext/fluid/Classes/View/AbstractTemplateView.php');
		require_once(PATH_typo3.'sysext/fluid/Classes/View/StandaloneView.php');
	}
	
	public static function baseSetup() {
		require_once '..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'/typo3/classes'.DIRECTORY_SEPARATOR.'Bootstrap.php';
		
		Typo3_Bootstrap::getInstance()
			->baseSetup('/api');
	}
	
	public static function initializeCachingFramework() {
		t3lib_cache::initializeCachingFramework();
	}
	
}
