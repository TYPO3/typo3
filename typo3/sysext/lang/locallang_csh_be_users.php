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
		'.description' => 'Dit is de tabel van het beheer van backendgebruikers',
		'username.description' => 'Vul hier de gebruikersnaam van de beheer gebruiker in.',
		'username.details' => 'Een gebruikersnaam is verplicht en moet in kleine letters zonder spaties worden ingevuld. Verder moet de gebruikersnaam uniek zijn. Als het niet uniek is zal er gelijk een nummer aan toegevoegt worden.',
		'password.description' => 'Geef hier het wachtwoord voor bovenstaande gebruiker in. (De waarde die wordt ingegeven is leesbaar)',
		'password.details' => 'Een wachtwoord is verplicht. Voordat een wachtwoord wordt verstuurd, wordt het md5-hashed, zodat het daadwerkelijke wachtwoord niet over het internet wordt verstuurd. Dit geldt voor het bewerken/invoeren van het wachtwoord, als ook voor het inloggen. Alhoewel niet het daadwerkelijke wachtwoord wordt verstuurd over het internet, is het niet hetzelfde als encryptie. Wil men de hoogste veiligheid dan moet men typo3 installeren op een beveiligde server. 
Het opgeslagen wachtwoord in de database is ook md5-hashed. Dit betekent dat vergeten wachtwoorden moeten worden vervangen door een nieuw wachtwoord voor de gebruiker.',
		'usergroup.description' => 'Voeg een gebruiker toe aan een gebruikersgroep.',
		'usergroup.details' => 'De gebruikersgroep definieert welke eigenschappen de gebruiker overneemt van deze groep. Tenzij men admin gebruiker is, moet men altijd lid zijn van een of meer groepen. De eigenschappen van de groepen worden bij elkaar opgeteld. 
De bovenste groep zal standaard de eigenaar worden van nieuw gecreerde pagina\'s.',
		'lockToDomain.description' => 'Vul de hostnaam waarvandaan de gebruiker dient in te loggen.',
		'lockToDomain.details' => 'Een TYPO3 systeem kan meerdere domeinen hebben die er naar verwijzen. Deze optie zorgt ervoor dat gebruikers alleen in kunnen loggen vanaf een bepaalde hostnaam.',
		'db_mountpoints.description' => 'Bepaald het beginpunt van de gebruikers mappenweergave.',
		'db_mountpoints.details' => 'Alle web-modules maken gebruik van de mappenweergave om te navigeren. Hier geeft men het beginpunt aan, welke wordt weergegeven als nieuw beginpunt van de site. De gebruiker kan vanaf dit punt de pagina\'s aanpassen. Dit wordt een "database mount" genoemd.

<strong>Let op!</strong> Ook backendgebruikersgroepen hebben een DB mount welke kan worden overgenomen door de leden van de groep. Dus als een groep gebruikers hetzelfde gedeelte van de site mogen beheren is het waarschijnlijk beter om de DB mount aan de backendgebruikersgroep toe te kennen.',
		'file_mountpoints.description' => 'Geeft het beginpunt voor de mappenweergave van de bestanden.',
		'file_mountpoints.details' => 'De mappenweergave voor bestanden wordt gebruikt door alle bestandsubmodules om te navigeren tussen de diverse mappen op de webserver. Om bestanden te kunnen uploaden dient er map aanwezig te zijn genaamd \'_temp_\' (hier komen uploads standaard in terecht).
Net zoals bij \'DB mounts\' kunnen de mapkoppelingen (mounts) ook geërfd worden van de groepen waar de gebruiker lid van is.',
		'email.description' => 'Vul het emailadres van de gebruiker in.',
		'email.details' => 'Het invoeren van dit adres is vrij belangrijk, daar berichten van het systeem naar dit adres worden gestuurd.
<strong>Let op!</strong> De gebruiker kan het emailadres zelf aanpassen in de setup module.',
		'realName.description' => 'Geef hier de echte naam in van de gebruiker.',
		'realName.details' => '<strong>Let op!</strong> De gebruiker kan dit zelf aanpassen in de setup module.',
		'disable.description' => 'Deze optie zorgt ervoor dat de gebruiker tijdelijk niet kan inloggen.',
		'admin.description' => 'Admin gebruikers hebben toegang tot het gehele systeem.',
		'admin.details' => 'Admin gebruikers hebben alle rechten. Admin gebruiker behoeven geen lid van een beheergroep te zijn, maar men dient wel op te passen dat een pagina die wordt gemaakt door een admin zonder beheergroep soms niet zichtbaar is voor andere gebruikers. Dit is simpel op te lossen door de admin lid te maken van een beheergroep. Admin zijn gemakkelijk te herkennen aan het rode icoontje inplaats van de blauwe.
Het is aan te raden om de admin rechten slechts toe te kennen aan de beheerder van het systeem.',
		'options.description' => 'Hier wordt bepaald of de gebruiker wel of niet de pagina en bestanden mappenweergave overneemt van de gebruikersgroep.',
		'options.details' => 'Het is een groot voordeel om gebruikers de eigenschappen van de groepen te laten overerven. 
Doet men dit niet dan moet men voor iedere gebruiker afzonderlijk een startpunt voor de mappenweergave ingeven.',
		'fileoper_perms.description' => 'Selecteer permissies voor het bewerken van bestanden voor deze gebruiker',
		'fileoper_perms.details' => 'Deze instellingen refereren aan functies die men vindt in de bestandenlijst module. Zij gelden ook in het algemeen voor het uploaden van bestanden.',
		'starttime.description' => 'Geef hier de datum in vanaf wanneer het account voor deze gebruiker ingaat.',
		'endtime.description' => 'Vanaf de ingegeven datum wordt het account van deze gebruiker geactiveerd.',
		'lang.description' => 'Kies de <em>standaard</em> taal.',
		'lang.details' => 'Dit bepaalt de taal van de backendinterface voor de gebruiker.
Admin gebruikers zullen ervaren dat in bepaalde admin gedeeltes van de site alleen de engelse taal beschikbaar is. Dit is o.a in de submodule "Tools" en de module Web>Template.

<strong>Let op!</strong> Deze instelling is de standaard taal. Wanneer de gebruiker is ingelogd kan deze de instelling veranderen in de setupmodule.',
		'userMods.description' => 'Selecteer de beschikbare backendmodules voor de gebruiker.',
		'userMods.details' => 'Dit bepaalt welke \'menu-items\' er beschikbaar zijn voor de gebruiker.

Bedenk dat de modules die men hier selecteert bij de modules die de gebruiker reeds overerft van de gebruikersgroep komen. Het is daarom ook aan te raden hier geen modules te selecteren, maar het is wel een ideale manier om bepaalde gebruikers van een groep meer rechten te geven dan alleen voor de gebruikersgroep gelden.',
		'TSconfig.description' => 'Voeg hier TSConfig aan voor de gebruiker (geavanceerd).',
		'TSconfig.details' => 'Hier kan men de mogelijkheden voor de gebruiker verder uitdiepen. Alle mogelijkheden worden beschreven in admin_guide.pdf (zie link hieronder).',
	),
	'cz' => Array (
	),
	'pl' => Array (
	),
	'si' => Array (
	),
	'fi' => Array (
		'.description' => 'Tämä on taustatoimintojen hallinnan käyttäjä taulu.',
		'username.description' => 'Anna taustakäyttäjän (login) käyttäjätunnus.',
		'username.details' => 'Käyttäjätunnus on pakollinen, pienin kirjaimin ja ilman välilyöntejä. Käyttäjätunnuksen on oltava lisäksi yksilöllinen. Jos tunnus ei ole yksilöllinen lisätään siihen automaattisesti numero.',
		'password.description' => 'Anna ylläolevalle taustakäyttäjälle salasana. (Huomaa, arvo jonka annat <i>näkyy</i> luettavana kentässä!).',
		'password.details' => 'Salasana on pakollinen. Ennenkuin salasana lähetetään palvelimelle se salataan md5 toiminnolla, joten itse salasanaa ei siirretä internetin välityksellä. Näin tapahtuu sekä muotoiltaessa salasanaa että käyttäjän sisäänkirjoittautuessa.
Tämä periaate ei kuitenkaan salaa raakaa salasanaajoka <i>ei</i> olesama kuin todellinen salaus. Jos tarvitset parempaa suojausta tulee Sinun asentaaTYPO3 tausta toiminnat suojattuun palvelimeen.
Salasana tallennetaan tietokantaa md5 toiminnolla suojattuna ja siten ei ole mahdollista purkaa alkuperäistä salasanaa tietokannastakaan. Tämä tarkoittaa että \'hukatut salasanat\' on korvattava kokonaan uusilla käyttäjälle.',
		'usergroup.description' => 'Määrittele taustakäyttäjäryhmät käyttäjälle.',
		'usergroup.details' => 'Taustakäyttäjäryhmä määrittelee mitkä oikeudet taustakäyttäjä perii. Siten, jos käyttäjä ei ole \'Admin\'., tarvitsee hänelle määritellä yksi tai useampia jotta tarvittavat oikeudet voidaan määritellä. Käyttäjäryhmien oikeudet on useimmiten ryhmiteltyjä.
Ensimmäinen (ylin) ryhmä listalla on, oletusarvoisesti, niiden sivujen omistaja jotka käyttäjä luo.',
		'lockToDomain.description' => 'Anna domainin nimi johon käyttäjä on pakotettu sisäänkirjoittautumaan (login).',
		'lockToDomain.details' => 'TYPO3 järjestelmässä voi olla monta domainia. Siksi tämä vaihtoehto varmistaa käyttäjän pääsevän vain hänelle sallittuihin.',
		'disableIPlock.description' => 'Poista taustakäyttäjiltä session lukitus  ulkoiseen IP numeroon.',
		'disableIPlock.details' => 'Sinun on poistettava tämä lukitus jos taustakäyttäjät TYPO3 käyttäessään ovat ISDN tai modeemiyhteydessä, jotka yleensä saavat aina uuden IP numeron muodostaessaan uuden linjayhteyden. Sama koskee myös DHCP:n antamia IP-osoitteita, jotka uusiutuvat aika ajoin.',
		'db_mountpoints.description' => 'Määrittele puurakenteen aloituskohta käyttäjälle.',
		'db_mountpoints.details' => 'Sivujen puurakenteeseen, jota WEB-aliohjelmat käyttävät, tulee määritellä joitakin liityntä pisteitä. Tässä lisäät yhden tai useamman referenssin sivulle jotka edustavat sivuston rakenteen uutta juurta (alkupistettä).
Tätä kutsutaan \'Tietokannan asetukseksi" ("Database mount").
<strong>Huomaa</strong> taustakäyttäjäryhmällä voi olla Tietokantaasetuksia jotka käyttäjä perii. Joten jos haluat ryhmällä käyttäjiä jakavan sivurakenteen, kannattaa Sinun asettaa sivurakenne taustakäyttäjäryhmälle joka on yhteinen käyttäjille.',
		'file_mountpoints.description' => 'Määrittele aloituskohdat tiedostohakemistolle rakenteelle.',
		'file_mountpoints.details' => 'Tiedostohakemisto rakennetta käyttävät kaikki Tiedosto-aliohjelmat (File-modules) siirtyessään hakemistoissa palvelimella. Jotta käyttäjälle olisi mahdollista ladata (upload) tiedostoja <em>tulee</em> asetettuna ollahakemisto jossa \'_temp_\' hakemisto (tänne ladataan (upload) tiedostot oletusarvoisesti).
Huomaa että Tietokanta asetukset voivat periytyä käyttäjälle siltä käyttäjäryhmältä jonka jäsen hän on.',
		'email.description' => 'Anna käyttäjän sähköpostiosoite.',
		'email.details' => 'Tämä osoite on tärkeä koska siihen lähetetään järjestelmän viestit.
<strong>Huomaa</strong> käyttäjä voi muuttaa tätät tietoa itse Käyttäjä>Asetukset (User>Setup) aliohjelmasta.',
		'realName.description' => 'Anna käyttäjän oikea nimi, esim.Matti Meikäläinen.',
		'realName.details' => '<strong>Huomaa</strong> käyttäjä voi itse muuttaa tätä arvoa Käyttäjä>Asetukset (User>Setup) aliohjelmasta.',
		'disable.description' => 'Tämä vaihtoehto poistaa käyttäjän sisäänkirjoittautumis oikeuden väliaikaisesti.',
		'admin.description' => '\'Admin\' käyttäjillä on TÄYDELLISET oikeudet järjestelmään!',
		'admin.details' => '\'Admin\' voi tehdä mitä tahansa TYPO3 salliikin ja siksi tämänkaltaista käyttäjää tulisikin hyödyntää vain hallinnollisiin tehtäviin. Kaikki päiivttäinen työskentely tulisi tehdä normaalein käyttäjätunnuksin.
\'Adminin\' ei tarvitse olla minkää käyttäjäryhmän tai taustakäyttäjäryhmän jäsen. Huomaa kuitenkin erityisesti seuraavaa, sivut jotka \'admin\'-käyttäjä luo eivät kuulu minhinkään ryhmään ja voivat olla siksi toisten käyttäjien näkymättömissä. Jos tämä tulee ongelmaksi voi luoda \'Admin\'käyttäjälle ryhmän joka tapauksessa. Tällä ei ole vaikutusta \'Admin-k\'yttäjän oikeuksiin vaan uusien sivujen omistajaksi tulee listan ensimmäinen ryhmä.
\'Admin\' käyttäjät tunnistaa helposti punaisesta ikonista tavallisten käyttäjien ikonin ollessa sininen.

Sinun tulee luultavasti määritellä vain itsesi \'Admin\'-käyttäjäksi.',
		'options.description' => 'Valitse periikö käyttäjä sivujen puurakenteen tai hakemistopuun käyttäjäryhmältä.',
		'options.details' => 'On suuri etu antaa käyttäjien peri asetuskohdat käyttäjäryhmiltä koska se tekee samojen asetuskohtien hallinnan usealle käyttäjälle helpoksi.
Jos et käytä näitä vaihtoehtoja, Sinun tulee varmistaa että sivurakenteen ja tiedostovalikoiden astukset ovat asetettu juuri tälle käyttäjälle.',
		'fileoper_perms.description' => 'Valitse mitkä tiedostotoiminnot ovat sallittuja käyttäjälle.',
		'fileoper_perms.details' => 'Nämä asetukset liittyvät Tiedosto>Lista (File>List) aliohjelmaan se yleiseen tiedostojen vientiin (upload).',
		'starttime.description' => 'Valitse päiväys mistä alkaen käyttäjän tili on voimassa.',
		'endtime.description' => 'Valitse päiväys jolloin käyttäjän tili poistetaan (voimassaolo).',
		'lang.description' => 'Valitse oletuskieli.',
		'lang.details' => 'Tämä määrittelee kielen taustakäyttäjälle. Kaikki keskeiset osat ovat käytettävissä järjestelmän kielellä tavallisille käyttäjille.
\'Admin\'-käyttäjät kokevat kuitenkin että vain heille tarkoitetut osat ovat englanniksi. Tämä tarkoittaa kaikkia aliohjelmia Työkalut (Tools) ja Web>Mallinteet (Web>Templates) aliohjelmia.

<b>Huomaa</b> tämä on vain oletuskieli. Kun käyttäjä on sisäänkirjoittautunut voi kielen vaihtaa Käyttäjä>Asetukset aliohjelmassa.',
		'userMods.description' => 'Valitse mitkä taustatoimintojen aliohjelmat ovat sallittuja käyttäjälle.',
		'userMods.details' => 'Tämä määrittelee mitkä\'Valikon toiminnat\' ovat käyttäjän käytettävissä.

Huomaa että sama aliohjelmien lista voidaan konfiguroida käyttäjäryhmälle aj se periytyy käyttäjälle tassä valitsemiesi aliohjelmien lisäksi. On epätodennäköistä että Sinun tarvitsee tässä valita mitään aliohjelmia käyttäjälle vaan voit tehdä sen käyttäjäryhmän avulla joissa hän on jäsen.
Tämä kuitenkin mahdollistaa oivan tavan lisätä yhden erillisen aliohjelman käyttäjälle.',
		'TSconfig.description' => 'Valitse TSconfig lisäykset käyttäjälle (laajennetut).',
		'TSconfig.details' => 'Tämä tieto antaa Sinulle mahdollisuuden antaa käyttäjälle erityisiä asetuksia. Esimerkiksi vaihtoehdot voivat sisältää yksityiskohtaisen konfiguraation tausta alimoduleista, erityisten käyttäjäkohtaisten taulujen oletusarvoista, tai RTE asetuksista jne. Tämä lista kasvaa ajan myötä ja on dokumentoitu Administraatio ohjestuksessa, erityisesti \'admin_guide.pdf\' (katso linkki alla).',
	),
	'tr' => Array (
	),
	'se' => Array (
		'.description' => 'Detta är tabellen med backend administratörsanvändare.',
		'username.description' => 'Fyll i backend-användarens inloggningsnamn',
		'username.details' => 'A username is required and must be in lowercase without spaces in it. Furthermore the username must be unique. If it is not unique a number will be prepended automatically.',
		'password.description' => 'Fyll i backend-användarens lösenord (Märk, att det du fyller i kommer att synas)',
		'password.details' => 'The password is required. Before the password is sent to the server it\'s md5-hashed, so the password value itself is not transferred over the internet. This is true both when editing the password and when the user logs in. 
While this principle does not reveal the raw password it is <i>not</i> the same as real encryption. If you need the highest degree of security you should install the TYPO3 backend on a secure server.
The password is stored in the database as an md5-hash and thus it\'s not possible to extract the original password from the database either. This means that \'lost passwords\' must be substituted with a new password for the user.',
		'usergroup.description' => 'Välj vilka backend-användargrupper som användaren hör till.',
		'usergroup.details' => 'The backend user groups defines the permissions which the backend user will inherit. So unless the backend user is an \'Admin\' user, he needs to be a member of one or more user groups in order to have practically any permissions assigned. The properties set in the user groups are mostly added together.
The first (top) group in the list is the group which will, by default, be the owner of pages the user creates.',
		'lockToDomain.description' => 'Fyll i från vilken värddator användaren måste logga in',
		'lockToDomain.details' => 'A TYPO3 system may have multiple domains pointing to it. Therefore this option secures that users can login only from a certain host name.',
		'disableIPlock.description' => 'Stänger möjligheten att låsa backend-användarens session till en speciell IP-nummer.',
		'disableIPlock.details' => 'You will have to disable this lock if backend users are accessing TYPO3 from ISDN or modem connections which may shutdown and reconnect with a new IP. The same would be true for DHCP assignment of IP numbers where new IP numbers are frequently assigned.',
		'db_mountpoints.description' => 'Ange startpunkter för användarens sidträd.',
		'db_mountpoints.details' => 'The page tree used my all Web-submodules to navigate must have some points-of-entry defined. Here you should insert one or more references to a page which will represent a new root page for the page tree. This is called a \'Database mount\'.

<strong>Notice</strong> that backend user groups also has DB mounts which can be inherited by the user. So if you want a group of users to share a page tree, you should probably mount the page tree in the backend user group which they share instead.',
		'file_mountpoints.description' => 'Ange startpunkter för filernas katalogträd.',
		'file_mountpoints.details' => 'The file folder tree is used by all File-submodules to navigate between the file folders on the webserver. In order to be able to upload <em>any</em> files the user <em>must</em> have a file folder mounted with a folder named \'_temp_\' in it (which is where uploads go by default). 
Notice as with \'DB mounts\' the file folder mounts may be inherited from the member groups of the user.',
		'email.description' => 'Fyll i användarens epostadress.',
		'email.details' => 'This address is rather important to enter because this is where messages from the system is sent.
<strong>Notice</strong> the user is able to change this value by himself from within the User>Setup module.',
		'realName.description' => 'Fyll i användarens riktiga namn, t.ex. Pelle Svensson.',
		'realName.details' => '<strong>Märk</strong> att användaren själv kan ändra detta värde via modulen Användare>Inställningar',
		'disable.description' => 'Denna option stänger tillfälligt användarens rättigheter att logga in.',
		'admin.description' => '\'Admin\'-användare har TOTAL åtkomst till systemet!',
		'admin.details' => '\'Admin\' can do anything TYPO3 allows and this kind of user should be used only for administrative purposes. All daily handling should be done with regular users. 
\'Admin\' users don\'t need to be members or any backend user groups. However you should be aware that any page created by an admin user without a group will not have any owner-group assigned and thus it will probably be invisible for other users. If this becomes a problem you can easily solve it by assigning a user group to the \'Admin\' user anyway. This does of course not affect the permissions since they are unlimited, but the first group listed is by default the owner group of newly created pages.
\'Admin\' users are easily recognized as they appear with a red icon instead of the ordinary blue user-icon.

You should probably not assign any other users than yourself as an \'Admin\' user.',
		'options.description' => 'Välj om användaren skall erhålla sidträds- eller katalogträdsmonteringspunkter från medlemsgrupper.',
		'options.details' => 'It\'s a great advantage to let users inherit mountpoints from membergroups because it makes administration of the same mountpoints for many users extremely easy. 
If you don\'t check these options, you must make sure the mount points for the page tree and file folder tree is set specifically for the user.',
		'fileoper_perms.description' => 'Välj filändringsrättigheter för användaren.',
		'fileoper_perms.details' => 'These settings relates to the functions found in the File>List module as well as general upload of files.',
		'starttime.description' => 'Fyll i från vilket datum kontot skall vara i bruk',
		'endtime.description' => 'Fyll i när kontot skall stängas',
		'lang.description' => 'Välj <i>standard</i> språk.',
		'lang.details' => 'This determines the language of the backend interface for the user. All mainstream parts available for regular users are available in the system language selected. 
\'Admin\'-users however will experience that the \'Admin\'-only parts of TYPO3 is in english. This includes all submodules in "Tools" and the Web>Template module.

<b>Notice</b> this is only the default language. As soon as the user has logged in the language must be changed through the User>Setup module.',
		'userMods.description' => 'Välj vilka backend-moduler användaren skall få.',
		'userMods.details' => 'This determines which \'menu items\' are available for the user.

Notice that the same list of modules may be configured for the backend user groups and that these will be inherited by the user in addition to the modules you select here. It\'s highly likely that you should not set any modules for the user himself but rather select the modules in the backend groups he\'s a member of. However this list provides a great way to add a single module for specific users.',
		'TSconfig.description' => 'Fyll i ytterligare TSconfig för användaren (avancerat).',
		'TSconfig.details' => 'This field allows you to extend the configuration of the user in severe details. A brief summary of the options include a more detailed configuration of the backend modules, setting of user specific default table field values, setting of Rich Text Editor options etc. The list will be growing by time and is fully documented in the adminstration documentation, in particular \'admin_guide.pdf\' (see link below).',
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
		'.description' => 'Ovo ja tablica backend korisnika.',
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
		'lockToDomain.description' => 'Unesi ime raèunala s kojeg je korisnik prisiljen prijaviti se za rad.',
		'lockToDomain.details' => 'TYPO3 sustav može imati višetruke domene koje upuæuju na njega. Stoga ova opcija osigurava da se korisnici mogu prijaviti samo s odreðenog raèunala.',
		'disableIPlock.description' => 'Onemoguæi zakljuèavanje odreðenog sessiona za udeljenu IP adresu.',
		'disableIPlock.details' => 'Morati æete iskljuèiti ovo zakljuèavanje ako korisnici pristupaju backendu pomoæu ISDN ili modemskih veza koje se mogu odspojiti i spojiti sa novom IP adresom.',
		'db_mountpoints.description' => 'Dodijeli poèetne toèke zkorisnièkom stablu stranice.',
		'db_mountpoints.details' => 'Stablo stranica koje koriste svi Web-podmoduli za navigaciju 
mora imati odreðene poèetne toèke ulaska u stablo. Ovdje bi trebali
upisati jednu ili više referenci na stranicu koja æe predstavljati novu
korijen stranicu u stablu stranica. 
To se naziva \'Poveznica baze podataka\' (DB poveznica).

<strong>Primjetite da</strong> Backend korisnièke grupe takoðer
sadrže DB poveznice koje korisnik može naslijediti. U sluèaju da želite više 
korisnika koji dijele jedno stablo stranice, trebate napraviti poveznicu stabla 
stranice u backend korisnièkoj grupi koju æe korisnici naslijediti.',
		'file_mountpoints.description' => 'Dodijeli poèetne toèke stablu datoteka',
		'file_mountpoints.details' => 'Stablo datoteka koriste svi podmoduli za navigaciju meðu direktorijima na web poslužitelju. Kako bi bili u moguænosti izvršiti upload <em>bilo koje</em> datoteke korisnik  <em>mora</em> imati direktorij postavljen sa poddirektorijem nazvanim \'_temp_\' unutar njega (kamo idu uploadi).
Primjetite da kao i sa \'DB mounts\' direktoriji za datoteke isto mogu biti naslijeðeni od korisnièke grupe kojoj pripada korisnik.',
		'email.description' => 'Upišite email adresu korisnika.',
		'email.details' => 'Ovu je adresu vrlo važno unijeti jer se na nju šalju sistemske poruke.
<strong>Primjdba</strong> Korisnik može sam mijenjati adresu
u Korisnik>Postavke modulu.',
		'realName.description' => 'Unesi pravo ime korisnika, npr. Ivo Iviæ',
		'realName.details' => '<strong>Napomena</strong> Korisnik može sam mijenjati adresu u Korisnik>Postavke modulu.',
		'disable.description' => 'Ova moguænost privremeno onemoguæava korisniku pristup.',
		'admin.description' => '\'Admin\' korisnici imaju POTPUN pristup sustavu!',
		'admin.details' => '\'Admin\' može raditi sve što TYPO3 omoguæuje i ovaj bi se tip korisnika trebao koristiti samo u administracijske svrhe. Sve drugo bi trebalo raditi s obiènim korisnicima. 
\'Admin\' korisnici ne moraju biti èlanovi ni jedne korisnièke grupe. Svejedno, treba imati na umu da æe svaka stranica koju napravi admin korisnik koji nije èlan neke grupe biti napravljena bez zadanog vlasnika ili grupe, pa neæe biti vidljiva ostalim korisnicima. Ako to postane problem, lako je rješiv time da se admin korisniku dodijeli neka postojeæa grupa. Time se naravno ne utjeæe na ovlasti jer su one ionako za admin korisnika neogranièene.
\'Admin\' korisnike je lako razaznati jer je njihova ikona u crvene umjesto plave boje obiènog korisnika.

Vjerovatno ne bi trebali odrediti nikoga osim sebe kao \'Admin\' korisnika.',
		'options.description' => 'Odaberite treba li korisnik naslijediti stablo stranica ili stablo direktorija od korisnièkih grupa kojima pripada.',
		'options.details' => 'Velika je prednost ukoliko se dopusti da korisnici naslijede postavljene direktorije od korisnièkih grupa kojima pripadaju jer je na taj naèin administracija veæeg broja korisnika vrlo laka. 
Ako ne odaberete ove opcije, morate osigurati da je stablo stranica i stablo direktorija postavljeno za svakog korisnika zasebno.',
		'fileoper_perms.description' => 'Odaberite dozvole za rad s datotekama za korisnika.',
		'fileoper_perms.details' => 'Ove postavke vrijede za funkcije u Datoteka>Popis modulu kao i za opæeniti upload datoteka.',
		'starttime.description' => 'Unesite datum od kojeg æe korisnièki raèun biti aktivan.',
		'endtime.description' => 'Unesite datum od kojeg æe korisnièki raèun biti onemoguæen.',
		'lang.description' => 'Odaberite <i>zadani</i> jezik.',
		'lang.details' => 'Ovom postavkom se odreðuje jezik backend suèelja korisnika. Svi glavni dijelovi sustava dostupni su u odabranom jeziku.
\'Admin\' korisnici primjetiti æe da su dijelovi TYPO3 namijenjeni samo \'Admin\'-ima na engleskom. To ukljuèuje sve podmodule u "Tools" i sve Web>Template module.

<b>Napomena</b> Ova postavka vrijedi samo za zadani jezik. Èim se korisnik prijavi na sustav ova postavka se mora promieniti kroz User>Setup modul.',
		'userMods.description' => 'Odaberite backend module koji æe biti dostupni korisniku',
		'userMods.details' => 'Ovom postavkom se odreðuje sadržaj menija koji æe biti dostupan korisniku.

Primjetite da ista lista modula može biti konfigurirana za backend korisnièke grupe te æe u tom sluèaju ona biti naslijeðena i dopunjena s modulima koji su ovdje selektirani. Poželjno je postavljati dostupne module putem backend korisnièkih grupa umjesto dodavanja modula putem ove opcije. Naravno ova opcija vam omoguæava da dodate pojedini modul specifiènom korisniku.',
		'TSconfig.description' => 'Unesite dodatne TSconfig instrukcije za korisnika (napredno).',
		'TSconfig.details' => 'Putem ovog polja moguæe je proširiti konfiguraciju koirsnika u nekoliko detalja. Ukratko ovdje možete detaljnije konfigurirati backend module, postaviti za posebno za kosirnika predefinirana vrijednos tpojedninih polja, definirati postavke WYSWYG editora (RTE editor), itd. Popis opcije koje možete podešavati putem ovog polja raste s vremenom i u potpunosti je dokumentiran u administratorskoj dkoumentaciji, posebno u \'admin_guide.pdf\' (pogledaj link ispod).',
	),
	'hu' => Array (
		'.description' => 'Ez a Backend adminisztrátor felhasználók táblája.',
		'username.description' => 'Add meg a backend felhasználó bejelentkezõ nevét.',
		'username.details' => 'A felhasználó név szükséges és csak kisbetûkbõl állhat szóköz nélkül. Továbbá a felhasználói névnek egyedinek kell lennie. Ha nem egyedi, akkor elé automatikusan egy szám kerül.',
		'password.description' => 'Add meg a fenti backend felhasználó részére a jelszót. Figyelj, mert a beírt adat <i>olvasható lesz</i> a mezõben.',
		'password.details' => 'A jelszó kötelezõ. Mielött a szerverre továbbításra kerül md5
szerint kódolódik, így maga a jelszó nem továbbítódik az
interneten. ez igaz mind a jelszó szerkesztésére mind a
felhasználó bejelentkezésére is.
Mivel  ez a dolog nem látható kívülrõl, a feldolgozott jelszó
<i>nem</i> azonos a valódi titkosítással. Ha magasabb
fokú biztonság szükséges, a TYPO3 backend rendszert egy
biztonságos szerverre kell telepíteni.
A jelszó md5 kódolással kerül tárolásra az adatbázisban, így
a jelszót nem lehet visszafejteni az adatbázisból sem. Ezt azt
jelenti, hogy az elfelejtett jelszót újjal kell helyettesíteni',
		'usergroup.description' => 'Backend felhasználócsoportot rendel a felhasználóhoz.',
		'usergroup.details' => 'A backend felhasználócsoport jogosultságokat ad meg,
amelyeket a backend felhasználó örököl. Így a felhasználó
hacsak nem \'Adminisztrátor \' felhasználó, egy vagy több
csoporttagsággal kell rendelkeznie, hogy gyakorlatilag
jogosultsággal rendelkezzen  A felhasználó csoport
tulajdonságai általában összeadódnak.
Ez elsõ (legfelsõ) csoport a listában az a csoport, amelyik a
felhasználó által létrehozott oldal tulajdonosa lesz.',
		'lockToDomain.description' => 'Add meg annak a hosztgépnek a nevét, ahonnan a felhasználónak be kell jelentkeznie.',
		'lockToDomain.details' => 'A TYPO3 rendszerre több domain mutathat. Így az opció biztosítja, hogy a felhasználó csak bizonyos hosztgéprõl jelentkezhet be.',
		'disableIPlock.description' => 'Távoli backend felhasználók IP cím zárolásának megszüntetése.',
		'disableIPlock.details' => 'Akkor kapcsold ki, ha vannak backend felhasználók, akik ISDN vagy modem használatával kapcsolódnak a TYPO3-hoz, és az IP címük változhat újrakapcsolódáskor. Ugyanez érvényes, ha DHCP szerver rendeli a felhasználókhoz az IP címeket.',
		'db_mountpoints.description' => 'A felhasználó oldalrendszeréhez kiindulópontot rendel.',
		'db_mountpoints.details' => 'Az összes, navigálásra használt web-almodul
oldalrendszerének rendelkeznie kell megadott belépési
ponttal. Itt egy vagy több hivatkozást kell beszúrni egy
oldalhoz, amely az oldalcsoporthoz új  kiinduló oldalt ad
 meg. Ezt hívják adatbázis csatolásnak.
<strong>Figyelem:</strong> a backend felhasználó
csoportoknak szintén vannak adatbáziscsatolásaik, amelyek a 
felhasználótól öröklõdtek. Így, hogy egy felhasználócsoport
megoszthasson egy oldalrendszert, azt valószinûleg csatolni
kell ahhoz a backend felhasználó csoporthoz, amely
megosztja azokat.',
		'file_mountpoints.description' => 'A fájlrendszerhez kiindulópontot rendel.',
		'file_mountpoints.details' => 'A fájl könyvtár-rendszert az összes, a webszerver
fájlkönyvtárai közötti navigálásra szánt  fájl almodul használja.
<em>Bármely<-/em> fájlfeltöltéshez a felhasználónak
<em>rendelkeznie kell</em> \'_temp_\' nevû csatolt
fájlkönyvtárral (amelyik a feltöltés alapértelmezett könyvtára).
Meg kell jegyezni, hogy az adatbázis csatolásokhoz hasonlóan, a
fájlkönyvtár csatolások is öröklõdnek a csoporthoz tartozó
felhasználóktól.',
		'email.description' => 'Add meg a felhasználó email címét.',
		'email.details' => 'A címet nagyon fontos megadni mivel ide érkeznek a
rendszer üzenetei.
<strong>Figyelem:</strong> a felhasználó maga
megváltoztathatja ezt az adatot a Felhasználó>Beállítás
modulban.',
		'realName.description' => 'Add meg a felhasználó szabályos nevét, pl.: John Doe',
		'realName.details' => '<strong>Figyelem:</strong> a felhasználó maga megváltoztathatja ezt az adatot a Felhasználó>Beállítás modulban.',
		'disable.description' => 'Ez az opció ideiglenesen megakadályozza a felhasználót a bejelentkezésben.',
		'admin.description' => 'Az \'Adminisztrátor\' felhasználók az ÖSSZES jogosultsággal rendelkeznek a rendszerben!',
		'admin.details' => 'Az \'Admin\' bármit megtehet, amit a TYPO3 megenged,
ez az adminisztrátor típus csak adminisztrációs céllal
használható. A napi karbantartásokat normál felhasználókkal
ajánlatos elvégeztetni. Az \'Admin\' felhasználóknak nem kell
szükségképpen egyik backend felhasználó csoporthoz sem
tartozni. Tehát óvakodni kell attól, hogy oldalakat
létrehozó \'Admin\' felhasználók ne tartozzanak egyik csoportba
sem, mert így valószinûleg láthatatlanok más felhasználók
részére. Ha ez a probléma felmerül, könnyen megoldható egy
csoport hozzárendelése az \'Admin\'-hoz valamilyen módon.
Ez természetesen nem érinti a jogosultságokat, mivel ezek
korlátlanok, de az elsõ listába felvett csoport
alapértelmezésként az újonnan létrehozott oldalak
tulajdonos csoportja.
Az \'Admin\' felhasználók könnyen felismerhetõek piros ikonukról
a szokásos kék felhasználói ikonnal ellentétben.
Valószínûleg csak magadat fogod \'Admin\' felhasználóként
felvenni.',
		'options.description' => 'Válaszd ki, ha a felhasználónak örökölnie ajánlatos az oldal- és fájlrendszer csatlakozási pontokat a tagcsoporttól.',
		'options.details' => 'Nagy elõny a felhasználóknak a tagcsoporttól öröklési
csatolópontokat adni, mert ez igen megkönnyíti ugyanannak a
csatolópontnak az adminisztrációját.
Ha nem ellenõrzöd ezeket a funkciókat, meg kell gyõzõdnöd
arról, hogy az oldalrendszer csatolópontjai és a fájl
könyvtárrendszer be legyen állítva a felhasználó részére.',
		'fileoper_perms.description' => 'Válassz mûködési engedélyt a felhasználónak.',
		'fileoper_perms.details' => 'Ezek a beállítások kapcsolódnak a Fájl>Lista modul-ban található funkciókhoz.',
		'starttime.description' => 'Add meg azt a dátumot, amikortól a fiók aktív.',
		'endtime.description' => 'Add meg azt a dátumot, amikortól a fiók nem engedélyezett.',
		'lang.description' => 'Válaszd ki az <i>alapértelmezett</i> nyelvet!',
		'lang.details' => 'Meghatározza a felhasználó részére a backend felület nyelvét.
Minden, a normál felhasználók részére elérhetõ fõfolyam a
rendszer kiválasztott nyelvén lesz elérhetõ.
Az \'Admin\' felhasználók tehát azt tapasztalják, hogy a
TYPO3-ban csak \'Admin\'-részek angol nyelvûek. Ebbe
beletartozik az "Eszközök" összes almodulja valamint
a Web>Sablon modul.
<b>Figyelem:</b> ez csak az alapértelmezett nyelv. Amint a 
felhasználó bejelentkezik, a nyelv megváltoztatható a
Felhasználó>Telepítés modulban.',
		'userMods.description' => 'Válaszd ki  a felhasználó számára elérhetõ backend modulokat.',
		'userMods.details' => 'Megadja a felhasználó által elérhetõ menüpontokat.
Fontos, hogy maga a modullista konfigurálható a backend
felhasználó  csoportok részére és ezeket a felhasználó örökölje
az itt kiválaszott modulokkal együtt. Igen valószínû, hogy nem
állítunk be semmilyen modult magára a felhasználóra, hanem
inkább annak a csoportnak, aminek a tagja. Tehát a lista nagy
lehetõséget biztosít egy egyszerû modul hozzárendeléshez
bizonyos felhasználók részére.',
		'TSconfig.description' => 'Adj kiegészítõ TSconfig-ot a felhasználónak (részletes).',
		'TSconfig.details' => 'Ez a mezõ lehetõséget ad a felhasználó konfigurálásának jelentõs kiterjesztésére. Az opció rövid összegzése magában foglalja a backend modulok részletes konfigurációját a felhasználó specifikus alapértelmezett tábla mezõk beállításával, a Rich Text Editor kiválasztási lehetõségével. A lista idõvel nõni fog és az adminsztrációs dokumentációban résztesen ki lesz fejtve, fõleg az \'admin_guide.pdf\'-ben (lásd alább).',
	),
	'gl' => Array (
	),
	'th' => Array (
	),
	'gr' => Array (
	),
	'hk' => Array (
		'.description' => '³o¬O«á¶ÔºÞ²z¨Ï¥ÎªÌªº¸ê®Æªí',
		'username.description' => '¿é¤J«á¶Ô¨Ï¥ÎªÌªºµn¤J¦WºÙ',
		'username.details' => '¨Ï¥ÎªÌ¦WºÙ¬O¥²¶·ªº©M¤@©w¥H²Ó¶¥¿é¡A¨ä¤¤¤£¯à¦³ªÅ¹j¡C¨Ï¥ÎªÌ¦WºÙ¬O¿W¤@ªº¡C°²¦p¤£¬O¿W¤@ªº¡A·|¦Û°Ê¥[¤W¼Æ¦r',
		'password.description' => '¿é¤J¤W­±«á¶Ô¨Ï¥ÎªÌªº±K½X¡]¯d·N§A¦bÄæ¤¤¿é¤Jªº­È<i>·|</i>³Q¬Ý¨£¡^',
		'password.details' => '»Ý­n±K½X¡C¦b±K½X§âMD5¥[±K¶Ç°e¥h¦øªA¾¹¤§«e¡A¦]¦¹±K½X­È¥»¨­¤£·|³z¹L¤¬Ápºô¶Ç°e¡C¦b¨Ï¥ÎªÌ­×§ï±K½X®É©Mµn¤J®É³£¬O³o¼Ë¡C
³o­Ó­ì«h¤£·|±Ò¥Ü­ì©l±K½X¡A¦ý¬O¥¦<i>¤£¬O</i>»P¯u¥¿ªº¥[±K¬Û¦P¡C°²¦p§A­n§ó°ªµ{«×ªº«O±K¡A§AÀ³¸Ó§âTYPO3ªº«á¶Ô¦w¸Ë¦b¤@­Ó¦w¥þªº¦øªA¾¹¤W¡C
±K½X¥HMD5¥[±Kªº§Î¦¡ÀxÀx¦s¦b¸ê®Æ®w¤¤¡A¦]¦¹¤]¤£¥i¯à±q¤¤©â¨ú­ì¥»ªº±K½X¡C³o¥Nªí¨Ï¥ÎªÌ¡u¿ò¥¢ªº±K½X¡v¤@©w­n¥Î·sªº±K½X¨ú¥N¡C',
		'usergroup.description' => '³]©w«á¶Ô¨Ï¥ÎªÌ©ÒÄÝªº¸s²Õ',
		'usergroup.details' => '«á¶Ô¨Ï¥ÎªÌ¸s²Õ©w¸q«á¶Ô¨Ï¥ÎªÌ©Ò©ÓÅ§ªºÅv­­¡C¦]¦¹¡A°£«D«á¶Ô¨Ï¥ÎªÌ¬O¡uºÞ²z­û¡v¯Å¨Ï¥ÎªÌ¡A§_«h¥L»Ý­n¦¨¬°¤@­Ó©Î¥H¤Wªº¨Ï¥ÎªÌ¸s²Õªº¦¨­û¡A¥H­P¥L¥i¥H¹ê¦b¦a±o¨ì©Ò½á¤©ªºÅv­­¡C¦b¨Ï¥ÎªÌ¸s²Õ¤¤©Ò³]©wªºµ¥©Ê¤j³¡¤À³Q³s¦X¦¨¬°¾ãÅé¡C
¦b¦W¤¤ªº²Ä¤@­Ó¡]³Ì¤Wªº¡^¨Ï¥ÎªÌ¸s²Õ¡A¹w³]¬°¨Ï¥ÎªÌ©Ò«Ø¥ßªººô­¶ªº¾Ö¦³ªÌ',
		'lockToDomain.description' => '¿é¤J¥D¾÷ªº¦WºÙ¡A¨Ï¥ÎªÌ¥²¶·±q¦¹µn¤J',
		'lockToDomain.details' => '¤@­ÓTYPO3¨t²Î¥i¥H¦³¦h­Óºô°ì¨Ï¥Î¡C¦]¦¹¡A³o­Ó¿ï¶µ«OÃÒ¥u¥i¥H±q¬Y¤@¥D¾÷µn¤J',
		'disableIPlock.description' => 'Ãö³¬°O¿ý«á¶Ô¨Ï¥ÎªÌIP¦ì§}ªº¥\\¯à',
		'disableIPlock.details' => '§A»Ý­nÃö³¬¦¹¥\\¯à¡A°²¦p¦³«á¶Ô¨Ï¥ÎªÌ¥HISDN©ÎMODEM³sµ²¨Ó¶i¤JTYPO3¡A¦]³o¨Ç³sµ²·|¦bÃö³¬¦A³s±µ«á±o¨ì·sªºIP¦ì§}¡C¥ÎDHCP¤À°t¦ì§}¥ç¤@¼Ë¡A±`±`·|¤À°t¨ì·sªºIP¦ì§}',
		'db_mountpoints.description' => '³]©w¨Ï¥ÎªÌºô­¶¾ð¹Ïªº°_©lÂI',
		'db_mountpoints.details' => 'ºô­¶¾ð¹Ï³Q©Ò¦³ºô¯¸°Æ¼Ò²Õ¥Î¥H·ÈÄýºô­¶¡A¥¦¤@©w­n¦³¬Y­Ó©w¸qªº°_©lÂI¡C³o¸Ì§AÀ³¸Ó´¡¤J©Î¥H¤Wªº°Ñ·Ó­Ó¬°¾ð¹Ï¤¤ªº·s®Ú­¶¡C³oºÙ¬°¡u¸ê®Æ®w±¾ÂI¡v

<strong>¯d·N</strong>¨Ï¥ÎªÌ¥i¥H©ÓÅ§©ÒÄÝªº«á¶Ô¨Ï¥ÎªÌ¸sªº¡u¸ê®Æ®w±¾ÂI¡v¡C¦]¦¹¡A°²¦p§A·Q¤@¸s²Õªº¨Ï¥ÎªÌ¦@¥Î¤@­Ó¾ð¹Ï¡A§AÀ³¸Ó¦b¥L­Ì¦PÄÝªº¸s²Õ¤¤±¾¤J¾ð¹Ï¡C',
		'file_mountpoints.description' => '³]©wÀÉ®×¸ê®Æ§¨¾ð¹Ïªº°_©lÂI',
		'file_mountpoints.details' => 'ÀÉ®×¾ð¹Ï³Q©Ò¦³ÀÉ®×°Æ¼Ò²Õ¥Î¥H·ÈÄýºô¯¸¦ø¾¹¤WªºÀÉ®×¡C¬°¤F¯à°÷¤W¸ü<em>¥ô¦ó</em>ÀÉ®×¡A¨Ï¥ÎªÌ<em>¤@©w</em>­n¦³¤@­ÓÀÉ®×¸ê®Æ§¨±¾©ó¤@­ÓºÙ¬°¡u_temp_¡v¡]³o¬O¤W¸üªºÀÉ®×¹w³]ªºÀx¦s¦ì¸m¡^ªº¸ê®Æ§¨¤§¤W¡C
¯d·N¡A¥¿¦p¡u¸ê®Æ®w±¾ÂI¡v¤@¼Ë¡AÀÉ®×¸ê®Æ§¨±¾ÂI¤]¥i¥H³Q¨Ï¥ÎªÌ±q¨Ï¥ÎªÌ¸s²Õ¤¤©ÓÅ§¡C',
		'email.description' => '¿é¤J¨Ï¥ÎªÌªº¹q¶l¦a§}',
		'email.details' => '¬O§_¿é¤J³o­Ó¦a§}¤£¤Ó­«­n¡A³o¬O±H¥X°T®§ªº¨t²Îªº¦a§}¡C
<strong>¯d·N</strong>¨Ï¥ÎªÌ¥i¥H¦Û¤v¦b¡u¨Ï¥ÎªÌ>³]©w¡v¼Ò²Õ¤¤§ïÅÜ³o­Ó­È',
		'realName.description' => '¿é¤J¨Ï¥ÎªÌ¯u©m¦W',
		'realName.details' => '<strong>¯d·N</strong>¨Ï¥ÎªÌ¯à°÷¦Û¤v¨Ï¥Î¡u¨Ï¥ÎªÌ>³]©w¡v¼Ò²Õ§ïÅÜ³o­Ó­È',
		'disable.description' => '³o­Ó¿ï¶µ·|¼È®ÉÃö³¬¨Ï¥ÎªÌªºµn¤J¥\\¯à',
		'admin.description' => '¡uºÞ²z­û¡v¨Ï¥ÎªÌ¹ï¨t²Î¦³§¹¾ãªº¦s¨úÅv',
		'admin.details' => '¡uºÞ²z­û¡v¥i¥H°µ¥ô¦óTYPO3®e³\\ªº¨Æ¡A¦Ó³oÃþ¨Ï¥ÎªÌÀ³¸Ó¬°¤FºÞ²zªº¥Øªº¤~·|¹B¥Î¡C©Ò¦³¤é±`ªº¤u§@À³¸Ó¥H´¶³qªº¨Ï¥ÎªÌ³B²z¡C¡uºÞ²z­û¡v¤£»ÝÄÝ©ó¥ô¦ó«á¶Ô¨Ï¥ÎªÌ¸s²Õ¡CµM¦Ó§AÀ³¸Ó¯d·N¥Ñ¤£ÄÝ©ó¥ô¸s²ÕªººÞ²z­û¯Å¨Ï¥ÎªÌ©Ò«Ø¥ßªº©Ò¦³ºô­¶±N¤£·|Àò°tµ¹¥ô¦óªº¦³ªÌ¸s²Õ¡A¦]¦¹¨ä¥L¨Ï¥ÎªÌ¤]³\\·|¬Ý¤£¨ì³o¨Çºô­¶¡C°²¦p¦¹°ÝÃD¥X²{¡A§A¥i¥H»´©ö¦a³z¹L°tµ¹¸s²Õµ¹¡uºÞ²z­û¡v¨Ï¥ÎªÌ¨Ó¸Ñ¨M³o­Ó°ÝÃD¡C¼Ë°µ·íµM¤£·|¼vÅT¤FÅv­­¡A¦]¬°Åv­­¬O¨S¦³­­¨îªº¡A¦ý¬O¦W³æ¤W²Ä¤@­Ó¸s²Õ³Q¹w³]¬°·s«Ø¥ßªººô­¶ªº¾Ö¦³ªÌ¸s²Õ¡C
¡uºÞ²z­û¡v¨Ï¥ÎªÌ¬O©ö©ó¿ë»{ªº¡A¦]¬°¥L­Ì¾Ö¦³¬õ¦âªº¨Ï¥ÎªÌ¹Ï¥Ü¡A³o»P¤@¯ë¨Ï¥ÎªÌÄx¦â¹Ï¥Ü¦³©Ò¤£¦P¡C

§A³Ì¦n°£¤F¦Û¤v¥H¥~¡A¤£°tµ¹¥ô¦ó¨Ï¥ÎªÌ§@¬°¡uºÞ²z­û¡v¨Ï¥ÎªÌ¡C',
		'options.description' => '°²¦p¨Ï¥ÎªÌÀ³¸Ó±q¸s²Õ©ÓÅ§ºô­¶¾ð¹Ï©Î¸ê®Æ§¨¾ð¹Ï±¾ÂI¡A½Ð¿ï¾Ü³o­Ó¿ï¶µ',
		'options.details' => 'Åý¨Ï¥ÎªÌ±q¸s²Õ¤¤©ÓÅ§±¾ÂI¦³«Ü¦nªº¦n³B¡A¦]¬°·|¨ÏºÞ²z¦h­Ó¨Ï¥ÎªÌ¦@¦P¨Ï¥Î¤@­Ó±¾ÂIÅÜ¬°·¥¤§®e©ö
°²¦p§A¤£¿ï¾Ü³o­Ó¿ï¶µ¡A§A¤@©w­n¬°¨Ï¥ÎªÌ¯S§O¦bºô­¶¾ð¹Ï©M®×¾ð¹Ï¤¤³]©w±¾ÂI¡C',
		'fileoper_perms.description' => '¬°¨Ï¥ÎªÌ¿ï¾ÜÀÉ®×¹B§@ªºÅv­­',
		'fileoper_perms.details' => '³o¨Ç³]©wÃö«Y¨ì¦b¡uÀÉ®×>ªí¦C¡v¤¤§ä¨ìªº¥\\¯à¡A¤]Ãö«Y¨ì¤@¯ëªºÀÉ®×¤W¸ü',
		'starttime.description' => '¿é¤J±b¤á¥Í®Äªº¤é´Á',
		'endtime.description' => '¿é¤J±b¤á¥¢®Äªº¤é´Á',
		'lang.description' => '¿ï¾Ü<i>¹w³]</i>ªº»y¨¥',
		'lang.details' => '¨M©w¨Ï¥ÎªÌ©Ò¨Ï¥Îªº«á¶Ô¤¶­±ªº»y¨¥¡C©Ò¦³¥i¨Ñ¤@¯ë¨Ï¥ÎªÌ¨Ï¥Îªº¥D­n³¡¤À³£¥i¥H¦b¿ï¾Üªº¨t²Î»y¨¥¤¤¹B¥Î¡CµM¦Ó¡uºÞ²z­û¨Ï¥ÎªÌ¡v·|¸g¾úTYPO3¤¤¡u¥u­­ºÞ²z­û¡v³¡¤À¬O­^»yªº¡C¥]¬A¤F©Ò¦³¡u¤u¨ã¡v¼Ò²Õ¤¤ªº°Æ¼Ò²Õ©M¡uºô­¶>¼Ëª©¡v¼Ò²Õ¡C

<b>¯d·N<¡þb>³o¥u¬O¹w³]»y¨¥¡C¤@¥¹¨Ï¥ÎªÌ¤w¸gµn¤J¡A»y¨¥¤@©w­n³z¹L¡u¨Ï¥ÎªÌ>³]©w¡v¼Ò²Õ§ïÅÜ',
		'userMods.description' => 'µ¹¨Ï¥ÎªÌ¿ï¾Ü¥i¥Hªº«á¶Ô¼Ò²Õ',
		'userMods.details' => '¨M©w¦³¨º¨Ç¡u¿ï³æ¶µ¥Ø¡v¥i¨Ñ¨Ï¥ÎªÌ¨Ï¥Î¡C

¯d·N«á¶Ô¨Ï¥ÎªÌ¸s²Õªº¼Ò²Õ³]©w¥i¥H¬O¬Û¦Pªº¡A°£¤F§A¦¹¾Üªº¼Ò²Õ¥~¡A¦A¥[¤W±q¸s²Õ©ÓÅ§ªº¼Ò²Õ¦¨¬°¤F¥i¥Îªº¼Ò²Õ¦W³æ¡C¸û¨Î¿ìªk¬O¤£­n¬°­Ó§O¨Ï¥ÎªÌ³]©w¼Ò²Õ²M³æ¡A¦Ó¬O¦b¨ä©ÒÄÝªº«á¶Ô¨Ï¥ÎªÌ¸s²Õ¤¤³]©w¥i¥Î¼Ò²Õªº²M³æ¡CµM¦Ó³o¸Ìªº¦W³æ¥i¥H´£¨Ñ«Ü¦nªº³~®|¨Ó¬°¬Y­Ó¨Ï¥ÎªÌ¥[¤J¤@­Ó¼Ò²Õ',
		'TSconfig.description' => '¬°¨Ï¥ÎªÌ¿é¤Jªþ¥[ªº TSconfig¡]¶i¶¥¡^',
		'TSconfig.details' => '³o­ÓÄæ¦ì®e³\\§A·¥«×¸Ô²Ó¦a©µ¦ù¨Ï¥ÎªÌªº³]©w¡C³o­Ó¿ï¶µªºÂ²¤¶¥]¬A¤@­Ó¸ÔºÉªº«á¶Ô¼Ò²Õ³]©w¡A³]©w¨Ï¥ÎªÌ¦³ªº¸ê®ÆªíÄæ¦ì­È¡A³]©w¦h¥\\¯à¤å¦r½s¿è¾¹ªº¿ï¶µµ¥¡C³o­Ó¦W³æ·|ÀH®É¶¡¼W¥[¡A¨Ã¥B¦bºÞ²z»¡©ú¤å¥ó¤¤¦³¸ÔºÉªº»¡©ú¡A¯S§O¬O¡uadmin_guide.pdf¡v¡]°Ñ¬Ý¥H¤U³sµ²¡^',
	),
	'eu' => Array (
	),
	'bg' => Array (
	),
	'br' => Array (
		'.description' => 'Esta é a tabela de usuários com acesso ao Painel de Administração.',
		'username.description' => 'Digite o nome de login do usuário-administrador.',
		'username.details' => 'Um nome de usuário é necessário, e deve estar em minúsculas e sem conter espaços. Além disso, o nome de usuário deve ser exclusivo. Se não for, um número será adicionado ao nome automaticamente.',
		'password.description' => 'Digite a senha correspondente para o usuário-administrador acima (note que a senha digitada <i>estará</i> visível neste campo!).',
		'password.details' => 'A senha é necessária. Antes de ser enviada ao servidor, a senha é codificada (md5), para que o valor original não seja enviado através da internet. Isso ocorre tanto ao editar a senha quanto no login do usuário.
Embora este princípio não revele a senha original, <i>não</i> é o mesmo que encriptação de verdade. Se você precisar do máximo grau de segurança, deve instalar o Painel de Adminstração do TYPO3 em um servidor seguro.
A senha é armazenada no banco de dados como um código md5 e sendo assim também não é possível extrair a senha original a partir do banco de dados. Isso significa que \'senhas esquecidas\' precisam ser substituídas por uma nova senha para cada usuário.',
		'usergroup.description' => 'Atribuir grupos de usuários-administradores a este usuário.',
		'usergroup.details' => 'Os grupos de usuários-administradores definem as permissões que serão herdadas pelo usuário. Portanto, exceto quando um usuário-administrador é um usuário \'Admin\', ele deve ser membro de um ou mais grupos para receber as permissões adequadas. As propriedades definidas aos grupos de usuários são na sua maioria incluídas juntas.
O primeiro grupo (do topo) da lista é o grupo que, por padrão, será proprietário das páginas criadas pelo usuário.',
		'lockToDomain.description' => 'Insira o nome do servidor pelo qual o usuário é forçado a logar.',
		'lockToDomain.details' => 'Um sistema TYPO3 pode ter múltiplos domínios apontando para ele. Logo, esta opção garante que usuários apenas possam logar a partir de um certo domínio.',
		'db_mountpoints.description' => 'Delegar pontos de partida para a árvore de páginas dos usuários.',
		'db_mountpoints.details' => 'A árvore de páginas usada por todos os Submódulos-web para navegar deve ter alguns pontos-de-entrada definidos. Você deve inserir aqui uma ou mais referências para uma página que irá representar uma nova página raiz para a árvore de páginas. Isto se chama \'Montar banco de dados\'.

<strong>Atenção</strong> grupos de usuários-administradores também têm montagens de banco de dados que podem ser herdadas pelo usuário. Então se você quer que um grupo de usuários compartilhe uma árvore de páginas, você deve montar a árvore de páginas no grupo de usuários-administradores ao qual pertencem.',
		'file_mountpoints.description' => 'Delegar pontos de partida para a árvore de pastas de arquivos.',
		'file_mountpoints.details' => 'A árvore de pastas de arquivos é utilizada por todos os submódulos-Arquivo para a navegação pelas pastas de arquivos do servidor. Para poder enviar <em>qualquer</em> arquivo, o usuário <em>precisa</em> ter uma pasta de arquivos montada, com outra pasta de nome \'_temp\' dentro dela (para onde os arquivos são enviados por padrão).
Observe que, assim como a \'montagem de banco de dados\', a montagem de pastas de arquivos pode ser herdada dos grupos aos quais o usuário pertence.',
		'email.description' => 'Insira o endereço de e-mail do usuário.',
		'email.details' => 'Este endereço é importante pois é para onde as mensagens do sistema serão enviadas.
<strong>Atenção</strong> o usuário tem a opção de mudar este campo sozinho através do módulo Usuários>Configuração.',
		'realName.description' => 'Insira o nome do usuário, ex: João Ninguém.',
		'realName.details' => '<strong>Atenção</strong> o usuário tem a opção de mudar este campo sozinho através do módulo Usuário>Configuração.',
		'disable.description' => 'Esta opção irá desabilitar temporariamente o login de usuários.',
		'admin.description' => 'Usuários \'Admin\' têm acesso TOTAL ao sistema!',
		'admin.details' => 'Usuários \'Admin\' podem fazer qualquer coisa que o TYPO3 permitir, assim este tipo de usuário deve ser utilizado apenas para tarefas administrativas. Todo o manuseio diário deve ser feito através de usuários comuns.
Usuários \'Admin\' não precisam ser membros de algum grupo de usuários. Entretanto, note que qualquer página criada por um usuário \'Admin\' sem grupo não terá um grupo-proprietário atribuído, e assim provavelmente ficará invisível a outros usuários. Se isso se tornar um problema, você pode facilmente resolvê-lo atribuindo um grupo ao usuário \'Admin\'. Isso, claro, não afeta as permissões, já que são ilimitadas, mas o primeiro grupo listado é por padrão o grupo-proprietário das novas páginas criadas.
Usuários \'Admin\' são facilmente reconhecidos, já que são indicados por um ícone vermelho ao invés do ícone de usuário normal, azul.

Você provavelmente não definirá quaisquer outros usuários, além de você mesmo, como usuários \'Admin\'.',
		'options.description' => 'Selecione se o usuário deve herdar dos grupos de usuário os pontos de montagem de página ou pastas.',
		'options.details' => 'É uma grande vantagem permitir que os usuários herdem pontos de montagem dos grupos de usuários, uma vez que torna a administração destes pontos de montagem extremamente fácil no caso de muitos usuários.
Se você não marcar estas opções, deve se certificar de que os pontos de montagem para a árvore de páginas e para a árvore de pastas de arquivos estão configuradas individualmente para cada usuário.',
		'fileoper_perms.description' => 'Selecione as permissões de operação de arquivos para o usuário.',
		'fileoper_perms.details' => 'Estes ajustes estão relacionados às funções localizadas no módulo Arquivo>Lista, assim como ao envio de arquivos em geral.',
		'starttime.description' => 'Digite a data a partir da qual a conta estará ativa.',
		'endtime.description' => 'Digite a data a partir da qual a conta estará inativa.',
		'lang.description' => 'Selecione o idioma <i>padrão</i>.',
		'lang.details' => 'Determina o idioma da interface de administração para o usuário. Todas as seções usuais de trabalho disponíveis para usuários normais estarão disponíveis na língua selecionada.
Usuários-Administradores, no entanto, notarão que as partes de uso exclusivo de \'Administradores\' estarão em inglês. Isso inclui todos os submódulos em "Ferramentas" e o módulo Internet>Modelos.

<b>Nota</b> este é apenas o idioma do modo padrão. Assim que o usuário se logar, o idioma deve ser modificado através do módulo Usuário>Configuração.',
		'userMods.description' => 'Selecione os módulos de Administração disponíveis para o usuário.',
		'userMods.details' => 'Determina quais \'itens de menu\' estarão disponíveis para o usuário.

Note que a mesma lista de módulos pode ser configurada para os grupos de usuários, e que estes módulos serão herdados pelo usuário em adição aos selecionados aqui. É altamente recomendado que você não atribua módulos aos usuários, e ao invés disso selecione os módulos nos grupos de usuários onde são membros. De qualquer modo, esta listagem permite uma grande forma de adicionar um único módulo a usuários específicos.',
		'TSconfig.description' => 'Digite TSconfig adicional para o usuário (avançado).',
		'TSconfig.details' => 'Este campo permite a você estender a configuração do usuário em vários detalhes. Um breve sumário das opções inclui uma configuração mais detalhada dos módulos de administração, ajuste de valores de campos de tabelas padrão específicos dos usuários, ajustes de opções do Editor Rich Text, etc. A lista deve crescer com o tempo e está fartamente documentada na documentação de administração, em particular \'admin_guide.pdf\' (veja o link abaixo).',
	),
	'et' => Array (
	),
	'ar' => Array (
	),
	'he' => Array (
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