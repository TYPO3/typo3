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
	'dk' => Array (
	),
	'de' => Array (
	),
	'no' => Array (
	),
	'it' => Array (
	),
	'fr' => Array (
	),
	'es' => Array (
	),
	'nl' => Array (
		'emconf_shy.alttitle' => 'Verlegen',
		'emconf_shy.description' => 'Als ingesteld zal deze extensie normaalgesproken verborgen zijn in de EM, omdat het een standaardextensie is of iets dat anders niet zo belangrijk is.',
		'emconf_shy.details' => 'Gebruik deze vlag als een extensie van weinig belang is (niet hetzelfde als onbelangrijk - gewoon een extensie die niet vaak gebruikt wordt...)
Normaalgesproken staat "verlegen" ingesteld voor alle extensies die standaard geladen worden aan de hand van TYPO3_CONF_VARS.',
		'emconf_category.alttitle' => 'Categorie',
		'emconf_category.description' => 'Categorie van deze extensie',
		'emconf_category.details' => '<b>be:</b> Backend (Backendgeoriënteerd, maar geen module)

<b>module:</b> Backend modules (Als iets een module is of van een module gebruik maakt)

<b>fe:</b> Frontend (Algemeen frontend georiënteerd, maar geen "echte" plugin)

<b>plugin:</b> Frontend plugins (Plugins ingevoegd als een "Invoegen plugin" inhoudselement)

<b>div:</b> Diverse zaken (Die niet ergens anders ondergebracht kunnen worden)

<b>voorbeeld:</b> Voorbeeld extensie (Dient als voorbeeld)',
		'emconf_dependencies.alttitle' => 'Afhankelijk van andere extensies?',
		'emconf_dependencies.description' => 'Dit is een lijst van andere extensie waar deze extensie van afhankelijk is. Deze dienen geladen te worden VOOR de extensie zelf.',
		'emconf_dependencies.details' => 'De EM regelt de afhankelijkheid terwijl de extensielijst naar de localconf geschreven wordt.',
		'emconf_conflicts.alttitle' => 'Conflicteerd met andere extensies?',
		'emconf_conflicts.description' => 'Lijst van extensiesleutels van extensies die niet met deze extensie werken (en dus niet geactiveerd kan worden voordat deze andere extensies gedeïnstalleerd zijn)',
		'emconf_priority.alttitle' => 'Gewenste volgorde van laden',
		'emconf_priority.description' => 'Dit zorgt ervoor dat de EM probeert de extensies als allereerste in de lijst zet. Standaard is laatste.',
		'emconf_module.alttitle' => 'Geïncludeerde backendmodules',
		'emconf_module.description' => 'Wanneer submappen van een extensie backendmodules bevatten dan dienen die mapnamen hier getoond te worden.',
		'emconf_module.details' => 'Dit zorgt ervoor dat de EM weet van het bestaan van de module. Dat is belangrijk, omdat de EM het conf.php bestand van de module moet actualiseren om de juiste TYPO3_MOD_PATH constante in te stellen.',
		'emconf_state.alttitle' => 'Ontwikkelingstoestand',
		'emconf_state.description' => 'In welke ontwikkelingstoestand de extensie zich bevindt.',
		'emconf_state.details' => '<b>alfa</b>
Beginstadium van ontwikkeling. Werkt misschien helemaal niet.V

<b>beta</b>
In ontwikkeling. Moet gedeeltelijk werken, maar is nog niet af.

<b>stabiel</b>
Stabiel en in productie.

<b>experimenteel</b>
Niemand weet waar dit naar toe gaat... Misschien gewoon een idee.

<b>test</b>
Test extensie voor concepten enz.',
		'emconf_internal.alttitle' => 'Intern ondersteunt in de core',
		'emconf_internal.description' => 'Deze vlag geeft aan dat de corecode zich specifiek bewust is van de aanwezigheid van de extensie.',
		'emconf_internal.details' => 'Deze vlag dient de boodschap over te  brengen dat "deze extensie niet geschreven kan worden zonder enige corebroncodemodificaties".

Een extensie is niet alleen intern omdat deze gebruik maakt van TYPO3\'s algemene classes zoals bijvoorbeeld die uit t3lib/. Daadwerkelijke niet-interne extensies worden gekarakteriseerd door het feit dat deze geschreven kunnen worden zonder gebruik te maken van corebroncodewijzigingen, maar alleen afhankelijk zijn van bestaande classes in TYPO3 en/of andere extensies plus eigen scripts in de extensiemap.',
		'emconf_clearCacheOnLoad.alttitle' => 'Cache leegmaken wanneer geïnstalleerd',
		'emconf_clearCacheOnLoad.description' => 'Wanneer dit ingesteld is dan zal de EM een verzoek doen de cache leeg te maken zodra de extensie geïnstalleerd is.',
		'emconf_modify_tables.alttitle' => 'Gewijzigde bestaande tabellen',
		'emconf_modify_tables.description' => 'Lijst van tabelnamen die alleen gewijzigd - niet aangemaakt - door deze extensie.',
		'emconf_modify_tables.details' => 'Tabellen van de lijst uit het ext_tables.sql bestand van deze extensie.',
		'.alttitle' => 'EM',
		'.description' => 'De extensie Manager (EM)',
		'emconf_moduleNames.alttitle' => 'Namen van backendmodules',
		'emconf_errors.alttitle' => 'Fouten',
		'emconf_NSerrors.alttitle' => 'Namespace fouten',
	),
	'cz' => Array (
		'emconf_shy.alttitle' => 'Stydlivá',
		'emconf_shy.description' => 'Pokud je nastaveno, bude rozšíøení v EM bìnì skryto, protoe to mùe bıt základní rozšíøení nebo nìco jinak ne pøíliš dùleitého.',
		'emconf_shy.details' => 'Pouijte tento pøíznak pokud je rozšíøení "nezajímavé" (co není to samé jako nedùleité - jen je nikdo nehledá èasto ...)
Neovlivòuje, zda je nebo není zapnuté. Jen zobrazení v EM.
Bìnì je pøíznak "stydlivá" nastaven pro všechna rozšíøení, která se nahrávají podle TYPO3_CONF_VARS.',
	),
	'pl' => Array (
	),
	'si' => Array (
	),
	'fi' => Array (
		'emconf_shy.alttitle' => 'Piiloitteleva',
		'emconf_shy.description' => 'Jos tämä on asetettu, on laajennsu normaalisti piiloitettu Laajennuksen Hallinnassa (EM) koska se voi olla oletuslaajennus tai muutoin sellainen joka ei olet ärkeä.',
		'emconf_shy.details' => 'Käytä tät merkintää kun laajennus on "vailla kiinnostusta" (mikä ei suinkaan tarkoita merkityksetöntä - laajennus jota tarvitaan vain kovin harvoin...)
Ei vaikuta yleiseti toimintaa on ladattu vai ei. Näkyy ainoastaan Laajennusten Hallinnassa (EM).
Piiloitteleva (Shy) asetetaan yleensä laajennuksille jotka ladataan oletuksena TYPO3_CONF_VARS muuttujan mukaisesti.',
		'emconf_category.alttitle' => 'Luokka',
		'emconf_category.description' => 'Mihin luokkaan laajennus kuuluu.',
		'emconf_category.details' => '<b>be:</b> Tausta (yleensä taustaan liittyvä, muttei aliohjelma)

<b>module:</b> Tausta aliohjelma (On jokin aliohjelma tai liittyy sellaiseen)

<b>fe:</b> Etutoiminto (Yleisesti etutoimintaa liittyvä muttei suoranainen aliohjelma)

<b>plugin:</b> Etutoiminto laajennus (Laajennus joka asennetaan kuten "Lisää Laajennus" (“Insert Plugin”) sisältö elementti)

<b>misc:</b> Satunnainen (Laajennus jota ei voi määritellä muualle)

<b>example:</b> Esimerkki laajennus (Laajennus joka on vain esimerkin luonteinen jne.)',
		'emconf_dependencies.alttitle' => 'Riippuvuudet muihin laajennuksiin ?',
		'emconf_dependencies.description' => 'Tämä on lista muiden laajennusten avaimista, jotka on ladattava ennenkuin tämä laajennus ladataan.',
		'emconf_dependencies.details' => 'Laajennusten Hallinta (EM) hoitaa riippuvuuden kirjoittaessaan laajennusten listan localconf.php tiedostoon.',
		'emconf_conflicts.alttitle' => 'Konfliktit muiden laajennusten kanssa ?',
		'emconf_conflicts.description' => 'Lista tämän laajennuksen kanssa toimimattomien laajennusten avaimista (ja joita ei voi asettaa ennekuin nuo laajennukset on poistettu asennuksesta)',
		'emconf_priority.alttitle' => 'Haluttu lataamisen pririsointi',
		'emconf_priority.description' => 'Tämän tarkoituksena on antaa Laajennusten Hallinnalle (EM) tieto laajennuksen asettamista ladattavaksi ensimmäisenä. Oletusarvo on lataus viimeisenä.',
		'emconf_module.alttitle' => 'Tausta-aliohjelmat jotka on lisätty',
		'emconf_module.description' => 'Jos laajennuksen mikä tahansa hakemisto sisältää tausta-aliohjelmia tulee ne (hakemistot) listata tähän.',
		'emconf_module.details' => 'Tämä mahdollistaa Laajennusten Hallinan (EM) tietävän aliohjelmista, joka on tarpeen jotta Laajennusten Hallinta voi päivittää conf.php tiedoston jotta voidaan asettaa oikea TYPO3_MOD_PATH vakio.',
		'emconf_state.alttitle' => 'Kehittämisen tila',
		'emconf_state.description' => 'Mihin kehittämitilaan laajennus kuuluu.',
		'emconf_state.details' => '<b>alpha</b>
Hyvin alkuvaihe. Voi olla ettei toimi lainkaan.

<b>beta</b>
Kehittämisen alainen. Pitäisi toimia ainakin osittain muttei ole viimesitelty.

<b>stable</b>
Vaka ja tuotantokelpoinen.

<b>experimental</b>
Kukaan ei tiedä miten tulee käymään... Voi olla vain ajatuksen asteella.

<b>test</b>
Testilaajennus, kuvastaa konseptia, ajatusta jne.',
		'emconf_internal.alttitle' => 'Tuetaan sisäisesti ohjelmarungossa.',
		'emconf_internal.description' => 'Tämä merkintä osoittaa että ohjelmarunko on tietoinen laajennuksesta.',
		'emconf_internal.details' => 'Toisin sanoen, tämä merkintä ilmoittaa että " tätä laajennnusta ei voi ilman jotain runko-ohjeman muutosta".

Laajennus ei ole sisäinen vain siksi että se käyttää jotain TYPO3n omialuokkia esim. luokkia t3lib/ hekemistosta.
Todellisia ei-sisäisiä laajennuksia luonnehtii se tosiasia että ne on voitu kirjoitaa ilman muutoksia runko-ohjelmistoon (core),vaan käyttävät vain jo olemassaolevia TYPO3n luokkia ja/tai muita laajennuksia, sekä omia scriptejä laajennuksen omassa hakemistossa.',
		'emconf_clearCacheOnLoad.alttitle' => 'Tyhjää välimuisti (cache) kun laajennus asennetaan.',
		'emconf_clearCacheOnLoad.description' => 'Jos asetettu, EM pyytää välimuistin tyhjennettäväksi kun tämä laajennus asennetaan.',
		'emconf_modify_tables.alttitle' => 'Olemassa olevia tauluja muotoiltu',
		'emconf_modify_tables.description' => 'Lista tauluista jotka ainoastaan muokataan - ei luoda - tällälaajennuksella.',
		'emconf_modify_tables.details' => 'Lista tauluista jotka ovat tämän laajennuksen ext_tables.sql tiedostossa.',
		'.alttitle' => 'Laajennuksen Hallinta (EM)',
		'.description' => 'Laajennuksen Hallinta (Extension Manager)',
		'.details' => 'TYPO3a voidaan laajentaa lähes miten tahansa menettämättä takaisin paluun mahdollisuutta. Laajennuksen käyttöliittymä (the Extension API) on tehokas perusta lisätä, poistaa, asentaa je kehittää helposti laajennuksiaTYPO3een. Tätä avustaa erityisesti tehokas Laajennuksen Hallinta (EM) TYPO3essa sisäisesti.

"Laajennus" on TYPO3n termi joka kattaa kasi termiä lisäyksen (plugin) ja aliohjelman (module).

Lisäys (plugin) on jotain jolla on rooli itse web-sivustolla. Esimerkikis. vieraskirja, kauppa jne. Se on normaalisti PHP luokkaja käynnistetään USER tai USER_INT cObjektina TypoScriptillä. Lisäys on laajennus edustatoimintoihin (www käyttäjälle näkyvä osa).

Aliohjelma on taustasovellus jolla on oma hallintavalikko taustatoiminnoissa. Se tarvitsee sisäänkirjoituksen taustatoimintoihin ja toimii taustatoimintojen sisällä. Voimme kutsua aliohjelmaksi myös mitä tahansa joka liittyy johonkin muuhun laajennukseen, joka yksinkertaisesti lisää toiminnan olemassa olevaan aliohjelmaan. Aliohjelma on laajennus taustatoimintoihin.',
		'emconf_private.alttitle' => 'Yksityinen',
		'emconf_private.description' => 'Jos asetettu, ei tätä versiota näytetä julkisesti online laajennusvarastosta.',
		'emconf_private.details' => '"Yksityinen" lataus (upload) tarvitsee sinun käsin lisäävän erityisen avaimen (joka näytetään kun olet suorittanut latauksen onnistuneesti) voidaksesi ladata ja nähdä yksityiskohdat tästä ladatusta laajennnuksesta.
Tämä on tarkoituksen mukaista (ja käytännöllistä) kun haluat itseksesi työskennellä jonkin laajennuksen kanssa jota et halua näyttää muille.
Voit asettaa tai poistaa yksityisyys merkinnän joka kerta kun lataat laajennuksen.',
		'emconf_download_password.alttitle' => 'Imuroinnin (download) salasana',
		'emconf_download_password.description' => 'Yksityisen laajennuksen imurointiin tarvittava pakollinen salasana',
		'emconf_download_password.details' => 'Kuka tahansa tietää laajennuksesi "erityis avaimen" voi hakea sen. Antamalla lataus salasanan voit antaaimurointi oikeuden yksityisiin laajennuksiisi jotka vaativat myös salasanan. Salasanan voi vaihtaa myöhemmin.',
		'emconf_type.alttitle' => 'Asennuksen tyyppi',
		'emconf_type.description' => 'Asennuksen tyyppi',
		'emconf_type.details' => 'Laajennuksen tiedostot sijoitetaan laajennuksen avaimen mukaisesti nimettyyn  hakemistoon. Tämä hakemisto voi sijaita joko, typo3/sysext/, typo3/ext/ tai typo3conf/ext/ hakemistoissa. Laajennus on ohjelmoitava siten että se itse tietääsijaintinsaja voi toimia mistä tahansa kolmesta sijaintimahdollisuudesta käsin.

<b>Paikallinen sijainti “typo3conf/ext/”:</b> Tänne sijoitetaan laajennukset jotka ovat erityisiä tälle TYPO3 installaatiolle. Hakemisto typo3conf/ on aina paikallinen, sisältääpaikallisen konfiguroinnin (esim.localconf.php), paikalliset aliohjelmat jne. Jos sijoitat laajennuksen tänne on se käytettävissä vain tässä TYPO3 installaatiossa. Se on tietokantakohtainen.

<b>Globaali sijainti “typo3/ext/”:</b>  Tänne sijoitetään TYPO3 installaation globaalit laajennukset. Nämä laajennukset ovat käytettävissä kaikilleTYPO3 installaatiosssa.
Kun päivität TYPO3 lähde koodiasi haluat varmasti kopioida typo3/ext/ hakemiston alkuperäisestälähteestä uudeksi lähteeksi, kirjoittaen yli oleetushakemiston. Näin toimien kaikki käyttämäsi laajennukset asennetaan uuteen lähdekoodiin. Tämän jälkeen voit siirtyä TYPO3een ja päivittää ne versiot joita tarvitset.
Tämä on per-palvelin tapa installoida laajennus.

<b>System location “typo3/sysext/”:</b> Tämä on järjestelmän oletus laajennukset joita ei voi eikäsaa päivittää Laajennusten Hallinnalla (EM).

<b>Lataamisen järjestys</b>
Paikalliset laajennukset ovat etuoikeutettuja eli jos laajennus on sekä  typo3conf/ext/ että typo3/ext/ hakemistoissa typo3conf/ext/ hakemistossa oleva ladataan. Samoin globaalit laajennukset ovat etuoikeutettuja järjestelmälaajennuksiin nähden. Tämä tarkoittaa että lataamisjärjestys on paikallinen-globaali-järjestelmä.
Toisin sanoen Sinulla voi olla - sanokaamme - "vakaa" versio laajennuksesta joka on installoitu  globaaliin hakemistoon jota kaikki hankkeet käyttävät ja jakavat yhteisen koodin,  mutta yksittäisessä hankeessa voi kokeilla samaa laajennusta "kokeilu" versiona ja jotain hanketta varten voit  käyttää paikallista versiota.',
		'emconf_doubleInstall.alttitle' => 'Installoitu toistamiseen tai useammin ?',
		'emconf_doubleInstall.description' => 'Kertoo Sinullejoslaajennus on asennettu useamman kerran joko Järjestelmä (System), Globaali (Global) tai Paikallinen (Local) hakemistoihin.',
		'emconf_doubleInstall.details' => 'Koska laajennukset voidaan sijoittaa kolmeen eri paikkaan, System, Global tai Local,osoittaa tämä jos laajennus on muissakin kuin tämän hetkisessä. Tässä tapauksessa varmista mistä hakemistosta laajennus ladataan.',
		'emconf_rootfiles.alttitle' => 'Juuren tiedostot',
		'emconf_rootfiles.description' => 'Lista tiedostoissa laajennuksen hakemistossa.Ei sisällä tiedostoja alahakemistoista.',
		'emconf_dbReq.alttitle' => 'Tietokantavaatimukset',
		'emconf_dbReq.description' => 'Näyttää vaatimukset tietokannan tauluihin ja tietoihin, jos mitään.',
		'emconf_dbReq.details' => 'Tämä lukee tiedostoista ext_tables.sql ja ext_tables_static+adt.sql näyttäen mitä tauluja, tietoja sekä staattisia tauluja tarvitaan tässä laajennuksessa.',
		'emconf_dbStatus.alttitle' => 'Tietokantavaatimusten tila',
		'emconf_dbStatus.description' => 'Näyttää tietokannantilan verrattuna laajennuksen vaatimuksiin.',
		'emconf_dbStatus.details' => 'Näyttää virheilmoituksen jos laajennus on ladattu eikä tietokannan taulut tai tiedot vastaa sitä miten niiden tulisi olla.',
		'emconf_flags.alttitle' => 'Merkinnät',
		'emconf_flags.description' => 'Lista erityisistä koodeista jotka kertovat mitä TYPO3n osia laajennus koskee.',
		'emconf_flags.details' => 'Tämä on lista merkinnöistä:

<b>Module:</b> Todellinen tausta ohjelma/aliohjelmajoka onlöydetty lisättäväksi.

<b>Module+:</b> Laajennus lisää itsensä jo olemassa olevan tausta aliohjelman valikkoon.

<b>loadTCA:</b> Laajennus sisältää toiminta kutsun t3lib_div::loadTCA taulun lataamiseksi. Tämä mahdollisesti tarkoittaa järjestelmän hidastumista, koska täydellinen kuvaus joistakin tauluista on aina muistissa. Voi olla ettätähän on hyväkin syy. Voi olla että laajennus yrittää manipuloida TCA-config tietoja laajentaakseen olemassa olevaa taulua.

<b>TCA:</b> Laajennus sisältää taulun konfiguraation $TCAssa.

<b>Plugin:</b> Laajennus lisää edustatoimintojen lisäyksen lisäytten listalle Sisältö Elementtien tyypiksi "Lisää plugin".

<b>Plugin/ST43:</b> TypoScript toiminta koodi lisäykselle on lisätty stattiseen mallinteeseen"Sisältö (oletus)" ("Content (default)"). "Plugin" ja "Plugin/ST43" käytetään yleisesti yhdessä.

<b>Page-TSconfig:</b> Oletus Page-TSconfig on lisätty.

<b>User-TSconfig:</b> Oletus User-TSconfig on lisätty.

<b>TS/Setup:</b> Oletus TypoScript Asetukset (Setup) on lisätty.

<b>TS/Constants:</b> Oletus TypoScript Vakiot on lisätty.',
		'emconf_conf.description' => 'Näyttää jos laajennuksella on mallinne (template) alemman tason konfigurointia varten.',
		'emconf_TSfiles.alttitle' => 'Staattiset TypoScript tiedostot',
		'emconf_TSfiles.description' => 'Näyttää mitkä staattiset TypoScript tiedostot voivat olla voimassa',
		'emconf_TSfiles.details' => 'Jos tiedostot ext_typoscript_constants.txt ja/tai ext_typoscript_setup.txt löytyvät laajennuksen hakemistosta ne lisätään kaikkiiTypoScript mallinteisiin heti muiden staatisten mallinteiden jälkeen.',
		'emconf_locallang.alttitle' => 'locallang-tiedostot',
		'emconf_locallang.description' => 'Näyttää mitä "locallang.php" tiedostoja löytyy laajennuksen hakemistoista (rekursiivinen haku). Nämä tiedostot ovat yleensä $LOCAL_LANG taulokoita joissa on tekstit sovelluksen kullekin järjestelmän kielelle.',
		'emconf_moduleNames.alttitle' => 'Tausta aliohjelmien nimet',
		'emconf_moduleNames.description' => 'Näyttää mitkä aliohjelmien nimet löytyvät laajennuksesta.',
		'emconf_classNames.alttitle' => 'PHP Luokka nimet',
		'emconf_classNames.description' => 'Näyttää mitkä PHP-luokat löytyvät .phpja .inc tiedostoista.',
		'emconf_errors.alttitle' => 'Virheet',
		'emconf_errors.description' => 'Tämä näyttää jos laajennuksesta löytyy vakavia virheitä.',
		'emconf_NSerrors.alttitle' => 'Namespace virheitä',
		'emconf_NSerrors.description' => 'Laajennusten nimeämiseen on olemassa käytäntö. Tämä näyttää mitä virheitä löytyy.',
		'emconf_NSerrors.details' => 'Nimeämiskäytäntö on selvitetty "Inside TYPO3" dokumentissa. Nimien pitämiseksi mahdollisimman yksinkertaisina, pyri välttämään alaviivan käyttöä laajennustesi nimissä.',
	),
	'tr' => Array (
	),
	'se' => Array (
		'emconf_shy.alttitle' => 'Blyg',
		'emconf_shy.description' => 'När detta är valt, kommer extensionen att gömmas i EM eftersom den kanske är en standardextension eller något, som inte är så viktigt.',
		'emconf_shy.details' => 'Använd denna "flagga" om en extension är mindre intressant (vilket inte betyder att den skulle vara ointressan - men en som inte söks så ofta...)
Det har ingen betydelse om extensionen är tillåten eller inte. Visas endast i EM. Normalt ger man värdet "Blyg" åt alla de extensions, som laddas som standard enligt TYPO3_CONF_VARS.',
		'emconf_category.alttitle' => 'Kategori',
		'emconf_category.description' => 'Berättar vilken kategori extensionen hör till.',
		'emconf_category.details' => '<b>be:</b> Backend (I regel för backend, men inte en modul)

<b>module:</b> Backend moduler (Om någonting är en modul eller hör till en sådan)

<b>fe:</b> Frontend (I regel för frontend, men inte en riktig plugin)

<b>plugin:</b> Frontend plugin (Plugins som kan infogas som ett "Infoga plugin"-innehållselement)

<b>misc:</b> Material av olika art (Sådant som är svårt att placera någon annanstans)

<b>example:</b> Exempel på extensions (Fungeras om exempel osv.)',
		'emconf_dependencies.alttitle' => 'Beroende av andra extensions?',
		'emconf_dependencies.description' => 'Detta är en lista på andra extension-nycklar som denhär extensionen är beroende av att laddas FÖRE sig själv.',
		'emconf_dependencies.details' => 'EM kan sköta om beroendet genom att skriva extension-listan till localconf.php',
		'emconf_conflicts.alttitle' => 'Konflikt med en annan extension?',
		'emconf_conflicts.description' => 'En lista över extension-nycklar med vilka denna extension inte fungerar (och kan alltså inte startas före den andra extensionen är avinstallerad)',
		'emconf_priority.alttitle' => 'Begärd laddningsprioritet',
		'emconf_priority.description' => 'Berättar för EM att försöka lägga denna extension som den allra första i listan. Standard är att den läggs till i slutet.',
		'emconf_module.alttitle' => 'Backend-moduler inkluderade',
		'emconf_module.description' => 'Om någon extensions underkatalog innehåller en backend-modul, skall deras katalognamn listas här.',
		'emconf_module.details' => 'Ger EM en möjlighet att veta om en modul,  som är viktig därför att EM måste uppdatera modulens conf.php-fil för att rätt konstant TYPO3_MOD_PATH skall ställas in.',
		'emconf_state.alttitle' => 'Utvecklingsskede',
		'emconf_state.description' => 'Berättar vilket utvecklingsskede extensionen är i.',
		'emconf_state.details' => '<b>alpha</b>
Utvecklingen på mycket tidigt stadie. Kanske inte gör något alls. 

<b>beta</b>
Utveckling på gång. Fungerar delvis men arbetet fortsätter.

<b>stable</b>
Stabil och i produktivt bruk.

<b>experimental</b>
Ingen vet om dethär skall bli något.. Kanske bara en idé.

<b>test</b>
Test-extension, demonstrerar olika sätt mm.',
		'emconf_internal.alttitle' => 'Full internatinell support',
		'emconf_internal.description' => 'Denna märkning betyder att kärnkällkoden är uppmärksam på extensionen.',
		'emconf_internal.details' => 'Med andra ord betyder denna märkning, att "denna extension kunde inte skrivas utan att några ändringar i kärnkällkoden gjordes".

En extension är inte intern bara därför att den använder TYPO3:s allmänna klasser, dvs från t3lib/.
Äkta icke-interna extensioner karaktäriseras av det faktum att de kan skrivas utan att göra ändringar i kärnkällkoden, men de är endast relaterade till existerande klasser i TYPO3 och/eller andra extensioner, plus deras egna skript i extensionsfoldern.',
		'emconf_clearCacheOnLoad.alttitle' => 'Töm mellanminnet efter installationen',
		'emconf_clearCacheOnLoad.description' => 'Om detta är valt, kommer EM att begära att mellanminnet töms då extensionen är installerad.',
		'emconf_modify_tables.alttitle' => 'Existerande tabeller ändrade',
		'emconf_modify_tables.description' => 'En lista på de tabeller, som endast ändrats - inte skapats - av denna extension.',
		'emconf_modify_tables.details' => 'Tabeller från denna lista finns i ext_tables.sql-filen i extensionen',
		'.alttitle' => 'EM',
		'.description' => 'Extension-managern (EM)',
		'.details' => 'TYPO3 can be extended in nearly any direction without loosing backwards compatibility. The Extension API provides a powerful framework for easily adding, removing, installing and developing such extensions to TYPO3. This is in particular powered by the Extension Manager (EM) inside TYPO3.

“Extensions” is a term in TYPO3 which covers two other terms, plugins and modules.

A plugin is something that plays a role on the website itself. Eg. a board, guestbook, shop etc. It is normally enclosed in a PHP class and invoked through a USER or USER_INT cObject from TypoScript. A plugin is an extension in the frontend.

A module is a backend application which has it\'s own position in the administration menu. It requires backend login and works inside the framework of the backend. We might also call something a module if it exploits any connectivity of an existing module, that is if it simply adds itself to the function menu of existing modules. A module is an extension in the backend.',
		'emconf_private.alttitle' => 'Privat',
		'emconf_private.description' => 'Om förkryssad kommer denna version inte att synas i den offentliga listan.',
		'emconf_private.details' => '"Private" uploads requires you to manually enter a special key (which will be shown to you after an upload has been completed) to be able to import and view details for the uploaded extension.
This is nice when you are working on something internally which you do not want others to look at.
You can set and clear the private flag every time you upload your extension.',
		'emconf_download_password.alttitle' => 'Lösenord för nerladdning',
		'emconf_download_password.description' => 'Ett extra lösenord som behövs för att ladda ner privata extensioner.',
		'emconf_download_password.details' => 'Anybody who knows the "special key" assigned to the private upload will be able to import it. Specifying an import password allows you to give away the download key for private uploads and also require a password given in addition. The password can be changed later on.',
		'emconf_type.alttitle' => 'Installationstyp',
		'emconf_type.description' => 'Typ av installation',
		'emconf_type.details' => 'The files for an extension are located in a folder named by the extension key. The location of this folder can be either inside typo3/sysext/, typo3/ext/ or typo3conf/ext/. The extension must be programmed so that it does automatically detect where it is located and can work from all three locations.

<b>Local location “typo3conf/ext/”:</b> This is where to put extensions which are local for a particular TYPO3 installation. The typo3conf/ dir is always local, containing local configuration (eg. localconf.php), local modules etc. If you put an extension here it will be available for this TYPO3 installation only. This is a “per-database” way to install an extension.

<b>Global location “typo3/ext/”:</b> This is where to put extensions which are global for the TYPO3 source code on the web server. These extensions will be available for any TYPO3 installation sharing the source code. 
When you upgrade your TYPO3 source code you probably want to copy the typo3/ext/ directory from the former source to the new source, overriding the default directory. In this way all global extension you use will be installed inside the new sourcecode. After that you can always enter TYPO3 and upgrade the versions if needed.
This is a “per-server” way to install an extension.

<b>System location “typo3/sysext/”:</b> This is system default extensions which cannot and should not be updated by the EM. 


<b>Loading precedence</b>
Local extensions take precedence which means that if an extension exists both in typo3conf/ext/ and typo3/ext/ the one in typo3conf/ext/ is loaded. Likewise global extension takes predence over system extensions. This means that extensions are loaded in the order of priority local-global-system. 
In effect you can therefore have - say - a “stable” version of an extension installed in the global dir (typo3/ext/) which is used by all your projects on a server sharing source code, but on a single experimental project you can import the same extension in a newer “experimental” version and for that particular project the locally available extension will be used instead.',
		'emconf_doubleInstall.alttitle' => 'Installerad dubbelt eller ännu flere gåner?',
		'emconf_doubleInstall.description' => 'Berättar om extensionen är installerad i flere än ett system, globalt eller lokalt ställe.',
		'emconf_doubleInstall.details' => 'Because an extension can reside at three locations, System, Global and Local, this indicates if the extension is found in other locations than the current. In that case you should be aware which one of the extensions is loaded!',
		'emconf_rootfiles.alttitle' => 'Rotfiler',
		'emconf_rootfiles.description' => 'Listar filerna i extensionens katalog. Visar inte filer i underkataloger.',
		'emconf_dbReq.alttitle' => 'Databasens krav',
		'emconf_dbReq.description' => 'Visar vilka krav databasens tabeller och fält ställer, om några.',
		'emconf_dbReq.details' => 'This will read from the files ext_tables.sql and ext_tables_static+adt.sql and show you which tables, fields and static tables are required with this extension.',
		'emconf_dbStatus.alttitle' => 'Statusen av databasens krav',
		'emconf_dbStatus.description' => 'Visar nuvarande ställningen i databasen jämförd med extensionens krav.',
		'emconf_dbStatus.details' => 'If the extension is loaded which will display and error message if some tables or fields are not present in the database as they should be!',
		'emconf_flags.alttitle' => 'Flaggor',
		'emconf_flags.description' => 'En lista på specialkoder som berättar någonting om vilka delar av TYPO3 som extensionen berör.',
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

<b>TS/Constants:</b> Default TypoScript Constants is added.',
		'emconf_conf.description' => 'Visar om extensionen har en template för framtida lågnivåkonfigurering.',
		'emconf_TSfiles.alttitle' => 'Statiska TypoScript filer',
		'emconf_TSfiles.description' => 'Visar vilka statiska TypoScript-filer kan förekomma',
		'emconf_TSfiles.details' => 'If the files ext_typoscript_constants.txt and/or ext_typoscript_setup.txt is found in the extension folder their are included in the hierarchy of all TypoScript templates in TYPO3 right after the inclusion of other static templates.',
		'emconf_locallang.alttitle' => 'locallang-filer',
		'emconf_locallang.description' => 'Shows which files named "locallang.php" are present in the extension folder (recursive search). Such files are usually used to present an array $LOCAL_LANG with labels for the application in the system languages.',
		'emconf_moduleNames.alttitle' => 'Backend-modulernas namn',
		'emconf_moduleNames.description' => 'Visar vilka modulnamn som hittades i extensionen',
		'emconf_classNames.alttitle' => 'PHP klassnamn',
		'emconf_classNames.description' => 'Visar vilka PHP-klasser som hittades i .php och .inc filerna.',
		'emconf_errors.alttitle' => 'Fel',
		'emconf_errors.description' => 'Visar allvarliga fel i extensionen.',
		'emconf_NSerrors.alttitle' => 'Fel i namnen',
		'emconf_NSerrors.description' => 'Vissa benämningsregler finns för extensionerna. Här visas eventuella fel i namnen.',
		'emconf_NSerrors.details' => 'The naming convensions are defined in the "Inside TYPO3" document. To make naming as simple as possible, try to avoid underscores in your extension keys.',
	),
	'pt' => Array (
	),
	'ru' => Array (
	),
	'ro' => Array (
	),
	'ch' => Array (
	),
	'sk' => Array (
	),
	'lt' => Array (
	),
	'is' => Array (
	),
	'hr' => Array (
		'emconf_shy.alttitle' => 'Prikriveno',
		'emconf_shy.description' => 'Ako je postavljeno, ekstenzija æe u biti skrivena u EM-u, npr. jer se radi o uobièajenoj ekstenziji ili nije od veæe vanosti.',
		'emconf_shy.details' => 'Koristite ovu zastavicu ako je mali interes za ekstenziju (to ne znaèi 
da nije vana væ smo da nije èesto traena...)
Ne utjeèe na ukljuèenost ekstenzije, samo na prikaz u EM-u.
Uobièajeno je opcija \'skriveno\' postavljena za sve ekstenzije koje
su standardno uèitane u skladu s TYPO3_CONF_VARS.',
		'emconf_category.alttitle' => 'Kategorija',
		'emconf_category.description' => 'Kojoj kategoriji pripada ekstenzija',
		'emconf_category.details' => '<b>be:</b> Backend (Uobièajeno backend orijentirana,
ali nije modul)

<b>modul:</b> Backend moduli (Kada je nešto modul
ili se spaja sa modulom)

<b>fe:</b> Frontend (Uobièajeno frontend orijentirana,
ali nije "pravi" plugin)

<b>plugin:</b> Frontend plugin-ovi (Plugin-ovi ubaèeni kao
sadrajni elementi "Ubaci Plugin" metode)

<b>razno:</b> Razne stvari (Kada se ne mogu drugdje smejstiti)

<b>primjer:</b> Primjer ekstenzija (Koja slui kao primjer, itd.)',
		'emconf_dependencies.alttitle' => 'Ovisnost o drugim ekstenzijama?',
		'emconf_dependencies.description' => 'Ovo je lista kljuèeva ostalih ekstenzija o kojima ova ekstenzija ovisi i koje moraju biti uèitane PRIJE nje same.',
		'emconf_dependencies.details' => 'EM æe obraditi tu ovisnost dok bude pisao popis ekstenzija u localconf.php.',
		'emconf_conflicts.alttitle' => 'Konflikti s drugim ekstenzijama?',
		'emconf_conflicts.description' => 'Popis kljuèeva onih ekstenzija s kojim dotièna ekstenzija dolazi u sukob (i zato nemoe biti ukljuèena prije nego su ostale iskljuèene).',
		'emconf_priority.alttitle' => 'Traeni prioritet uèitavanja',
		'emconf_priority.description' => 'Ovo govori EM-u da pokuša upisati ekstenzije na vrh liste. Standardno je na kraj liste.',
		'emconf_module.alttitle' => 'Ukljuèi backend module',
		'emconf_module.description' => 'Ako neki poddirektoriji ekstenzije sadre backend module, imena tih direktorija moraju ovdje biti popisana.',
		'emconf_module.details' => 'Omoguæava EM-u informacije o postojanju modula, što je bitno jer EM mora osvjeiti datoteku modula conf.php kako bi se valjano postavila TYPO3_MOD_PATH konstanta.',
		'emconf_state.alttitle' => 'Status razvoja',
		'emconf_state.description' => 'U kojem se statusu razvoja nalazi ekstenzija.',
		'emconf_state.details' => '<b>alfa</b>
Poèetni razvoj. Ne mora biti funkcionalna.

<b>beta</b>
Trenutno u razvoju. Djelomièno funkcionalna, ali nije završena.

<b>stabilna</b>
Stabilna, koristi se u produkciji.

<b>experimentalna</b>
Nepoznanica je da li æe uopæe biti stvarnih rezultata.
Moda se radi samo o ideji.

<b>test</b>
Test ekstenzije, predstavljanje koncepata, itd.',
		'emconf_internal.alttitle' => 'Interno podran u jezgri sustava.',
		'emconf_internal.description' => 'Ova zastavica ukazuje da ekstenzija posebno utjeèe na izvorni kod jezgre sustava.',
		'emconf_internal.details' => 'U stvari ova zastavica bi trebala upozoravati na èinjenicu da 
"ekstenzija nije mogla biti napisana bez izmjena u izvornom kodu sustava" 

Ekstenzija nije interna samo zato jer upotrebljava TYPO3 opæe klase,
npr. one iz t3lib/.
Prave ne-interne ekstenzije obiljeava èinjenica da su napisane 
bez uvoğenja promjena u izvornom kodu jezgre sustava. One se
uz svoje skripte u pretincu ekstenzije oslanjaju samo na postojeæe 
klase u TYPO3 i/ili drugim ekstenzijama.',
		'emconf_clearCacheOnLoad.alttitle' => 'Oèisti privremeni spremnik (cache) nakon instalacije.',
		'emconf_clearCacheOnLoad.description' => 'Ako je postavljena, EM æe zahtijevati da se privremeni spremnik (cache) oèisti kada se ova ekstenzija instalira.',
		'emconf_modify_tables.alttitle' => 'Postojeæi tablice su promijenjene',
		'emconf_modify_tables.description' => 'Popis imena tablica koje ova ekstenzija samo mijenja, ne stvara ih.',
		'emconf_modify_tables.details' => 'Tablice iz ove liste pronağene u datoteci ekstenzije ext_tables.sql',
		'.alttitle' => 'EM',
		'.description' => 'Menader ekstenzijama (EM)',
		'.details' => 'TYPO3 moe se proširiti u bilo kojem smjeru bez gubitka 
kompatibilnosti unazad. API za ekstenzije prua moænu strukturu
za lagano dodavanje, oduzimanje, instalaciju i razvoj takvih 
ekstenzija u TYPO3 okruenju. To posebno omoguæava menader 
ekstenzija EM unutat TYPO3.

"Ekstenzije" su pojam koje u TYPO3 pokriva dva druga pojma, 
plugin-ovi i moduli.

Plugin je dio koji ima ulogu u samom web sjedištu. 
Npr. oglasna ploèa, knjiga gostiju, duæan itd. Uobièajeno je sadran
unutar PHP klase i pozvan kroz USER ili USER_INT cObject 
iz TypoScript-a. Plugin je ekstenzija u frontend-u.

Modul je backend aplikacija koja ima vlastitu poziciju u administracijskom
meniju. Zahtijeva prijavu na backend sustav i djeluje unutar strukture
beckenda. Takoğer modulom moemo nazvati ako iskorištava
bilo kakvu povezivost nekog postojeæeg modula, tj. ako se dodaje
meniju funkcija postojeæih modula. Modul je ekstenzija u backend-u.',
		'emconf_private.alttitle' => 'Privatno',
		'emconf_private.description' => 'Ako je postavljeno, ova verzija se ne pokazuje u javnoj listi online repozitorij.',
		'emconf_private.details' => '"Privatni"  uploadi zahtjevaju da ruèno unesete posebni kljuè (koji æe vam se prikazati
nakon što se upload završi) da bi mogli importirati i pregledavati detalje uploadane ekstenzije.
To je zgodno kad radite na internim stvarima koje ne elite da drugi pregledavaju.
Moete postaviti i maknuti zastavicu privatno svaki put kada uploadate ekstenziju.',
		'emconf_download_password.alttitle' => 'Download zaporke',
		'emconf_download_password.description' => 'Dodatna zaporka je potrebna za download privatne ekstenzije.',
		'emconf_download_password.details' => 'Svi koji poznaju "poseban kljuè" dodijeljen privatnom uploadu mogu taj upload importirati. Specificiranje zaporke za importiranje omoguæava vam da podijelite kljuè za download privatnih uploada ali i dodatno zahtjevate spomenutu zaporku.Zaporka se moe kasnije promijeniti.',
		'emconf_type.alttitle' => 'Tip instalacije',
		'emconf_type.description' => 'Tip instalacije',
		'emconf_type.details' => 'Datoteke pojedine ekstenzije spremljene su u direktorij nazvan po kljuèu ekstenzije. Mjesto ovog direktorija moe biti 
unutar typo3/sysext/, typo3/ext/ , ili typo3conf/ext. Ekstenzija mora biti programirana tako da automatski otkriva
gdje je smještena i da moe rasditi sa sve tri lokacije.

<b>  Lokalni smještaj “typo3conf/ext/”:</b> Ovdje se spremaju ekstenzije koje su lokalne za pojedinu TYPO3 instalaciju.
Direktorij typo3conf/ je uvijek lokalan, i sadri lokalnu konfiguraciju (npr. localconf.php), lokalne module, itd.
Ako ovdje smjestite ekstenziju biti æe dostupna samo ovoj TYPO3 instalaciji. 
To je "po-bazi-podataka" naèin instalacije ekstenzije.

<b>Globalni smještaj “typo3/ext/”:</b> Ovdje se spremaju ekstenzije koje su globalne za TYPO3 izvorni kod na web
posluitelju. Ove ekstenzije æe biti dostupne za sve TYPO3 instalacije koje dijele izvorni kod.
Kada aurirate vaš TYPO3 izvorni kod prikladno je kopirati typo3/ext direktorij iz prijašnje u obnovljenu instalaciju, 
nadjaèavši preddefinirani direktorij. Na taj æe se naèin sve korištene globalne ekstenzije naæi unutar novog izvornog koda.
Nakon toga uvijek moete ulaskom u TYPO3 po potrebi aurirati inaèice. 
To je "po-serveru" naèin instalacije ekstenzije.

<b> Sistemski smještaj “typo3/sysext/”:</b> Ovo su sistemski zadane ekstenzje  koje se nemogu i ne bi trebale aurirati 
od strane EM-a

<b> Prioriteti uèitavanja</b> 
Lokalne ekstenzije imju prednost što znaèi da ukoliko ekstenzija postoji u typo3conf/ext/ i typo3/ext/ uèitava 
se samo ona iz typo3conf/ext/. Slièno globalne ekstenzije imaju prednost nad sistemskim. To znaèi da se ekstenzije
uèitavaju redosljedom lokalne-globalne-sistemske.
U praksi moete imati  "stabilnu" inaèicu instaliranu u globalni direktorij (typo3/ext/), koju koriste svi vaši projekti 
koji dijele izvorni kod na posluitelju. S  druge strane na pojedinom pokusnom projektu moete importirati istu 
ekstenziju u novijoj "pokusnoj" inaèici, i za taj posebni projekt koristiti æe se lokalno dostupne ekstenzije.',
		'emconf_doubleInstall.alttitle' => 'Instalirana dva ili više puta?',
		'emconf_doubleInstall.description' => 'Pokazuje ako je ekstenzija instalirana na više od jednog Sistemskog, Globalnog ili Lokalnog mjesta.',
		'emconf_doubleInstall.details' => 'Kako ekstenzija moe postojati na tri mjesta, Sistemsko, Globalno, Lokalno, ovo ukazuje nalaz li se ekstenzijai i na drugim mjestima osim trenutnog. U tom sluèaju obratite panju koja je ekstenzija uèitana!',
		'emconf_rootfiles.alttitle' => 'Poèetne datoteke',
		'emconf_rootfiles.description' => 'Popis datoteka u direktoriju ekstenzije. Ne prikazuje datoteka u poddirektorijima.',
		'emconf_dbReq.alttitle' => 'Zahtjevi bazi podataka',
		'emconf_dbReq.description' => 'Ako postoje prikazuje zahtjeve tablicama i poljima baze podataka.',
		'emconf_dbReq.details' => 'Ovo æe iz datoteka ext_tables.sql i ext_tables_static+adt.sql proèitati i prikazati koje su tablice, polja i statièke tablice potrebne ovoj ekstenziji.',
		'emconf_dbStatus.alttitle' => 'Status zahtjeva bazi podataka.',
		'emconf_dbStatus.description' => 'Prikazuje trenutni status baze podataka s obzirom na zahtjeve ekstenzije.',
		'emconf_dbStatus.details' => 'Ako se ekstenzija uèita pojaviti æe se poruka greške ako potrebna polja ili tablice nisu prisutni u bazi podataka.',
		'emconf_flags.alttitle' => 'Zastavice',
		'emconf_flags.description' => 'Popis posebnih kodova koji vam govore na koje dijelove TYPO3 sustava ekstenzija utjeèe.',
		'emconf_flags.details' => 'Ovo je popis zastavica:

<b>Module:</b> Pravi backend main/sub modul je pronağen

<b>Module+:</b> Ekstenzija dodaje samu sebe funkcijskom 
meniju postojeæeg backend modula

<b>loadTCA:</b> Ekstenzija sadri funkcijski poziv prema 
t3lib_div::loadTCA za uèitavanje tablice. To potencijalno moe
usporiti sistem zato jer je potpun opis tablice uvijek ukljuèen u
samu tablicu. U svakom sluèaju vjerojatno postoji dobar razlog 
za takav postupak Vjerojatno ekstenzija pokušava manipulirati 
TCA-config za postojeæu tablicu kako bi je proširila.

<b>TCA:</b> Ekstenzija sadri konfiguraciju tablice u $TCA.

<b>Plugin:</b> Ekstenzija dodaje frontend plugin listi pluginova 
u Sadrajni Element tipa "Dodaj Plugin"

<b>Plugin/ST43:</b> TypoScript kod za prikazivanje plugin-a 
dodan je statièkom predlošku "Sadraj (osnovni)". "Plugin" i 
"Plugin/ST43" se obièno koriste zajedno.

<b>Page-TSconfig:</b> Osnovna Stranica-TSconfig je dodana.

<b>TS/Setup:</b> Osnovne TypoScript Postavke su dodane.

<b>TS/Constants:</b> Osnovne TypoScript Konstante su dodane.',
		'emconf_conf.description' => 'Pokazuje da li ekstenzija ima predloak za daljnju konfiguraciju',
		'emconf_TSfiles.alttitle' => 'Statiène TypoScript datoteke',
		'emconf_TSfiles.description' => 'Pokazuje koji TypoScript statiène datoteke mogu biti prisutne.',
		'emconf_TSfiles.details' => 'Ako su datoteke ext_typoscript_constants.txt i/ili ext_typoscript_setup.txt pronağene u direktoriju ekstenzije, one se ukljuèuju u hijerarhiju svih TypoScript predloaka u TYPO3 sustavu odmah nakon preostalih ukljuèenih statièkih predloaka.',
		'emconf_locallang.alttitle' => 'locallang-datoteke',
		'emconf_locallang.description' => 'Prikazuje koje su datoteke nazvane "locallang.php" prisutne u direktoriju ekstenzije (rekurzivna pretraga). Takve datoteke se uobièajeno koriste da dodijele nizu $LOCAL_LANG  oznake koje se koriste u sistemskim jezicima.',
		'emconf_moduleNames.alttitle' => 'Imena Backend Modula',
		'emconf_moduleNames.description' => 'Pokazuje koja su imena modula pronağena unutar ekstenzije.',
		'emconf_classNames.alttitle' => 'Imena PHP klasa',
		'emconf_classNames.description' => 'Pokazuje koje su PHP klase pronağene u .php i .inc datotekama.',
		'emconf_errors.alttitle' => 'Greške',
		'emconf_errors.description' => 'Prikazuje otkrivene ozbiljne greške u ekstenziji.',
		'emconf_NSerrors.alttitle' => 'Greške pri imenovanju',
		'emconf_NSerrors.description' => 'Odreğene konvencije imenovanja odnose se na ekstenzije. Ovdje se prikazuju pronağena prekršenja.',
		'emconf_NSerrors.details' => 'Konvencije imenovanja definirane su u "Inside TYPO3" dokumentu. Da bi se davanje imena maksimalno pojednostavilo, pokušajte izbjeæi "underscore" znak u vašim kljuèevima ekstenzija.',
	),
	'hu' => Array (
		'emconf_shy.alttitle' => 'Shy',
		'emconf_shy.description' => 'Ha be van állítva, a kiterjesztés alapértelmezetten rejtett lesz a Bõvítménykezelõben, mert ez alapértelmezett kiterjesztés vagy más valami lehet, amely nem annyira fontos.',
		'emconf_shy.details' => 'Ezt a jelzõt akkor használd, ha a kiterjesztés \'kevésbé érdekes\'
(ami nem jelenti azt, hogy nem fontos - csak a kiterjesztés
ritkán használatos...)
Ez nem érinti azt, hogy ez engedélyezve van-e vagy sem.
Csak a Bõvítménykezelõben látható.
Hagyományosan a shy egy a TYPO3_CONF_VARS-nak
megfelelõ alapértelmezetten betöltõdõ kiterjesztéshalmaz.',
		'emconf_category.alttitle' => 'Kategória',
		'emconf_category.description' => 'Melyik kategóriához tartozzon a kiterjesztés.',
		'emconf_category.details' => '<b>be:</b> Backend (általánosan backend orientált, de nem modul) 

<b>modul:</b> Backend modulok (amikor valami egy modul vagy ahhoz csatlakozik)

<b>fe:</b> Frontend (általánosan backend orientált, de nem “valódi” beépülõ)

<b>beépülõ:</b> Frontend beépülõk (“Beszúrt beépülõ”tartalmi elemként beszúrt beépülõ) 

<b>vegyes:</b> Vegyes dolog (máshova nem besorolt)

<b>példa:</b> péda kiterjesztés (példaként stb. szolgálnak)',
		'emconf_dependencies.alttitle' => 'Függések más kiterjesztésektõl',
		'emconf_dependencies.description' => 'Ez egy egyéb kiterjesztés kulcslista, amelynek tartalma a már korábban betöltött kiterjesztésektõl függ.',
		'emconf_dependencies.details' => 'A Bõvítménykezelõ kezeli a localconf.php-ba íródó kiterjesztések függõségeit.',
		'emconf_conflicts.alttitle' => 'Ütközések más kiterjesztésekkel?',
		'emconf_conflicts.description' => 'Kiterjesztések kucslistája, amellyel a kiterjesztés nem dolgozik össze (és így nem engedélyezhetõek csak a megfelelõ kiterjesztés törlésével)',
		'emconf_priority.alttitle' => 'Lekérdezett betöltési elsõbbség',
		'emconf_priority.description' => 'Meghatározza a Bõvítménykezelõ számára, hogy a kiterjesztéseket a lista elejére tegye. Alapértelmezésként a végére helyezi.',
		'emconf_module.alttitle' => 'Betöltött backend modulok',
		'emconf_module.description' => 'Ha bármely, a kiterjesztéshez tartozó alkönyvtár tartalmaz backend modult, ezek a könyvtár nevek itt lesznek kilistázva.',
		'emconf_module.details' => 'Engedélyezi a Bõvítménykezelõnek, hogy tudjon a modul létezésérõl, ami fontos, mert a Bõvítménykezelõnek frissíteni kell a modul conf.php fájlját azért, hogy helyesen tudja beállítani a TYPO3_MOD_PATH konstanst.',
		'emconf_state.alttitle' => 'Fejlesztési állapot',
		'emconf_state.description' => 'Jelzi, hogy a kiterjesztés milyen fejlesztési állapotban van.',
		'emconf_state.details' => '<b>alfa</b>
Nagyon kezdeti állapot. Valószinûleg még semmi sem történt.

<b>béta</b>
Éppen fejlesztés alatt. Részben mûködhet, de még nem befejezett.

<b>stabil</b>
Stabil és használat alatt a termékben

<b>kísérleti</b>
Senki nem tudja, mûködik-e... Talán csak egy ötlet...

<b>teszt</b>
Teszt kiterjesztés, koncepciókat mutat be stb.',
		'emconf_internal.alttitle' => 'A magban belsõleg támogatott',
		'emconf_internal.description' => 'Ez a jelzõ jelzi, hogy a mag forráskódja speciálisan tud a kiterjesztésrõl.',
		'emconf_internal.details' => 'A szavak sorrendjében ennek a jelzõnek kell továbbítani azt
az üzenetet, hogy “ez a kiterjesztés nem írható meg bizonyos
mag forráskód változtatás nélkül“.
Egy kiterjesztés nem tekinthetõ belsõnek csak azért, mert a
TYPO3 általános osztályai használják, azaz a t3lib/.-bõl.
A valódi nem belsõ kiterjesztések azzal jellemezhetõek, hogy
megírhatóak a mag forráskód változtatása nélkül, csak
felhasználják a TYPO3 meglevõ osztályait és/vagy
kiterjesztéseit, valamint kiterjesztés könyvtárában levõ saját
szkripteket.',
		'emconf_clearCacheOnLoad.alttitle' => 'Telepítés után a cache törlése',
		'emconf_clearCacheOnLoad.description' => 'Ha be van állítva, a Bõvítménykezelõ kitakarítja a gyorsítótárat a kiterjesztés telepítésekor.',
		'emconf_modify_tables.alttitle' => 'Létezõ megváltozott táblák',
		'emconf_modify_tables.description' => 'Táblanevek listája, amelyeket ez a kiterjesztés csak módosít, de nem teljesen készít el.',
		'emconf_modify_tables.details' => 'A kiterjesztés ext_tables.sql fájljában található táblalista.',
		'.alttitle' => 'BK',
		'.description' => 'Bõvítménykezelõ (BK)',
		'.details' => 'ATYPO3 kiterjeszthetõ gyakorlatilag bármely irányban a felülrõl
kompatilbilitás elvesztése nélkül. A Kiterjesztési Programozói
Felület hatékony keretrendszert nyújt a TYPO3 részére az ilyen
kiterjesztések hozzáadásához, törléséhez, telepítéséhez,
fejlesztéséhez. Ezt a Bõvítménykezelõ (BK) hatékonyan
támogatja a TYPO3.on belûl.

A “kiterjesztés“ egy fogalom a TYPO3-on belûl, amely két
másik fogalmat takar, a bõvítményt és a modult.

A bõvítmény egy szerepet játszik el magán a webhelyen. Azaz
üzenõfalat, vendégkönyvet, boltot stb. Általában PHP osztályba
ágyazott és a USER vagy a USER_INT cObject-en keresztûl
hívja meg a TypoScript. A bõvítmény a frontenden egy
kiterjesztés.

A modul egy backend alkalmazás, amelynek saját pozíciója
van az adminisztrációs menüben. Backend bejelentkezést
igényel és a backend keretrendszeren belül mûködik.
Meghívhatunk valamit modulként akkor is, ha ez bontja
egy létezõ modul kapcsolódását, ez egyszerûen behelyezi
önmagát a meglévõ modulok függvény menüjébe. A modul
a backenden egy kiterjesztés.',
		'emconf_private.alttitle' => 'Privát',
		'emconf_private.description' => 'Ha be van állítva, ez a verzió nem jelenik meg az online tár nyilvános listájában.',
		'emconf_private.details' => 'A "privát" feltöltések egy olyan manuálisan begépelt kulcs
megadását igénylik (amely a feltöltés befejeztével megjelenik)
a feltöltött kiterjesztés importálásának vagy a részletek
megtekinthetõségének érdekében.
Ez egy nagyon klassz dolog, amikor valami belsõ dolgon
dolgozol és nem szeretnéd, ha ezt mások is látnák.
A privát jelzõt be- és kikapcsolhatod valahányszor
feltöltöd a kiterjesztésedet.',
		'emconf_download_password.alttitle' => 'Letöltési jelszó',
		'emconf_download_password.description' => 'Privát kiterjesztések letöltéséhez szükséges kiegészítõ jelszó.',
		'emconf_download_password.details' => 'Aki ismeri a privát feltöltéshez rendelt kulcsot, importálhatja azt. Az import jelszó megadása a letöltési kulcsot adja meg és továbbiakban egy jelszó is szükséges. A jelszó a késõbbiekben megváltoztatható.',
		'emconf_type.alttitle' => 'Telepítési típus',
		'emconf_type.description' => 'A telepítés típusa',
		'emconf_type.details' => 'A kiterjesztés fájljai a kiterjesztés kulcsa alapján elnevezett
könyvtárban vannak. E könyvtár helye vagy a  typo3/sysext/,
a typo3/ext/ vagy a typo3conf/ext/ valamelyike.
A kiterjesztésnek programozottnak kell lennie, így
automatikusan érzékeli, hogy hol található és mûködhet mind
a három helyrõl.

<b>Lokális hely “typo3conf/ext/”:</b> Itt találhatóak azok
a kiterjesztések, amelyek egy bizonyos TYPO3 telepítésre
nézve lokálisak. A typo3conf/ könyvtár mindig helyi, helyi
konfigurációt (lásd localconf.php), helyi modulokat stb.
tartalmaz. Az ide elhelyezett kiterjesztés csak ezen TYPO3
telepítés számára lesz elérhetõ. Ez az adatbázis telepítés 
“adatbázis-alapú” módja.

<b>Globális hely “typo3/ext/”:</b> Itt olyan kiterjesztések
találhatóak, amelyek a TYPO3 forráskód szempontjából
globálisak a webszerveren. Ezek a kiterjesztések elérhetõek
bármelyik TYPO3 telepítés részére megosztva a forráskódot.
Ha frissül a TYPO3 forráskód, célszerû a régi typo3/ext/
könyvtárat átmásolni az új telepítésbe, felülírva az eredetit.
Ezen a módon az összes használatos globális kiterjesztés az 
új verzió forráskódjában is megjelenik. Ezek után mindig lehet
frissíteni a kívánt verziókat. Ez az adatbázis telepítés 
“szerver-alapú” módja.

<b>Rendszerszintû hely “typo3/sysext/”:</b> Ez a rendszer
alap kiterjesztéseinek a helye, amelyet nem lehet és nem 
ajánlatos frissíteni a Kiterjesztés Menedzser segítségével.

<b>Betöltési elsõbbség:</b> A helyi kiterjesztések egyfajta
elsõbbséggel bírnak, aza, ha egy kiterjesztés létezik mind a
typo3conf/ext/ and typo3/ext/ könyvtárban, akkor a
typo3conf/ext/ -beli töltõdik be. Hasonlóan a globális
kiterjesztések szintén elsõbbséggel bírnak a rendszerszintû
kiterjesztésekkel szemben. Ez azt jelenti, hogy a kiterjesztések
a lokális-globális-rendszerszintû sorrendben töltõdnek be.
Ilyen módon egy telepített kiterjesztésnek stabil verziója a 
globális könyvtárban van (typo3/ext/), amelyet a szerveren
levõ bármely projekt használhat megosztva a forráskódot, de
egy egyszerû kísérleti projektben ugyannak a kiterjesztésnek
egy újabb verziója importálható be a projektnek megfelelõen
helyi kiterjesztésként a régebbi helyett.',
		'emconf_doubleInstall.alttitle' => 'Kétszer vagy többször telepített?',
		'emconf_doubleInstall.description' => 'Megmondja, hogy egy kiterjesztés telepítve van-e egynél többször a System, Global vagy Local helyek egyikében.',
		'emconf_doubleInstall.details' => 'Mivel a kiterjesztés három helyen fordulhat elõ (System, Global, Local), jelzi, ha a kiterjesztés a jelenleginél eltérõ helyen található. Ebben az esetben ügyelni kell arra, hogy a kiterjesztések melyikét töltjük be.',
		'emconf_rootfiles.alttitle' => 'Gyökér fájlok',
		'emconf_rootfiles.description' => 'Fájlok listája a kiterjesztés könyvtárában. Nem listázza az alkönyvtárbakban levõ fájlokat.',
		'emconf_dbReq.alttitle' => 'Adatbázis szükséges',
		'emconf_dbReq.description' => 'Jelzi az adatbázis táblák és mezõk szükségességét, ha van ilyen.',
		'emconf_dbReq.details' => 'Ez az ext_tables.sql és az ext_tables_static+adt.sql fájlokból kerül beolvasásra és megmutatja, mely táblák, mezõk és statikus táblák szükségesek a kiterjesztés részére.',
		'emconf_dbStatus.alttitle' => 'Adatbázis szükségességi állapot',
		'emconf_dbStatus.description' => 'Kijelzi az adatbázis jelen állapotát összevetve a szükséges kiterjesztésekkel.',
		'emconf_dbStatus.details' => 'Ha egy kiterjesztés olyan töltõdött be, amely hibaüzenetet jelenít meg, amennyiben bizonyos szükséges táblák és mezõk nincsenek meg.',
		'emconf_flags.alttitle' => 'Jelzõk',
		'emconf_flags.description' => 'Speciális kódolású lista, amely megmondja, hogy a kiterjesztés a TYPO3 mely részeit érinti.',
		'emconf_flags.details' => 'Ez jelzõlista:

<b>Modul:</b> Egy valódi backend fõmodul vagy almodul
található hozzáadásra.

<b>Modul+:</b> A kiterjesztés hozzáadja önmagát egy létezõ
backend modul funkciómenüjéhez.

<b>loadTCA:</b> A kiterjesztés tartalmaz
egy t3lib_div::loadTCA  nevû függvényt egy tábla betöltésére.
Ez potenciálisan azt jelenti, hogy a rendszer lelassul, mert 
valamely tábla teljes táblaleírása mindig betöltõdik. Ennek meg
van a maga oka. A kiterjesztés megpróbálja manipulálni egy
létezõ tábla részére a TCA konfigurációt, hogy kiterjessze azt.

<b>TCA:</b>  A kiterjesztés a $TCA-ban tartalmazza egy
tábla konfigurációját.

<b>Bõvítmény:</b> A kiterjesztés hozzáad egy frontend
beépülõt a Tartalmi Elemek "Beszúrt bõvítmények" részének
bõvítménylistájába.

<b>Bõvítmény/ST43:</b> TypoScript generált kód a bõvítmény
részére, amely hozzáadódik a staikus "Tartalom (alap)"
sablonhoz. A "Bõvítmény" és a "Bõvítmény/ST43" általában együtt
használatos.

<b>Oldal-TSconfig:</b> Alapértelmezett Oldal-TSconfig
hozzáadva.

<b>Felhasználó-TSconfig:</b> Alapértelmezettt
Felhasználó-TSconfig hozzáadva.

<b>TS/Telepítés:</b> Alapértelmezett TypoScript
telepítés hozzáadva.

<b>TS/Konstansok:</b> Alapértelmezett TypoScript
konstansok hozzáadva.',
		'emconf_conf.description' => 'Jelzi, hogy a kiterjesztésnek van-e további alsószintû konfigurációs sablonja.',
		'emconf_TSfiles.alttitle' => 'Statikus TypoScript fájlok',
		'emconf_TSfiles.description' => 'Kijelzi. hogy mely statikus TypoScript fájlok vannak jelen.',
		'emconf_TSfiles.details' => 'Ha a ext_typoscript_constants.txt és/vagy ext_typoscript_setup.txt fájlok megtalálhatóak a kiterjesztés könyvtárában, akkor ezek beépülnek a TYPO3 összes TypoScript sablon hierarhiájába közvetlenül a más statikus sablonokba való beépülés után.',
		'emconf_locallang.alttitle' => 'locallang fájlok',
		'emconf_locallang.description' => 'Kijelzi, hogy a helyi nyelvi fájlnak nevezett fájlok vannak jelen a kiterjesztés könyvtárában (rekurzív kereséssel). Az ilyen fájlok rendszerint a $LOCAL_LANG tömb címkéit jelenítik meg a rendszer nyelvének megfelelõen.',
		'emconf_moduleNames.alttitle' => 'Backend modul nevek',
		'emconf_moduleNames.description' => 'Kijelzi, melyik modulnév található a kiterjesztésen belül.',
		'emconf_classNames.alttitle' => 'PHP osztály nevek',
		'emconf_classNames.description' => 'Kijelzi, hogy mely PHP osztályok találhatóak a .php és .inc fájlokban.',
		'emconf_errors.alttitle' => 'Hibák',
		'emconf_errors.description' => 'Kijelzi, ha valamilyen komoly hiba merült fel a kiterjesztéssel kapcsolatban.',
		'emconf_NSerrors.alttitle' => 'Névhely hibák',
		'emconf_NSerrors.description' => 'Bizonyos névkonvenciókat alkalmaz a kiterjesztésekre. Kijelzi, ha bármilyen ütközés merült fel.',
		'emconf_NSerrors.details' => 'A névkonverziók az "Inside TYPO3" dokumentumban olvashatóak. Próbálj a lehetõ legegyszerûbb elnevezést adni, lehetõleg nem alkalmazva az aláhúzás jelet ( _ ).',
	),
	'gl' => Array (
		'emconf_shy.alttitle' => 'Toqqoqqasoq',
	),
	'th' => Array (
	),
	'gr' => Array (
	),
	'hk' => Array (
		'emconf_shy.alttitle' => 'ÁôÂÃ',
		'emconf_shy.description' => '°²¦p³]©w¤F¡A©µ¦ù¤u¨ã¥¿±`·|¦b©µ¦ù¤u¨ãºŞ²z­û¤¤³QÁôÂÃ¡A¦]¬°¥¦¥i¯à¬O¤@­Ó¹w³]ªº©µ¦ù¤u¨ã©Î¤£µM¬O¤@¨Ç¤£«Ü­«­nªºªF¦è',
		'emconf_shy.details' => '°²¦p¤@­Ó©µ¦ù¤u¨ã¤£¬O¤Ó¤Ş°_¿³½ì¡]¤£¦P©ó¤£­«­n - ¥u¬O¤@­Ó¤£±`¦³¤H´M§äªº©µ¦ù¤u¨ã¡^¡A´N¨Ï¥Î³o­Ó¼Ğ°O
³o¤£¼vÅT©µ¦ù¤u¨ã¬O§_±Ò°Ê¡C¥u¬O¦b©µ¦ù¤u¨ãºŞ²z­û¤¤Åã¥Ü¡C¥¿±`ªº»¡¡A®Ú¾ÚTYPO3_CONF_VARS¡A©Ò¦³¹w³]¸ü¤Jªº©µ¦ù¤u¨ã³£³]©w¬°ÁôÂÃ',
		'emconf_category.alttitle' => 'Ãş§O',
		'emconf_category.description' => '©µ¦ù¤u¨ã©ÒÄİÃş§O',
		'emconf_category.details' => '<b>be:</b>«á¶Ô
¡]¤@¯ë¬°«á¶Ô¾É¦V¡A¦ı¨Ã«D¼Ò²Õ¡^

<b>module:</b>«á¶Ô¼Ò²Õ¡]·í¦³¨ÇªF¦è¬O¼Ò²Õ©Î³s±µ¨ì¼Ò²Õ¡^

<b>fe:</b>«e¸m
¡]¤@¯ë¬°«e¸m´¡¤J¦¡¸Ë¸m¡^


<b>plugin:</b>«e¸m´¡¤J¦¡¸Ë¸m¡]´¡¤J¦¡¸Ë¸m³Q¥[¤J¬°¤¸¥ó¡^

<b>misc:</b>¨ä¥L¡]¤£¯à»´©ö¤ÀÃş¡^

<b>example:</b>©µ¦ù¤u¨ã½d¨Ò¡]§@¬°½d¨Òµ¥¡^',
		'emconf_dependencies.alttitle' => '¨ä¥L©µ¦ù¤u¨ãªº¬Û¨Ì©Ê¡H',
		'emconf_dependencies.description' => '³o­Ó©µ¦ù¤u¨ã©Ò­Ê¿àªº¨ä¥L©µ¦ù¤u¨ãÆ_¦r¦W³æ¡A¦b¸ü¤J¦Û¤v«e¥ı¸ü¤J¦¹¦W³æ',
		'emconf_dependencies.details' => '·í§â©µ¦ù¤u¨ã¦W³æ¼g¶i localconf.php ®É¡A©µ¦ù¤u¨ãºŞ²z­û·|³B²z¬Û¨Ì©Ê',
		'emconf_conflicts.alttitle' => '»P¨ä¥L©µ¦ù¤u¨ã½Ä¬ğ¡H',
		'emconf_conflicts.description' => '¤£¯à»P³o­Ó©µ¦ù¤u¨ã¤@°_¹B§@ªº©µ¦ù¤u¨ãÆ_¦r¦W³æ¡]¦]¦¹¦b³o¨Ç¤u¨ã¤£³Q¸Ñ°£¦w¸Ë¤§«e¡A©µ¦ù¤u¨ã¤£¯à°÷³Q±Ò°Ê¡^',
		'emconf_priority.alttitle' => '­n¨D¸ü¤JÀu¥ı©Ê',
		'emconf_priority.description' => '³qª¾©µ¦ù¤u¨ãºŞ²z­û¹Á¸Õ§â©µ¦ù¤u¨ã©ñ¨ì¦W³æªº³Ì«e­±¡C¹w³]¬O©ñ¨ì³Ì«áªº',
		'emconf_module.alttitle' => '³Q¥]¬Aªº«á¶Ô¼Ò²Õ',
		'emconf_module.description' => '°²¦p¥ô¦ó¤@­Ó©µ¦ù¤u¨ãªº¤l¸ê®Æ§¨¥]§t«á¶Ô¼Ò²Õ¡A³o¨Ç¸ê®Æ§¨ªº¦WºÙÀ³·|¦b³o¸Ì³Q¦C¥X',
		'emconf_module.details' => '®e³\\¡u©µ¦ù¤u¨ãºŞ²z­û¡vª¾¹D¼Ò²Õªº¦s¦b¡A¬O­«­nªº¡A¦]¬°¡u©µ¦ù¤u¨ãºŞ²z­û¡v¬°¤F³]©w¥¿½TªºTYPO3_MOD_PATH±`­È¡A»İ­n§ó·s¼Ò²Õªºconf.phpÀÉ',
		'emconf_state.alttitle' => 'µo®iª¬ºA',
		'emconf_state.description' => '©µ¦ù¤u¨ã³B©ó¨º¤@­Óµo®iª¬ºA',
		'emconf_state.details' => '<b>alpha</b>
«D±`ªì´Áªºµo®i¡C
¤]³\\§¹¥ş¤£°µ¥ô¦ó¨Æ¡C

<b>beta</b>
¥¿¦bµo®i¤¤
À³¸Ó¦³­­¦a¹B§@,¦ı¬O¤´¥¼§¹¦¨

<b>stable</b>
Ã­©w©M¥i§¹¥ş¹B§@

<b>experimental</b>
ÁÙ¨S¦³¤Hª¾¹D·|µo¥Í¬Æ»ò¨Æ...¤]³\\¤´¬O¥u¬O¤@­Ó·N©À

<b>test</b>
´ú¸Õ©µ¦ù¤u¨ã¡AÅã¥Ü·§©Àµ¥',
		'emconf_internal.alttitle' => '¦b®Ö¤ß¤º³¡¤ä´©',
		'emconf_internal.description' => '³o°O¸¹«ü¥X®Ö¤ß·½½X¬O¯S§O¦a¯d·N©µ³o­Ó©µ¦ù¤u¨ã',
		'emconf_clearCacheOnLoad.alttitle' => '·í¦w¸Ë®É¡A²M°£§Ö¨úÀÉ®×',
		'emconf_clearCacheOnLoad.description' => '°²¦p³]©w¤F¡A·í¦w¸Ë©µ¦ù¤u¨ã®É¡A©µ¦ù¤u¨ãºŞ²z­û´N·|­n¨D²M°£§Ö¨úÀÉ®×',
		'emconf_modify_tables.alttitle' => '²{¦s¸ê®Æªí³Q­×§ï',
		'emconf_modify_tables.description' => '¥u¯à°÷³Q³o­Ó©µ¦ù¤u¨ã­×§ï¡]¤£¬O§¹¥şªº«Ø¥ß¡^ªº¸ê®Æªí¦WºÙªº¦W³æ',
		'emconf_modify_tables.details' => '¦b©µ¦ù¤u¨ãªºext_tables.sqlÀÉ®×¤¤§ä¨ìªº³o­Ó¦W³æ¤¤ªº¸ê®Æªí',
		'.alttitle' => 'EM',
		'.description' => '©µ¦ù¤u¨ãºŞ²z­û(EM¡^',
		'emconf_private.alttitle' => '¨p¤H',
		'emconf_private.description' => '°²¦p©w¤F¡A³o­Óª©¥»¤£·|Åã¥Ü©ó½u¤W¦¬ÂÃ®wªº¤½¶}¦W³æ¤¤',
		'emconf_download_password.alttitle' => '¤U¸ü±K½X',
		'emconf_download_password.description' => '­n¤U¸ü¨p¤H©µ¦ù¤u¨ã´N»İ­nªş¥[ªº±K½X',
		'emconf_type.alttitle' => '¦w¸ËÃş«¬',
		'emconf_type.description' => '¦w¸ËÃş«¬',
		'emconf_doubleInstall.alttitle' => '¦w¸Ë¨â¦¸©Î¥H¤W¡H',
		'emconf_doubleInstall.description' => '³qª¾§A©µ¦ù¤u¨ã¬O§_¦b¨t²Î¤¤¦w¸Ë¦h©ó¤@¦¸¡A¥şÅé¦a©Î¥»¦a¦ì¸m',
		'emconf_doubleInstall.details' => '¦]¬°¤@­Ó©µ¦ù¤u¨ã¥i¥H³B¨­©ó¤T­Ó¦ì¸m¡A¨t²Î¡N¾ãÅé©M¥»¦a¡A³o«ü¥X©µ¦ù¤u¨ã¬O§_³Qµo²{³B¨­©ó²{¦³¦ì¸m¥H¥~¡C¦b³o±¡ªp¤U§AÀ³¸Ó·N¨º¤@­Ó©µ¦ù¤u¨ã¤w¸g³Q¸ü¤J¡C',
		'emconf_rootfiles.alttitle' => '®ÚÀÉ®×',
		'emconf_rootfiles.description' => '¦b©µ¦ù¤u¨ã¤¤ªºÀÉ®×¦W³æ¡C¨S¦³¦C¥X¤l¸ê®Æ§¨¤¤ªºÀÉ®×',
		'emconf_dbReq.alttitle' => '¸ê®Æ®w­n¨D',
		'emconf_dbReq.description' => '¦V§AÅã¥Ü¹ï¸ê®Æ®wªí®æ©MÄæªº­n¨D¡]¦p¦³¡^',
		'emconf_dbReq.details' => '³o·|±qext_tables.sql ÀÉ©M ext_tables_static+adt.sql ÀÉÅª¤J¡A¨Ã¥B¦V§AÅã¥Ü©µ¦ù¤u¨ã»İ­n¨º¨Ç¸ê®Æªí¡NÄæ¦ì©MÀRºA®Æªí',
		'emconf_dbStatus.alttitle' => '¸ê®Æ®w­n¨Dª¬ªp',
		'emconf_dbStatus.description' => 'Åã¥Ü¸ê®Æ®wªº¥Ø«eª¬ºA¡A»P©µ¦ù¤u¨ã©Ò­n¨Dªº§@¤ñ¸û',
		'emconf_dbStatus.details' => '°²¦p©µ¦ù¤u¨ã³Q¸ü¤J¡A°²¦p¦³¸ê®Æªí©ÎÄæ¨Ã¤£¦p©Ò®Æ¥X²{¦b¸ê®Æ®w¤¤´N·|Åã¥Ü¿ù»~°T®§',
		'emconf_flags.alttitle' => '°O¸¹',
		'emconf_flags.description' => '¯S§O½Xªº¦W³æ¡A¥¦­Ì·|§i¶D§A¦³Ãö©µ¦ù¤u¨ã·|Ä²¤ÎTYPO3ªº¨º¨Ç³¡¤À',
		'emconf_conf.description' => 'Åã¥Ü©µ¦ù¤u¨ã¬O§_¦³¼Ëª©¥H¶i¦æ§ó§C¼h¦¸ªº³]©w',
		'emconf_TSfiles.alttitle' => 'ÀRºAªºTypoScriptÀÉ®×',
		'emconf_TSfiles.description' => 'Åã¥Ü¨º¤@­ÓTypoScriptÀRºAÀÉ®×·|¥X²{',
		'emconf_TSfiles.details' => '°²¦pext_typoscript_constants.txtÀÉ©M¡ş©Î ext_typoscript_setup.txt¦b©µ¦ù¤u¨ãªº¸ê®Æ§¨³Qµo²{¡A¥L­Ì´N³Q¬A¦bTYPO3©Ò¦³TypoScript¼Ëª©ªº¶¥¯Å²ÕÃÑ¤¤¡A´N¦b¨ä¥LÀRºA¼Ëª©ªº¤º®e¤§«á',
		'emconf_locallang.alttitle' => 'locallang-files',
		'emconf_locallang.description' => 'Åã¥Ü¨º¤@¨Ç©RºÙ¬°¡ulocallang.php¡vªºÀÉ®×¥X²{©ó©µ¦ù¤u¨ãªº¸ê®Æ§¨¤¤¡]»¼¶iªº·j´M¡^¡C³o¨ÇÀÉ®×¤@¯ë¥X²{©ó¤@­Óarray$LOCAL_LANG¡A¤º¦³¨t²Î»y¨¥ªº¼ĞÅÒ',
		'emconf_moduleNames.alttitle' => '«á¶Ô¼Ò²Õ¦WºÙ',
		'emconf_moduleNames.description' => 'Åã¥Ü¦b©µ¦ù¤u¨ã¤¤§ä¨ì¨º­Ó¼Ò²Õ',
		'emconf_classNames.alttitle' => 'PHP Class ¦WºÙ',
		'emconf_classNames.description' => 'Åã¥Ü¦b.php ©M .inc ÀÉ®×¤¤§ä¨ì¨º¨Ç PHP-Class',
		'emconf_errors.alttitle' => '¿ù»~',
		'emconf_errors.description' => 'Åã¥Ü¦³§_µo²{©µ¦ù¤u¨ã¦³¥ô¦óÄY­«ªº¿ù»~',
		'emconf_NSerrors.alttitle' => '¦WºÙªÅ¹j¿ù»~',
		'emconf_NSerrors.description' => '¬Y¨Ç©R¦W¤èªkÀ³¥Î¨ì©µ¦ù¤u¨ã¡C³oÅã¥Üµo²{¨ìªº¥ô¦ó¤£²Å',
		'emconf_NSerrors.details' => '©R¦W¤èªk¦b¡uInside TYPO3¡v¤¤¤w©w¸q¤F¡C¬°¤F¨Ï©R¦WºÉ¶qÂ²³æ¡A¹Á¸ÕÁ×§K¦b§Aªº©µ¦ù¤u¨ãÆ_¦r¤¤¥[¤W©³½u',
	),
	'eu' => Array (
	),
	'bg' => Array (
		'emconf_shy.alttitle' => 'Shy',
		'emconf_shy.description' => 'Àêî å ñëîæåíî, ğàçøèğåíèåòî îáèêíîâåííî ùå å ñêğèòî â ÌĞ, çàùîòî ìîæå äà å ïî-ïîäğàçáèğàíå èëè â äğóã ñëó÷àé íåùî êîåòî íå å òîëêîâà âàæíî.',
		'emconf_shy.details' => 'Èçïîëçâàéòå òîçè ôëàã àêî ğàçğåøåíèå å ñ  \'ğÿäúê èíòåğåñò\' 
(êîåòî íå å ñúùîòî êàòî íåâàæåí - ïğîñòî ğàçøèğåíèå êîåòî
 íå å ÷åñòî òúğñåíî...)
Òîâà íå çàñÿãà äàëè å èëè íå å ïîçâîëåíî (enabled) 
ğàçøèğåíèåòî. 
Ñàìî å ïîêàçàíî â ÌĞ.
Îáèêíîâåííî "shy" å ñëîæåíî çà âñè÷êè çàğåäåíè 
ïî-ïîäğàçáèğàíå ğàçøèğåíèÿ ñúîáğàçíî ñ TYPO3_CONF_VARS.',
		'emconf_category.alttitle' => 'Êàòåãîğèÿ',
		'emconf_category.description' => 'Êúì êîÿ êàòåãîğèÿ ïğèíàäëåæè ğàçøèğåíèåòî (extension).',
		'emconf_category.details' => '<b>be:</b> Backend (Îáèêíîâåííî backend îğèåíòèğàíî, íî íå ìîäóë) 

<b>ìîäóë:</b> Backend ìîäóëè (Êîãàòî íåùî å ìîäóë èëè å ñâúğçàíî ñ åäèí ìîäóë)

<b>fe:</b> Frontend (Îáèêíîâåííî frontend îğèåíòèğàí, íî íå "èñòèíñêè" plugin)

<b>plugin:</b> Frontend plugins (Plugins âìúêíàò êàòî “Âìúêíàò Plugin” åëåìåíò íà ñúäúğæàíèå) 

<b>misc:</b> Ğàçëè÷íè íåùà (Êúäåòî äğóãàäå íå ëåñíî ñëîæåíè)

<b>ïğèìåğ:</b> Ïğèìåğíî ğàçøèğåíèå ( Êîéòî ñëóæàò êàòî ïğèìåğè äğ.)',
		'emconf_dependencies.alttitle' => 'Çàâèñèìîñò îò äğóãè ğàçøèğåíèÿ (extension)?',
		'emconf_dependencies.description' => 'Òîâà å ñïèñúê ñ äğóãè êëş÷îâå íà ğàçøèğåíèÿ îò êîéòî òåçè ğàçøèğåíèÿ çàâèñÿò ÏĞÅÄÈ äà áúäàò çàğåäåíè ñàìèòå òå.',
		'emconf_dependencies.details' => 'ÌĞ ùå óïğàâëÿâà òàçè çàâèñèìîñò äîêàòî ñå çàïèñâà ñïèñúêà íà ğàçøèğåíèÿòà â localconf.php',
		'emconf_conflicts.alttitle' => 'Êîíôëèêò ñ äğóãè ğàçøèğåíèÿ (extension)?',
		'emconf_conflicts.description' => 'Ñïèñúê íà êëş÷îâå íà ğàçøèğåíèÿ ñ êîéòî òå íå ğàáîòÿò (è òàêà íå ìîãàò äà áúäàò èçïîëçâàíè ïğåäè äğóãèòå íå áúäàò äåèíñòàëèğàíè)',
		'emconf_priority.alttitle' => 'Èñêàíèÿ ïğèîğèòåò ïğè çàğåæäàíå',
		'emconf_priority.description' => 'Òîâà êàçâà íà ÌĞ äà îïèòà äà ñëîæè ğàçøèğåíèåòî íà ïúğâîòî ìÿñòî â ñïèñúêà. Ïî-ïîäğàçáèğàíå å ïîñëåäíîòî.',
		'emconf_module.alttitle' => 'âêëş÷èòåëíî Backend ìîäóëè',
		'emconf_module.description' => 'Àêî íÿêîÿ ïîäïàïêà êúì ğàçøèğåíèå ñúäúğæà backend ìîäóëè, èìåíàòà íà òåçè ïàïêè òğÿáâà äà ñà èçïèñàíè òóê.',
		'emconf_module.details' => 'Òîâà ïîçâîëÿâà íà ÌĞ äà çíàå çà ñúùåñòâóâàíåòî íà ìîäóëè, êîéòî ñà âàæíè çàùîòî ÌĞ òğÿáâà äà îáíîâè ôàéëà conf.php íà ìîäóëà çà äà ñëîæè ïğàâèëíàòà TYPO3_MOD_PATH êîíñòàíòà.',
		'emconf_state.alttitle' => 'Ñúñòîÿíèå íà ğàçâèòèåòî (Development state)',
		'emconf_state.description' => 'Â êàêâî ñúñòîÿíèå íà ğàçâèòèå ñå íàìèğà ğàçøèğåíèåòî.',
		'emconf_state.details' => '<b>àëôà</b>
Ìíîãî ïúğâîíà÷àëíî ğàçâèòèå. Ìîæå äà íå ïğàâè íèùî. 

<b>áåòà</b>
Ïîä òåêóùî ğàçâèòèå. Òğÿáâà äà ğàáîòè îò ÷àñòè, íî íå å çàâúğøåíî âñå îùå.

<b>ñòàáèëíî</b>
Ñòàáèëíî è èçïîëçâàíî â ïğîèçâîäñòâî .

<b>åêñïåğèìåíòàëíî</b>
Íèêîé íåçíàå äàëè òîâà îòèâà íàíÿêàäå... Ìîæå áè å âñå îùå ñàìî èäåÿ.

<b>òåñò</b>
Òåñòîâî ğàçøèğåíèå, äåìîíñòğèğàùî èäåÿ è äğ.',
		'emconf_internal.alttitle' => 'Âúòğåøíî ïîääúğæàíî â ÿäğîòî',
		'emconf_internal.description' => 'Òîçè ôëàã ïîêàçâà ÷å, èçõîäíèÿ êîä íà ÿäğîòî å ñïåöèàëíî íàÿñíî ñ ğàçøèğåíèåòî.',
		'emconf_clearCacheOnLoad.alttitle' => 'Èç÷èñòè êåøà êîãàòî ñå èíñòàëèğà',
		'emconf_clearCacheOnLoad.description' => 'Àêî å ñëîæåíî, ÌĞ ùå ïîèñêà äà ñå èç÷èñòè êåøà ñëåä êàòî ñå å èíñòàëèğàëî ğàçøèğåíèåòî.',
		'.alttitle' => 'ÌĞ',
		'.description' => 'Ìåíàæåğà íà ğàçøèğåíèÿ (ÌĞ)',
		'emconf_private.alttitle' => '×àñòíî',
		'emconf_download_password.alttitle' => 'Ñâàëè ïàğîëàòà',
		'emconf_type.alttitle' => 'Òèï íà èíñòàëàöèÿòà',
		'emconf_type.description' => 'Òèïà íà èíñòàëàöèÿòà',
		'emconf_doubleInstall.alttitle' => 'Äâà ïúòè èëè ïîâå÷å èíñòàëèğàíî?',
		'emconf_dbReq.alttitle' => 'Èçèñêâàíèÿ íà áàçàòà äàííè',
		'emconf_dbStatus.alttitle' => 'Ñòàòóñ íà èçèñêâàíèÿòà íà áàçàòà äàííè',
		'emconf_flags.alttitle' => 'Ôëàãîâå',
		'emconf_TSfiles.alttitle' => 'Ñòàòè÷íè TypoScript ôàéëîâå',
		'emconf_classNames.alttitle' => 'PHP Class èìåíà',
		'emconf_classNames.description' => 'Ïîêàæè êîé PHP-êëàñîâå ñà íàìåğåíè â .php è .inc ôàéëîâå.',
		'emconf_errors.alttitle' => 'Ãğåøêè',
	),
	'br' => Array (
		'emconf_shy.alttitle' => 'Oculta',
		'emconf_shy.description' => 'Quando selecionada, esta extensão normalmente é oculta no AE por ser considerada uma extensão padrão, ou por algum outro motivo de menor importância.',
		'emconf_shy.details' => 'Use esta opção se uma extensão for de "raro interesse" (o que não significa que seja de pouca importância - apenas uma extensão procurada com pouca freqüência...)
Esta opção não determina se a extensão está ou não ativada, apenas determina a visualização no AE.
Normalmente a opção "oculta" é usada em todas as extensões carregadas por padrão, conforme TYPO3_CONF_VARS.',
		'emconf_category.alttitle' => 'Categoria',
		'emconf_category.description' => 'Categoria à qual a extensão pertence.',
		'emconf_dependencies.alttitle' => 'Depende de outras extensões?',
		'emconf_dependencies.description' => 'Esta é uma lista das extensões das quais esta extensão depende que sejam carregadas ANTES dela.',
		'emconf_dependencies.details' => 'O AE estabelecerá tal dependência ao gravar a lista de extensões em localconf.php',
		'emconf_conflicts.alttitle' => 'Gera conflito com outras extensões?',
		'emconf_conflicts.description' => 'Lista de extensões com as quais esta extensão não funciona (e portanto não pode ser ativada antes que as outras sejam desinstaladas)',
		'emconf_priority.alttitle' => 'Prioridade para carregamento da extensão',
		'emconf_priority.description' => 'Esta opção avisa ao AE para tentar carregar a extensão como a primeira da lista. O padrão é carregá-la por último.',
		'emconf_module.alttitle' => 'Módulos de administração inclusos',
		'emconf_module.description' => 'Se alguma subpasta de uma extensão contiver módulos de administração, os nomes das subpastas serão listados aqui.',
		'emconf_module.details' => 'Informa ao AE sobre a existência do módulo, o que é importante já que o AE precisa atualizar o arquivo conf.php do módulo para atribuir corretamente a constante TYPO3_MOD_PATH.',
		'emconf_state.alttitle' => 'Status de desenvolvimento',
		'emconf_state.description' => 'Em qual status de desenvolvimento a extensão se encontra.',
		'emconf_state.details' => '<b>alfa</b>
Desenvolvimento em fase inicial. A extensão pode não estar executando qualquer função.

<b>beta</b>
Sob desenvolvimento. Pode estar funcionando parcialmente, mas não está concluída.

<b>estável</b>
Estável e utilizada normalmente em produção.

<b>experimental</b>
Não se sabe se vai chegar a algum lugar... Talvez seja ainda apenas uma idéia.

<b>teste</b>
Extensão para teste, para demonstrar conceitos, etc.',
		'emconf_clearCacheOnLoad.alttitle' => 'Limpar o cache quando instalada',
		'emconf_clearCacheOnLoad.description' => 'Quando selecionada, o AE solicitará a limpeza do cache quando esta extensão for instalada.',
		'emconf_modify_tables.alttitle' => 'Tabelas existentes modificadas',
		'emconf_modify_tables.description' => 'Lista de tabelas que apenas foram modificadas - não criadas a partir do zero - por esta extensão.',
		'emconf_modify_tables.details' => 'Tabelas desta lista encontradas no arquivo ext_tables.sql desta extensão',
		'.alttitle' => 'AE',
		'.description' => 'O Administrador de Extensões (AE)',
		'emconf_private.alttitle' => 'Particular',
		'emconf_download_password.alttitle' => 'Senha para download',
		'emconf_download_password.description' => 'Senha adicional necessária para baixar extensões particulares.',
		'emconf_type.alttitle' => 'Tipo de instalação',
		'emconf_type.description' => 'O tipo da instalação',
	),
	'et' => Array (
	),
	'ar' => Array (
	),
	'he' => Array (
		'emconf_category.alttitle' => '×§×˜×’×•×¨×™×”',
	),
	'ua' => Array (
	),
	'lv' => Array (
	),
	'jp' => Array (
	),
	'vn' => Array (
	),
);
?>