<?php
/**
 * Default  TCA_DESCR for "be_users"
 * TYPO3 CVS ID: $Id$
 */

$LOCAL_LANG = Array (
	'default' => Array (
		'.description' => 'This is the table of backend administration users.',
		'_.seeAlso' => 'be_groups',
		'username.description' => 'Enter the login name of the backend user.',
		'username.details' => 'A username is required and must be in lowercase without spaces in it. Furthermore the username must be unique. If it is not unique a number will be prepended automatically.',
		'_username.seeAlso' => 'be_users:password',
		'password.description' => 'Enter the password for the backend username above (Notice the value you enter <i>will</i> be readable in the field!).',
		'password.details' => 'The password is required. Before the password is sent to the server it\'s md5-hashed, so the password value itself is not transferred over the internet. This is true both when editing the password and when the user logs in. 
While this principle does not reveal the raw password it is <i>not</i> the same as real encryption. If you need the highest degree of security you should install the TYPO3 backend on a secure server.
The password is stored in the database as an md5-hash and thus it\'s not possible to extract the original password from the database either. This means that \'lost passwords\' must be substituted with a new password for the user.',
		'usergroup.description' => 'Assign backend user groups to the user.',
		'usergroup.details' => 'The backend user groups defines the permissions which the backend user will inherit. So unless the backend user is an \'Admin\' user, he needs to be a member of one or more user groups in order to have practically any permissions assigned. The properties set in the user groups are mostly added together.
The first (top) group in the list is the group which will, by default, be the owner of pages the user creates.',
		'_usergroup.seeAlso' => 'be_users:TSconfig,
be_groups',
		'lockToDomain.description' => 'Enter the host name from which the user is forced to login.',
		'lockToDomain.details' => 'A TYPO3 system may have multiple domains pointing to it. Therefore this option secures that users can login only from a certain host name.',
		'_lockToDomain.seeAlso' => 'be_groups:lockToDomain,
fe_users:lockToDomain,
fe_groups:lockToDomain',
		'disableIPlock.description' => 'Disable the lock of the backend users session to the remote IP number.',
		'disableIPlock.details' => 'You will have to disable this lock if backend users are accessing TYPO3 from ISDN or modem connections which may shutdown and reconnect with a new IP. The same would be true for DHCP assignment of IP numbers where new IP numbers are frequently assigned.',
		'db_mountpoints.description' => 'Assign startpoints for the users page tree.',
		'db_mountpoints.details' => 'The page tree used my all Web-submodules to navigate must have some points-of-entry defined. Here you should insert one or more references to a page which will represent a new root page for the page tree. This is called a \'Database mount\'.

<strong>Notice</strong> that backend user groups also has DB mounts which can be inherited by the user. So if you want a group of users to share a page tree, you should probably mount the page tree in the backend user group which they share instead.',
		'_db_mountpoints.seeAlso' => 'be_groups:db_mountpoints,
be_users:file_mountpoints,
be_users:options
',
		'file_mountpoints.description' => 'Assign startpoints for the file folder tree.',
		'file_mountpoints.details' => 'The file folder tree is used by all File-submodules to navigate between the file folders on the webserver. In order to be able to upload <em>any</em> files the user <em>must</em> have a file folder mounted with a folder named \'_temp_\' in it (which is where uploads go by default). 
Notice as with \'DB mounts\' the file folder mounts may be inherited from the member groups of the user. ',
		'_file_mountpoints.seeAlso' => 'be_groups:file_mountpoints,
be_users:db_mountpoints,
be_users:options',
		'email.description' => 'Enter the email address of the user.',
		'email.details' => 'This address is rather important to enter because this is where messages from the system is sent.
<strong>Notice</strong> the user is able to change this value by himself from within the User>Setup module.
',
		'_email.seeAlso' => 'be_users:realName',
		'realName.description' => 'Enter the ordinary name of the user, eg. John Doe.',
		'realName.details' => '<strong>Notice</strong> the user is able to change this value by himself from within the User>Setup module.',
		'_realName.seeAlso' => 'be_users:email',
		'disable.description' => 'This option will temporarily disable the user from logging in.',
		'_disable.seeAlso' => 'be_users:starttime,
be_users:endtime',
		'admin.description' => '\'Admin\' users has TOTAL access to the system!',
		'admin.details' => '\'Admin\' can do anything TYPO3 allows and this kind of user should be used only for administrative purposes. All daily handling should be done with regular users. 
\'Admin\' users don\'t need to be members or any backend user groups. However you should be aware that any page created by an admin user without a group will not have any owner-group assigned and thus it will probably be invisible for other users. If this becomes a problem you can easily solve it by assigning a user group to the \'Admin\' user anyway. This does of course not affect the permissions since they are unlimited, but the first group listed is by default the owner group of newly created pages.
\'Admin\' users are easily recognized as they appear with a red icon instead of the ordinary blue user-icon.

You should probably not assign any other users than yourself as an \'Admin\' user.',
		'options.description' => 'Select if the user should inherit page tree or folder tree mountpoints from member groups.',
		'options.details' => 'It\'s a great advantage to let users inherit mountpoints from membergroups because it makes administration of the same mountpoints for many users extremely easy. 
If you don\'t check these options, you must make sure the mount points for the page tree and file folder tree is set specifically for the user.',
		'_options.seeAlso' => 'be_users:db_mountpoints,
be_users:file_mountpoints',
		'fileoper_perms.description' => 'Select file operation permissions for the user.',
		'fileoper_perms.details' => 'These settings relates to the functions found in the File>List module as well as general upload of files.',
		'_fileoper_perms.seeAlso' => 'be_users:file_mountpoints',
		'starttime.description' => 'Enter the date from which the account is active.',
		'_starttime.seeAlso' => 'be_users:disable,
be_users:endtime,
pages:starttime',
		'endtime.description' => 'Enter the date from which the account is disabled.',
		'_endtime.seeAlso' => 'be_users:disable,
be_users:starttime,
pages:starttime',
		'lang.description' => 'Select the <i>default</i> language.',
		'lang.details' => 'This determines the language of the backend interface for the user. All mainstream parts available for regular users are available in the system language selected. 
\'Admin\'-users however will experience that the \'Admin\'-only parts of TYPO3 is in english. This includes all submodules in "Tools" and the Web>Template module.

<b>Notice</b> this is only the default language. As soon as the user has logged in the language must be changed through the User>Setup module.',
		'userMods.description' => 'Select available backend modules for the user.',
		'userMods.details' => 'This determines which \'menu items\' are available for the user.

Notice that the same list of modules may be configured for the backend user groups and that these will be inherited by the user in addition to the modules you select here. It\'s highly likely that you should not set any modules for the user himself but rather select the modules in the backend groups he\'s a member of. However this list provides a great way to add a single module for specific users.',
		'_userMods.seeAlso' => 'be_groups:groupMods',
		'TSconfig.description' => 'Enter additional TSconfig for the user (advanced).',
		'TSconfig.details' => 'This field allows you to extend the configuration of the user in severe details. A brief summary of the options include a more detailed configuration of the backend modules, setting of user specific default table field values, setting of Rich Text Editor options etc. The list will be growing by time and is fully documented in the adminstration documentation, in particular \'admin_guide.pdf\' (see link below).',
		'_TSconfig.seeAlso' => 'pages:TSconfig,
fe_users:TSconfig,
be_groups:TSconfig
admin_guide.pdf|http://www.typo3.com/doclink.php?key=admin_guide.pdf
',
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
		'.description' => 'Forma za administraciju backend korisnika.',
		'username.description' => 'Unesite korisnièko ime backend korisnika.',
		'username.details' => 'Korisnièko ime je obavezno i mora biti uneseno malim slovima bez razmaka. Korisnièko ime mora biti jedinstveno. Ako nije uneseno jedinstveno korisnièko ime sustav æe mu automatski pridodati broj.',
		'password.description' => 'Unesite zaporku za prethodno upisano korisnièko ime (Zaporka koju unesete <i> biti æe vidljiva</i> u polju za unos.',
		'password.details' => 'Zaporka je obavezna. Poslužitelju se ne šalje vrijednost same zaporke, 
veæ kod dobiven pomoæu MD5-hash algoritma. Ovaj se postupak poštuje 
i kod administracije zaporke, kao i kod autentifikacije korisnika prilikom 
pristupanja poslužitelju.
Iako ovaj princip ne otkriva pravu vrijednost zaporke, on <i>nije</i> 
isto što i stvarna enkripcija. Ako trebate najviši stupanj sigurnosti,
potrebno je instalirati Typo3 backend na sigurni popslužitelj.
Zaporka je pohranjena u bazu podataka u MD5-hash obliku, pa je nemoguæe
iz te baze podataka otkriti izvornu zaporku.Zato u sluèaju \'izgubljenje zaporke\' 
ona se mora zamijeniti novom korisnièkom zaporkom.',
		'usergroup.description' => 'Dodijeli backend korisnièke grupe korisniku.',
		'usergroup.details' => 'Backend korisnièke grupe odreðuju ovlasti koje æe backend korisnik 
naslijediti. Zato, osim u sluèaju da korisnik ima \'admin\' ovlasti, 
korisnik mora biti èlan jedne ili više korisnièkih grupa kako bi dobio
potrebne ovlasti. Parametri ovlasti postavljeni u pojedinim korisnièkim 
grupama se uglavnom zbrajaju.
Prva (najviša) grupa u listi je grupa koja æe u pravilu bit vlasnik stranica 
koje korisnik napravi.',
		'db_mountpoints.description' => 'Dodijeli poèetne toèke zkorisnièkom stablu stranice.',
		'db_mountpoints.details' => 'Stablo stranica koje koriste svi Web-podmoduli za navigaciju 
mora imati odreðene poèetne toèke ulaska u stablo. Ovdje bi trebali
upisati jednu ili više referenci na stranicu koja æe predstavljati novu
korijen stranicu u stablu stranica. 
To se naziva \'Poveznica baze podataka\' (DB poveznica).

<strong>Primjedba</strong> Backend korisnièke grupe takoðer
sadrže DB poveznice koje korisnik može naslijediti. U sluèaju da želite više 
korisnika koji dijele jedno stablo stranice, trebate napraviti poveznicu stabla 
stranice u backend korisnièkoj grupi koju æe korisnici naslijediti.',
		'file_mountpoints.description' => 'Dodijeli poèetne toèke stablu datoteka',
		'email.description' => 'Upišite email adresu korisnika.',
		'email.details' => 'Ovu je adresu vrlo važno unijeti jer se na nju šalju sistemske poruke.
<strong>Primjdba</strong> Korisnik može sam mijenjati adresu
u Korisnik>Postavke modulu.',
		'realName.description' => 'Unesi pravo ime korisnika, npr. Ivo Iviæ',
		'realName.details' => '<strong>Primjdba</strong> Korisnik može sam mijenjati adresu u Korisnik>Postavke modulu.',
		'disable.description' => 'Ova moguænost privremeno onemoguæava korisniku pristup.',
		'admin.description' => '\'Admin\' korisnici imaju POTPUN pristup sustavu!',
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
