<?php
/**
 * Default  TCA_DESCR for "sys_filemounts"
 * TYPO3 CVS ID: $Id$
 */

$LOCAL_LANG = Array (
	'default' => Array (
		'.description' => 'Filemounts describe a filepath on the server, relative or absolute.',
		'.details' => 'By creating a file mount record and placing a reference to it in a Backend usergroup you can allow a user access to the file mount in the File>List module. 
You need to create and set at least one filemount with a folder \'_temp_\' in it if you want users to upload files through the webbrowser.
Filemounts may also configure access to a path on the server to which the user has FTP-access. Just remember to set file-permissions on the server correctly so the webserver user (which PHP is running as) has at least read access to the FTP-dir.',
		'_.seeAlso' => 'be_users,
be_groups',
		'title.description' => 'Enter a title for the filemount',
		'path.description' => 'Enter the path of the filemount, relative or absolute depending on the settings of BASE.',
		'path.details' => 'If BASE is set to relative, the path mounted is found in the subfolder "fileadmin/" of the website. Then you should enter the subfolder in "fileadmin/" as path. For instance if you want to mount access to "fileadmin/user_uploads/all/" then enter the value "user_uploads/all" as the value of PATH.
If BASE is absolute you should enter the absolute path on the server, eg. "/home/ftp_upload" or "C:/home/ftp_upload". 

<strong>Notice:</strong> In any case, make sure the webserver user which PHP is running as has <em>at least</em> read-access to the path. If not, the mount will simply not appear without any warnings. 
If you have problems - especially with absolute mounts - try to mount something "simple" like a relative path in fileadmin. If that is working well, try the absolute path.

Your PHP-configuration may also impose other restrictions on you if safe-mode like features are enabled. Then use relative paths.',
		'_path.seeAlso' => 'sys_filemounts:base',
		'hidden.description' => 'Use this option to temporarily disable the filemount.',
		'hidden.details' => 'All backend users using the mount will not have access anymore. This includes \'Admin\'-users.',
		'base.description' => 'Determines whether the value of the PATH field is to be recognized as an absolute path on the server or a path relative to the fileadmin/ subfolder to the website.',
		'_base.seeAlso' => 'sys_filemounts:path',
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
	),
	'cz' => Array (
	),
	'pl' => Array (
	),
	'si' => Array (
	),
	'fi' => Array (
		'.description' => 'Tiedostoasetukset (filemount) kuvaavattiedostopolkua palvelimella, relatiivista tai absoluuuttista.',
		'.details' => 'Luomalla tiedostoasetus tiedon ja asettamalla sen viitteeksi Tausta käyttäjäjoukolla voit antaa käyttäjäoikeuksia tiedostoasetuksille File->List aliohjelmassa.
Sinun tulee luoda vähintään yksi tiedostoasetus jossa hakemistona on \'_temp_\' jotta käyttäjä pystyvät myös viemään tiedostoja palvelimelle.
Tiedostoasetuksella voidaan konfiguroida ,yös polku jonne käyttäjällä on FTP-oikeus. Muista vain asettaa tiedosto-oikeudetoikein siten että www-palvelinkäyttäjä (millä PHP toimii) on vähintäin luku oikeus FTP-hakemistoon.',
		'_.seeAlso' => 'be_users,
be_groups',
		'title.description' => 'Anna tiedostoasetuksen (Filemount) nimi',
		'path.description' => 'Anna tiedostoasetusten polku, joko relatiivinen tai absoluuttinen, riippuen BASE asetuksista.',
		'path.details' => 'Jos Base on asetettu relatiiviseksi, voimassa oleva polku löytyy hakemiston fileadmin/ alta www-palvelimessa.
Näin ollen Sinun tulee asettaa hakemisto \'fileadmin/\' poluksi. Esimerkiksi jos haluat tiedostoasetukseksi "fileadmin/user_upload/all/" on Sinun annettava PATH tiedoksi "user_upload/all/".
Jos BASE on absoluuttinen, on Sinun annettava absoluuttinen polku palvelimessa, esim. /home/ftp_upload" tai "C:/home/ftp_upload". 

<strong>Huomautus:</strong> Kaikissa tapuksissa, varmista että palvelinkäyttäjä jolla PHP toimii on <em>ainakint</em> luku-oikeudet polkuun. Jos näin ei ole, tiedostoasetukset eivät yksinkertaisesti tule näkyviin eikä varoituksia.
Jos Sinulla on ongelmia - erityisesti absoluuttisten asetusten kanssa - yrita asettaa jotain \'yksinkertaista\' kuten relatiivinen asetus fileadmin hakemiston sisällä. Jos tämätoimii oikein yritä asettaa absoluuttinen polku.

Voi olla että myös PHP-asetukset aiheuttavat rajoituksia Sinulle. kuten esimerkikis safe-moodi asetukset. Käytä silloin relatiivisia asetuksia.',
		'_path.seeAlso' => 'sys_filemounts:base',
		'hidden.description' => 'Käytä tätä vaihtoehtoa poistaaksesi tiedostoasetukset väliaikaisesti.',
		'hidden.details' => 'Kenelläkään taustkäyttäjälle ei ole enää oikeuksia tiedostoasetukseen. Tämä koskee myös \'Admin\'-käyttäjiä.',
		'base.description' => 'Tällä päätellään onko PATH kentän tieto tunnistettava absoluuttiseksi vai relatiiviseksi poluksi fileadmin/ hakemiston alla olevaksi alihakemistoksi',
		'_base.seeAlso' => 'sys_filemounts:path',
	),
	'tr' => Array (
	),
	'se' => Array (
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
	),
	'hu' => Array (
	),
	'gl' => Array (
	),
	'th' => Array (
	),
	'gr' => Array (
	),
	'hk' => Array (
	),
	'eu' => Array (
	),
	'bg' => Array (
	),
	'br' => Array (
	),
	'et' => Array (
	),
	'ar' => Array (
	),
	'he' => Array (
	),
	'ua' => Array (
	),
);
?>