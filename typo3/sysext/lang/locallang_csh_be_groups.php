<?php
/**
 * Default  TCA_DESCR for "be_groups"
 * TYPO3 CVS ID: $Id$
 */

$LOCAL_LANG = Array (
	'default' => Array (
		'.description' => 'This is the backend administration user groups available for the Backend users. These determine the permissions for the Backend users.',
		'_.seeAlso' => 'be_users',
		'title.description' => 'Name of the Backend usergroup',
		'db_mountpoints.description' => 'Assign startpoints for the users page tree.',
		'db_mountpoints.details' => 'The page tree used my all Web-submodules to navigate must have some points-of-entry defined. Here you should insert one or more references to a page which will represent a new root page for the page tree. This is called a \'Database mount\'.
DB mounts may be inherited by the users which are members of this group. This does depend on whether the user is configured to include the mounts set in the member groups. However it\'s recommended to use backend user groups like this to configure mounts. Especially if the need to be shared amoung many users.',
		'_db_mountpoints.seeAlso' => 'be_groups:file_mountpoints,
be_users:db_mountpoints,
be_users:options
',
		'file_mountpoints.description' => 'Assign startpoints for the file folder tree.',
		'file_mountpoints.details' => 'The file folder tree is used by all File-submodules to navigate between the file folders on the webserver.
Notice as with \'DB mounts\' the file folder mounts may be inherited by the users which are members of this group.',
		'_file_mountpoints.seeAlso' => 'be_groups:db_mountpoints,
be_users:file_mountpoints,
be_users:options
',
		'pagetypes_select.description' => 'Select which \'Types\' of Pages the members may set.',
		'pagetypes_select.details' => 'This option limits the number of valid choices for the user when he is about to select a page type.',
		'_pagetypes_select.seeAlso' => 'pages:doktype,
be_groups:inc_access_lists',
		'tables_modify.description' => 'Select which tables the members may modify.',
		'tables_modify.details' => 'An important part of setting permissions is to define which database tables a user is allowed to modify. 
Tables allowed for modification is automatically also allowed for selection and thus you don\'t need to set tables entered here in the "Tables (listing)" box.

<strong>Notice</strong> that this list adds to the fields selected in other member groups of a user.',
		'_tables_modify.seeAlso' => 'be_groups:tables_select,
be_groups:inc_access_lists',
		'tables_select.description' => 'Select which tables the members may see in record lists (\'modify\' tables does not need to be re-entered here!).',
		'tables_select.details' => 'This determines which tables - in addition to those selected in the "Tables (modify)" box - may be viewed and listed for the user. He is thus not able to <em>edit</em> the table - only select the records and view the content.
This list is not so very important. It\'s a pretty rare situation that a user may select tables but not modify them.',
		'_tables_select.seeAlso' => 'be_groups:tables_modify,
be_groups:inc_access_lists',
		'non_exclude_fields.description' => 'Certain table fields are not available by default. Those fields can be explicitly enabled for the group members here.',
		'non_exclude_fields.details' => '"Allowed excludefields" allows you to detail the permissions granted to tables. By default all these fields are not available to users but must be specifically enabled by being selected here.
One application of this is that pages are usually hidden by default and that the hidden field is not available for a user unless he has been granted access by this list of "Allowed excludefields". So the user may create a new page, but cannot un-hide the page. Unless of course he has been assigned the "Page: Hidden" exclude field through one of his member groups.
Of course it does not make any sense to add fields from tables which are not included in the list of table allowed to be modified.',
		'_non_exclude_fields.seeAlso' => 'be_groups:inc_access_lists',
		'hidden.description' => 'Disables a user group.',
		'hidden.details' => 'If you disable a user group all user which are members of the group will in effect not inherit any properties this group may have given them.',
		'lockToDomain.description' => 'Enter the host name from which the user is forced to login.',
		'lockToDomain.details' => 'A TYPO3 system may have multiple domains pointing to it. Therefore this option secures that users can login only from a certain host name.',
		'_lockToDomain.seeAlso' => 'be_users:lockToDomain,
fe_users:lockToDomain,
fe_groups:lockToDomain',
		'groupMods.description' => 'Select available backend modules for the group members.',
		'groupMods.details' => 'This determines which \'menu items\' are available for the group members.
This list of modules is added to any modules selected in other member groups of a user as well as the corresponding field of the user himself.',
		'_groupMods.seeAlso' => 'be_users:userMods,
be_groups:inc_access_lists',
		'inc_access_lists.description' => 'Select whether Page type, Table, Module and Allowed excludefield access lists are enabled for this group.',
		'_inc_access_lists.seeAlso' => 'be_groups:pagetypes_select,
be_groups:tables_modify,
be_groups:tables_select,
be_groups:groupMods,
be_groups:non_exclude_fields
',
		'description.description' => 'Enter a short description of the user group, what it is for and who should be members. This is for internal use only.',
		'_description.seeAlso' => 'fe_groups:description',
		'TSconfig.description' => 'Additional configuration through TypoScript style values (Advanced).',
		'TSconfig.syntax' => 'TypoScript style without conditions and constants.',
		'_TSconfig.seeAlso' => 'be_users:TSconfig
fe_users:TSconfig
fe_groups:TSconfig
pages:TSconfig',
		'hide_in_lists.description' => 'This option will prevent the user group from showing up in lists, where user groups are selected.',
		'hide_in_lists.details' => 'This will affect the list of user groups in the Task Center To-Do and Messages parts as well as the Web>Access module.
The option is extremely useful if you have general user groups defining some global properties which all your users are members of. Then you would probably not like all those users to \'see\' each other through the membership of this group, for instance sending messages or To-Dos to each other. And this is what is option will prevent.',
		'subgroup.description' => 'Select backend user groups which are automatically included for members of this group.',
		'subgroup.details' => 'The properties or subgroups are added to the properties of this groups and basically they will simply be added to the list of member groups of any user which is a member of this group.
This feature provides a great way to create \'Supervisor\' user groups.',
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
		'.description' => 'Dit zijn de backendbeheer-gebruikersgroepen die beschikbaar zijn voor de Backendgebruikers. Deze bepalen de permissies voor de Backendgebruikers.',
		'title.description' => 'Naam van de backend gebruikersgroep',
		'db_mountpoints.description' => 'Startpunten toekennen voor de gebruikers paginaboom.',
		'db_mountpoints.details' => 'De paginaboom, gebruikt om door alle web-submodules te navigeren, dient een aantal ingangspunten gedefiniëerd te hebben. Hier moeten een aantal referenties naar een pagina worden ingevoegd die een nieuwe rootpagina representeren voor de paginaboom. Dit word een \'Databasestartpunt\' genoemd.
Databasestartpunten kunnen worden ge-erfd door gebruikers die lid zijn van deze groep. De gebruiker dient dan wel zo geconfigureerd te zijn dat deze de startpunten bevat zoals bepaald in de gebruikersgroepen. Het wordt echter aanbevolen de backend gebruikersgroepen te gebruiken om de startpunten te configureren. Vooral als ze gedeeld worden door een groot aantal gebruikers.',
		'file_mountpoints.description' => 'Startpunten toekennen aan de mappenweergave.',
		'file_mountpoints.details' => 'De mappenweergave wordt door alle bestands-submodules gebruikt om tussen de bestandsmappen op de webserver te navigeren.
Net zoals bij \'Databasestartpunten\' kunnen de bestandstartpunten ge-erfd worden door de gebruikers die lid zijn van deze groep.',
		'pagetypes_select.description' => 'Selecteer welke \'Type\' pagina\'s de leden van de groep mogen bepalen.',
		'pagetypes_select.details' => 'Deze optie beperkt het aantal geldige keuzes voor de gebruiker als deze een paginatype wil selecteren.',
		'tables_modify.description' => 'Selecteer welke tabellen door de leden van de groep aangepast mogen worden.',
	),
	'cz' => Array (
	),
	'pl' => Array (
	),
	'si' => Array (
	),
	'fi' => Array (
		'.description' => 'Tämä on taustan hallinta käytettävissä oleville Taustakäyttäjien (BE) käyttäjäryhmille. Nämä ryhmät määrittelevät käyttöoikeudet Taustakäyttäjille (BE).',
		'title.description' => 'Taustakäyttäjien ryhmän nimi.',
		'db_mountpoints.description' => 'Määrittele aloituskohta käyttäjien sivurakenteelle.',
		'file_mountpoints.description' => 'Määrittele aloituskohta tiedostohakemistojen rakenteelle.',
		'pagetypes_select.description' => 'Valitse mihin sivutyyppeihin käyttäjillä on oikeus.',
		'pagetypes_select.details' => 'Tämä valinta rajoittaa valintaehtojen lukumäärää käyttäjille kun he valitsevat sivun tyyppiä.',
		'tables_modify.description' => 'Valitse mihin tauluihin käyttäjillä on muutosoikeus.',
		'non_exclude_fields.description' => 'Eräät taulujen tiedot eivät ole oletusarvoisesti käytettävissä. Nuo tiedot voidaan eksplisiittisesti saattaa voimaan ryhmän jäsenille tässä.',
		'hidden.description' => 'Poista käyttäjä ryhmän voimassaolo.',
		'description.description' => 'Anna lyhyt kuvaus käyttäjäryhmästä, miksi seon olemassa ja minkälaisia käyttäjiä. Tämä tieto on vain sisäiseen käyttöön.',
		'TSconfig.description' => 'Lisä asetukset käyttäen TypoScript tyyli tietoja (Laajennettu eli Advanced).',
		'TSconfig.syntax' => 'TypoScript tyyli ilman ehtoja (conditions) ja vakioita (constants).',
		'hide_in_lists.description' => 'Tämä valinta estää käyttäjäryhmää näkymästä listoilla kun käyttäjäryhmiä valitaan.',
		'subgroup.description' => 'Valitse taustakäyttäjäryhmä joka automaattisesti lisätään tämän ryhmän jäsenille.',
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
		'.description' => 'Ovo je pozadinska administracija korisnièkih grupa dostupna pozadinskim korisnicima. Ovdje se odreðuju dozvole za pozadinske korisnike.',
		'title.description' => 'Naziv grupe pozadinskih korisnika',
		'db_mountpoints.description' => 'Pridruži poèetnu toèku stabla stranica korisnika.',
		'db_mountpoints.details' => 'Stablo stranica koje koriste svi podmoduli unutar Web navigacije mora imati definiranu toèku ulaska. Ovdje možete unjeti jednu ili više referenci na stranicu koja æe predstvljati novu poèetnu stranicu stabla. To se naziva \'Database mount\' (DB poveznica).
DB poveznica može biti naslijeðena od korisnika koji je èlan grupe. Ovo zavisi od toga da li je korisnik konfiguriran tako da ukljuèuje svoje poveznice u grupu èiji je èlan. Preporuèljivo je koristiti pozadinske korisnièke grupe sliène ovoj za konfiguraciu poveznica, posebno ako je trebate dijeliti izmeðu mnogo korisnika.',
		'file_mountpoints.description' => 'Pridruži poèetnu toèku stabla pretinaca.',
		'file_mountpoints.details' => 'Stablo pretinaca koristi se od svih podmodula unutar menija \'Datoteka\' radi navigacije izmeðu pretinaca web sjedišta. Napomenimo da slièno kao i kod \'DB povezince\' (DB mount) povezinca pretinaca može biti naslijeðena od korsinika koji je èlan grupe.',
		'pagetypes_select.description' => 'Selektirajte koji tip stranice èlanovi grupe mogu postaviti.',
		'pagetypes_select.details' => 'Ova opcija ogranièava broj izbora koji korisnik može odabrati prilikom selektiranja tipa stranice.',
		'tables_modify.description' => 'Selektirajte koje tablice èlanovi grupe mogu modificirati.',
		'tables_modify.details' => 'Važan dio postavljanja dozvola predstavlja definiranja tablica baze koje korsinici mgu mijenjati. Tablice nad kojima je omoguæeno mijenjanje takoðer je automatski omoguæena i selekcija i tako da nemate potrebu za postavljanjem tablica unutar "Tables (popis)" okvira.

<strong>Napomena:</strong>',
		'tables_select.description' => 'Selektirajte koje tablice èlanovi grupe mogu vidjeti u popisu (tablice nad kojima je omoguæeno modificiranje ne moraju ponovo biti unešene ovdje).',
		'tables_select.details' => 'Ovdje definirate koje tablice - u dodatku onih selektiranih u okviru "Tables (modify)" - mogu biti dodane na popis tablica koje korisnik može pregledavati. Korisnik nije u moguænosti <em>editirati</em> tablice veæ samo selektirati zapis i pregledati njegov sadržaj.
Ovaj popis nije pretjerano važan. Prilièno su rijetke situacije u kojima korisnik može selektirati tablice ali ih ne može i mijenjati.',
		'non_exclude_fields.description' => 'Odreðena polja tablice poèetno nisu dostupna. Ta polja ovdje trebaju biti eksplicitno omoguæena èlanovima grupe.',
		'non_exclude_fields.details' => '"Allowed excludefields" omoguæavaju detaljnije dozvole nad dostupnim tablicama. Poèetno sva ova polja nisu dostupna korisnicima veæ moraju ovdje biti selektirana.
Jedana primjena ovog svojstva su stranice koje su obiæno poèetno definirane kao skrivene i polje koje sadrži to svojstvo nije dostupno korisniku dokle god mu to pravo nije dano pomoæu "Allowed excludefields". Dakloe korisnik može kreirati novu stranicu ali æe ona ostati skrivena dokle god on ne bude pridružen iskljuèenom polju "Page: Hidden" putm èlanstva u grupi.
Naravno nema nikakvog smisla dodavati polja tablica koje nisu ukljuèene  na popis tablica koje grupa može mijenjati.',
		'hidden.description' => 'Onemoguæava grupu korisnika',
		'hidden.details' => 'Ukoliko onemoguæite grupu korisnika niti jedan èlan grupe neæe naslijediti svojstva ove grupe.',
		'lockToDomain.description' => 'Unesite naziv poslužitelja (domene) putem kojega se prisiljava korisnika za prijavu.',
		'lockToDomain.details' => 'TYPO3 sustav omoguæava opsluživanje više domena. Ovom opcijom osiguravate da se korisnik može prijaviti na sustav samo putem zadane domene (naziva poslužitelja - URLa).',
		'groupMods.description' => 'Selektirajte dostupne pozadinske module èlanovima grupe.',
		'groupMods.details' => 'Ova opcija odreðuje koji sadržaj menija æe biti dostupan èlanovima grupe.
Ova lista modula biti æe dodana listi modula koje korisnik dobiva kao èlan drugih grupa kao i odgovarajuèim postavkama samog korisnika.',
		'description.description' => 'Unesite kratak opis korisnièke grupe, koja joj je namjena i tko može postati njezin èlan. Ovo je samo za internu upotrebu.',
		'TSconfig.description' => 'Dodatna konfiguracija kroz unos vrijednosti u TypoScript stilu (napredna opcija).',
		'TSconfig.syntax' => 'TypoScript stil bez uvjeta i konstanti.',
		'hide_in_lists.description' => 'Ova opcija omoguæava skrivanje korisnièke grupe u popisu grupa.',
		'hide_in_lists.details' => 'Ova opcija ima efekt na korisnièke grupe unutar "Task Center To-Do" i "Messagess" dijela kao i Web>Pristup modula.
Ova opcija je izrazito korisna kada imate opæenite korisnièke grupe koje definiraju neka globalna svojstva èiji su èlanovi svi korisnici. Tada vjerojatno neæete htjeti da svi korisnici vide ostale kroz èlanstvo u ovoj grupi, tako da npr. ne mogu slati poruke ili dodjeljivati zadatke jedni drugima.',
		'subgroup.description' => 'Odaberite pozadinsku grupu korisnika u koja æe automatski biti dodani èlanovi ove grupe.',
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