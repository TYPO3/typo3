<?php
/**
 * Default  TCA_DESCR for "_MOD_tools_em"
 * TYPO3 CVS ID: $Id$
 */

$LOCAL_LANG = Array (
	'default' => Array (
		'emconf_shy.alttitle' => 'Shy',
		'emconf_shy.description' => 'If set, the extension will normally be hidden in the EM because it might be a default extension or otherwise something which is not so important.',
		'emconf_shy.details' => 'Use this flag if an extension is of “rare interest” (which is not the same as un-important - just an extension not sought for very often...)
It does not affect whether or not it\'s enabled. Only display in EM.
Normally “shy” is set for all extensions loaded by default according to TYPO3_CONF_VARS.',
		'emconf_category.alttitle' => 'Category',
		'emconf_category.description' => 'Which category the extension belongs to.',
		'emconf_category.details' => '<b>be:</b> Backend (Generally backend oriented, but not a module) 

<b>module:</b> Backend modules (When something is a module or connects with one)

<b>fe:</b> Frontend (Generally frontend oriented, but not a “true” plugin)

<b>plugin:</b> Frontend plugins (Plugins inserted as a “Insert Plugin” content element) 

<b>misc:</b> Miscellaneous stuff (Where not easily placed elsewhere)

<b>example:</b> Example extension (Which serves as examples etc.)',
		'emconf_dependencies.alttitle' => 'Dependencies of other extensions?',
		'emconf_dependencies.description' => 'This is a list of other extension keys which this extension depends on being loaded BEFORE it self.',
		'emconf_dependencies.details' => 'The EM will manage that dependency while writing the extension list  to localconf.php',
		'emconf_conflicts.alttitle' => 'Conflicts with other extensions?',
		'emconf_conflicts.description' => 'List of extension keys of extensions with which this extension does not work (and so cannot be enabled before those other extensions are un-installed)',
		'emconf_priority.alttitle' => 'Requested Loading priority',
		'emconf_priority.description' => 'This tells the EM to try to put the extensions as the very first in the list. Default is last.',
		'emconf_module.alttitle' => 'Backend modules included',
		'emconf_module.description' => 'If any subfolders to an extension contains backend modules, those foldernames should be listed here.',
		'emconf_module.details' => 'It allows the EM to know about the existence of the module, which is important because the EM has to update the conf.php file of the module in order to set the correct TYPO3_MOD_PATH  constant.',
		'emconf_state.alttitle' => 'Development state',
		'emconf_state.description' => 'Which development state the extension is in.',
		'emconf_state.details' => '<b>alpha</b>
Very initial development. May do nothing at all. 

<b>beta</b>
Under current development. Should work partly but is not finished yet.

<b>stable</b>
Stable and used in production.

<b>experimental</b>
Nobody knows if this is going anywhere yet... Maybe still just an idea.

<b>test</b>
Test extension, demonstrates concepts etc.',
		'emconf_internal.alttitle' => 'Internally supported in core',
		'emconf_internal.description' => 'This flag indicates that the core source code is specifically aware of the extension.',
		'emconf_internal.details' => 'In order words this flag should convey the message that “this extension could not be written without some core source code modifications”.

An extension is not internal just because it uses TYPO3 general classes eg. those from t3lib/. 
True non-internal extensions are characterized by the fact that they could be written without making core source code changes, but relies only on existing classes  in TYPO3 and/or other extensions, plus its own scripts in the extension folder.',
		'emconf_clearCacheOnLoad.alttitle' => 'Clear cache when installed',
		'emconf_clearCacheOnLoad.description' => 'If set, the EM will request the cache to be cleared when this extension is installed.',
		'emconf_modify_tables.alttitle' => 'Existing tables modified',
		'emconf_modify_tables.description' => 'List of tablenames which are only modified - not fully created - by this extension.',
		'emconf_modify_tables.details' => 'Tables from this list found in the ext_tables.sql file of the extension ',
		'.alttitle' => 'EM',
		'.description' => 'The Extension Manager (EM)',
		'.details' => 'TYPO3 can be extended in nearly any direction without loosing backwards compatibility. The Extension API provides a powerful framework for easily adding, removing, installing and developing such extensions to TYPO3. This is in particular powered by the Extension Manager (EM) inside TYPO3.

“Extensions” is a term in TYPO3 which covers two other terms, plugins and modules.

A plugin is something that plays a role on the website itself. Eg. a board, guestbook, shop etc. It is normally enclosed in a PHP class and invoked through a USER or USER_INT cObject from TypoScript. A plugin is an extension in the frontend.

A module is a backend application which has it\'s own position in the administration menu. It requires backend login and works inside the framework of the backend. We might also call something a module if it exploits any connectivity of an existing module, that is if it simply adds itself to the function menu of existing modules. A module is an extension in the backend.',
		'emconf_private.alttitle' => 'Private',
		'emconf_private.description' => 'If set, this version is not shown in the public list in the online repository.',
		'emconf_private.details' => '"Private" uploads requires you to manually enter a special key (which will be shown to you after an upload has been completed) to be able to import and view details for the uploaded extension.
This is nice when you are working on something internally which you do not want others to look at.
You can set and clear the private flag every time you upload your extension.',
		'_emconf_private.seeAlso' => '_MOD_tools_em:emconf_download_password',
		'emconf_download_password.alttitle' => 'Download password',
		'emconf_download_password.description' => 'Additional password required for download of private extensions.',
		'emconf_download_password.details' => 'Anybody who knows the "special key" assigned to the private upload will be able to import it. Specifying an import password allows you to give away the download key for private uploads and also require a password given in addition. The password can be changed later on.',
		'_emconf_download_password.seeAlso' => '_MOD_tools_em:emconf_private',
		'emconf_type.alttitle' => 'Installation type',
		'emconf_type.description' => 'The type of the installation',
		'emconf_type.details' => 'The files for an extension are located in a folder named by the extension key. The location of this folder can be either inside typo3/sysext/,  typo3/ext/ or  typo3conf/ext/. The extension must be programmed so that it does automatically detect where it is located and can work from all three locations.

<b>Local location “typo3conf/ext/”:</b> This is where to put extensions which are local for a particular TYPO3 installation. The typo3conf/ dir is always local, containing local configuration (eg. localconf.php), local modules etc. If you put an extension here it will be available for this TYPO3 installation only. This is a “per-database” way to install an extension.

<b>Global location “typo3/ext/”:</b> This is where to put extensions which are global for the TYPO3 source code on the web server. These extensions will be available for any TYPO3 installation sharing the source code. 
When you upgrade your TYPO3 source code you probably want to copy the typo3/ext/ directory from the former source to the new source, overriding the default directory. In this way all global extension you use will be installed inside the new sourcecode. After that you can always enter TYPO3 and upgrade the versions if needed.
This is a “per-server” way to install an extension.

<b>System location “typo3/sysext/”:</b> This is system default extensions which cannot and should not be updated by the EM. 


<b>Loading precedence</b>
Local extensions take precedence which means that if an extension exists both in typo3conf/ext/ and typo3/ext/ the one in typo3conf/ext/ is loaded. Likewise global extension takes predence over system extensions. This means that extensions are loaded in the order of priority local-global-system. 
In effect you can therefore have - say - a “stable” version of an extension installed in the global dir (typo3/ext/) which is used by all your projects on a server sharing source code, but on a single experimental project you can import the same extension in a newer “experimental” version and for that particular project the locally available extension will be used instead.
',
		'emconf_doubleInstall.alttitle' => 'Installed twice or more?',
		'emconf_doubleInstall.description' => 'Tells you if the extensions is installed in more than one of the System, Global or Local locations.',
		'emconf_doubleInstall.details' => 'Because an extension can reside at three locations, System, Global and Local, this indicates if the extension is found in other locations than the current. In that case you should be aware which one of the extensions is loaded!',
		'emconf_rootfiles.alttitle' => 'Root files',
		'emconf_rootfiles.description' => 'List of the files in the extension folder. Does not list files in subfolders.',
		'emconf_dbReq.alttitle' => 'Database requirements',
		'emconf_dbReq.description' => 'Shows you the requirements to the database tables and fields, if any.',
		'emconf_dbReq.details' => 'This will read from the files ext_tables.sql and ext_tables_static+adt.sql and show you which tables, fields and static tables are required with this extension.',
		'emconf_dbStatus.alttitle' => 'Database requirements status',
		'emconf_dbStatus.description' => 'Displays the current status of the database compared to the extension requirements.',
		'emconf_dbStatus.details' => 'If the extension is loaded which will display and error message if some tables or fields are not present in the database as they should be!',
		'emconf_flags.alttitle' => 'Flags',
		'emconf_flags.description' => 'A list of special codes which tells you something about what parts of TYPO3 the extension touches.',
		'emconf_flags.details' => 'This is a list of the flags:

<b>Module:</b> A true backend main/sub module is found to be added.

<b>Module+:</b> The extension adds itself to the function menu of an existing backend module.

<b>loadTCA:</b> The extension includes a function call to t3lib_div::loadTCA for loading a table. This potentially means that the system is slowed down, because the full table description of some table is always included. However there probably is a good reason for this to happen. Probably the extension tries to manipulate the TCA-config for an existing table in order to extend it.

<b>TCA:</b> The extension contains configuration of a table in $TCA.

<b>Plugin:</b> The extension adds a frontend plugin to the plugin list in Content Element type "Insert Plugin".

<b>Plugin/ST43:</b> TypoScript rendering code for the plugin is added to the static template "Content (default)". "Plugin" and "Plugin/ST43" are commonly used together.

<b>Page-TSconfig:</b> Default Page-TSconfig is added.

<b>User-TSconfig:</b> Default User-TSconfig is added.

<b>TS/Setup:</b> Default TypoScript Setup is added.

<b>TS/Constants:</b> Default TypoScript Constants is added.
',
		'emconf_conf.description' => 'Shows if the extension has a template for further lowlevel configuration.',
		'emconf_TSfiles.alttitle' => 'Static TypoScript files',
		'emconf_TSfiles.description' => 'Shows which TypoScript static files may be present',
		'emconf_TSfiles.details' => 'If the files ext_typoscript_constants.txt and/or ext_typoscript_setup.txt is found in the extension folder their are included in the hierarchy of all TypoScript templates in TYPO3 right after the inclusion of other static templates.',
		'emconf_locallang.alttitle' => 'locallang-files',
		'emconf_locallang.description' => 'Shows which files named "locallang.php" are present in the extension folder (recursive search). Such files are usually used to present an array $LOCAL_LANG with labels for the application in the system languages.',
		'emconf_moduleNames.alttitle' => 'Backend Module names',
		'emconf_moduleNames.description' => 'Shows which module names was found inside the extension.',
		'emconf_classNames.alttitle' => 'PHP Class names',
		'emconf_classNames.description' => 'Shows which PHP-classes were found in .php and .inc files.',
		'emconf_errors.alttitle' => 'Errors',
		'emconf_errors.description' => 'Displays if any serious errors with the extension was discovered.',
		'emconf_NSerrors.alttitle' => 'Namespace errors',
		'emconf_NSerrors.description' => 'Certain naming convensions apply to extensions. This displays any violations found.',
		'emconf_NSerrors.details' => 'The naming convensions are defined in the "Inside TYPO3" document. To make naming as simple as possible, try to avoid underscores in your extension keys.',
	),
);
?>