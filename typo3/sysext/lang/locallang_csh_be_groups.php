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
		'.description' => 'Ceci est l\'interface BE d\'administration des groupes utilisateurs disponible pour les utilisateurs BE: ici sont déterminées les permissions pour les utilisateurs BE',
		'title.description' => 'Nom du groupe utilisateur Backend',
		'db_mountpoints.description' => 'Assigne le point de départ de l\'arborescence de pages de l\'utilisateur',
		'db_mountpoints.details' => 'L\'arborescence de pages vue dans les sous-modules web en naviguant doit avoir des points d\'entrée définis. Ici vous devriez insérer une ou plusieurs références à des pages qui représenteront de nouvelles pages racine dans l\'arborescence. Ceci est appelé un \'point de montage de base de données\' (DB mounts, faisant référence à la notion de point de montage Unix)
Les DB mounts peuvent hérités des utilisateurs qui sont membres de ce groupe. Ceci dépend de la façon dont l\'utilisateur est configuré, s\'il doit inclure les DB mounts spécifiés dans son (ses) groupes. Cependant il est recommandé d\'utiliser les groupes utilisateures comme ceci pour configurer les montages, surtout s\'ils doivent être partagés par un grand nombre d\'utilisateurs',
		'file_mountpoints.description' => 'Assigne des points de départ pour l\'arborescence de fichiers',
	),
	'es' => Array (
		'.description' => 'Este es el administrador de grupos de usuarios del backend disponible para los usuarios del Backend. Dichos grupos determinan los permisos para los usuarios del Backend.',
		'title.description' => 'Nombre del grupo de usuarios del Backend',
		'db_mountpoints.description' => 'Asigna puntos de entrada para el árbol de páginas de los usuarios',
		'db_mountpoints.details' => 'El árbol de páginas usado por todos mis submódulos Web para navegar debe tener algunos puntos de entrada definidos. Aquí deberías insertar una o más referencias a una página que representará una nueva página raíz para el árbol de páginas. Esto es conocido como un “Punto de montaje de base de datos”.
Los Puntos de Montaje de BBDD pueden ser heredados por los usuarios miembros de este grupo. Esto depende de si el usuario está configurado para incluir los puntos de montaje establecidos en los grupos de usuarios a los que pertenece. De cualquier manera, es recomendable utilizar grupos de usuarios para configurar dichos puntos de montaje, especialmente si deben ser compartidos por varios usuarios.',
		'file_mountpoints.description' => 'Asigna puntos de entrada para el árbol de carpetas de ficheros.',
		'file_mountpoints.details' => 'El árbol de carpetas de ficheros es utilizado por todos los submódulos de “Fichero” para navegar entre las carpetas de servidor web.
Como ocurre con los puntos de montaje de BBDD, los puntos de montaje de ficheros pueden ser heredados por los usuarios que son miembros de este grupo.',
		'pagetypes_select.description' => 'Selecciona qué "Tipos" de páginas pueden utilizar los miembros del grupo.',
		'pagetypes_select.details' => 'Esta opción limita el número de opciones válidas para el usuario cuando éste va a seleccionar un tipo de página.',
		'tables_modify.description' => 'Selecciona qué tablas pueden modificar los miembros.',
		'tables_modify.details' => 'Un parte importante al establecer permisos es definir qué tablas de la base de datos puede modificar un usuario.
Las tablas que se han seleccionado para modificación automáticamente son seleccionadas para visualización, por lo que no es necesario especificarlas también en el cuadro “Tablas (Visualización)”.

<strong>Nota:</strong> Esta lista se acumula con las que existen en otros grupos de los que es miembro el usuario.',
		'tables_select.description' => 'Seleciona qué tablas puede ver el usuario en las listas de registros (no es necesario especificar aquí las tablas para modificación).',
		'tables_select.details' => 'Esto determina qué tablas – además de las seleccionadas en el cuadro “Tablas (modificar)” - deben ser visibles y listadas por el usuario. Por tanto él no puede <em>editar</em> la tabla, sino tan sólo seleccionar los registros y ver el contenido.
Esta lista no es demasiado importante. Es una situación bastante extraña el que un usuario pueda ver unas tablas pero no modificarlas.',
		'non_exclude_fields.description' => 'Algunos campos de las tablas no están disponibles por defecto. Dichos campor pueden ser activados explícitamente para los miembros del grupo aquí.',
		'non_exclude_fields.details' => '“Campos permitidos” permite detallar los permisos asignados a las tablas. Por defecto todos esos campos no están disponibles a los usuarios y deben ser activados específicamente seleccionándolos aquí.
Una aplicación de esto es que las páginas están normalmente ocultas por defecto y el campo “Ocultar” no está disponible para los usuarios a no ser que se active explícitamente en esta lista. Así, el usuario puede crear una página nueva pero no la puede dejar visible. A no ser por supuesto que tenga seleccionado el campo “Página: Ocultar” en uno de los grupos de los que es miembro.
Por supuesto no tiene sentido añadir campos de tablas que no están en la lista de tablas con permiso para modificar.',
		'hidden.description' => 'Desactiva un grupo de usuarios.',
		'hidden.details' => 'Si desactivas un grupo de usuarios, todos los usuarios que sean miembros del grupo no heredarán ninguna de las propiedades que el grupo les ofecía.',
		'lockToDomain.description' => 'Introduce el nombre del equipo desde el cual el usuario está forzado a autentificarse.',
		'lockToDomain.details' => 'Un sistema TYPO3 puede tener múltiples dominios apuntando a él. Esta opción asegura que los usuarios sólo puedan entrar desde cierto equipo.',
		'groupMods.description' => 'Selecciona los módulos de backend disponibles para los miembros del grupo.',
		'groupMods.details' => 'Esto determina qué “elementos de menú” están disponibles para los miembros del grupo. 
Esta lista de módulos se suma a las de los otros grupos a los que pertenece el usuario, así como a los seleccionados en el mismo campo del propio usuario.',
		'inc_access_lists.description' => 'Selecciona que listas de acceso de tipo de página, tabla, módulo y campos permitidos están activados en este grupo.',
		'description.description' => 'Introduce una pequeña descripción del grupo de usuarios, para qué se utiliza y quienes deben ser los miembros. Esto es sólo para uso interno.',
		'TSconfig.description' => 'Configuración adicional mediante valores de estilo de TypoScript (Avanzado).',
		'TSconfig.syntax' => 'Estilo de TypoScript sin condiciones ni constantes.',
		'hide_in_lists.description' => 'Esta opcion previene al grupo de usuarios de aparecer en las listas donde son seleccionados los grupos de usuarios.',
		'hide_in_lists.details' => 'Esto afectará el listado de grupos de usuarios en en Centro de Tareas (partes de Tareas y Mensajes) y también en el módulo Web>Acceso.
Esta opción es extremadamente útil si dispones de grupos de usuarios generales que definen algunas propiedades globales de los que son miembros tus usuarios. Seguramente no quieras que todos esos usuarios “vean” a los otros ya que son miembros del mismo grupo, para por ejemplo enviar mensajes o tareas entre ellos. Y esto es lo que previene esta opción.',
		'subgroup.description' => 'Selecciona grupos de usuarios del backend que serán incluidos automáticamente para miembros de este grupo.',
		'subgroup.details' => 'Las propiedades o subgrupos son añadidos a las propiedades de estos grupos y básicamente serán añadidos a la lista de grupos miembros de cualquier usuario que sea miembro de este grupo.
Esta carácterística ofrece una buena manera de crear grupos de usuarios de “Supervisores”.',
	),
	'nl' => Array (
		'.description' => 'Dit zijn de backendbeheer-gebruikersgroepen die beschikbaar zijn voor de Backendgebruikers. Deze bepalen de permissies voor de Backendgebruikers.',
		'title.description' => 'Naam van de backendgebruikersgroep',
		'db_mountpoints.description' => 'Startpunten toekennen voor de gebruikers paginaboom.',
		'db_mountpoints.details' => 'De paginaboom, gebruikt om door alle web-submodules te navigeren, dient een aantal ingangspunten gedefiniëerd te hebben. Hier moeten een aantal referenties naar een pagina worden ingevoegd die een nieuwe rootpagina representeren voor de paginaboom. Dit word een \'Databasestartpunt\' genoemd.
Databasestartpunten kunnen worden ge-erfd door gebruikers die lid zijn van deze groep. De gebruiker dient dan wel zo geconfigureerd te zijn dat deze de startpunten bevat zoals bepaald in de gebruikersgroepen. Het wordt echter aanbevolen de backend gebruikersgroepen te gebruiken om de startpunten te configureren. Vooral als ze gedeeld worden door een groot aantal gebruikers.',
		'file_mountpoints.description' => 'Startpunten toekennen aan de mappenweergave.',
		'file_mountpoints.details' => 'Om de mappenweergave met alle sub-modules zichtbaar te maken moeten er startpunten ingegeven worden. Hier worden een of meer referenties aangegeven naar een pagina welke zichtbaar wordt als een beginpunt voor de mappenweergave. Dit wordt een "Database mount" genoemd. DB mounts worden door leden van een gebruikersgroep overgenomen, mits dit zo is ingesteld. Het wordt aangeraden om met gebruikersgroepen te werken, zeker als er veel gebruikers zijn.',
		'pagetypes_select.description' => 'Selecteer welke \'Type\' pagina\'s de leden van de groep mogen bepalen.',
		'pagetypes_select.details' => 'Deze optie beperkt het aantal geldige keuzes voor de gebruiker als deze een paginatype wil selecteren.',
		'tables_modify.description' => 'Selecteer welke tabellen door de leden van de groep aangepast mogen worden.',
		'tables_modify.details' => 'Een zeer belangerijke optie is welke database velden (tabellen) de gebruiker mag aanpassen. De velden die hier worden geselecteerd worden automatisch toegevoegd aan de lijst van velden (tabellen).

<strong>p.s.</strong> Bedenk wel dat de geselecteerde velden ook worden toegevoegd aan eventuele subgroepen van deze beheergroep.',
		'tables_select.description' => 'Om de boomstructuur met alle sub-modules zichtbaar te maken moeten er startpunten ingegeven worden. Hier worden een of meer referenties aangegeven naar een pagina welke zichtbaar wordt als een beginpunt voor de boomstructuur. Dit wordt een "Database mount" genoemd. DB mounts worden door leden van een gebruikersgroep overgenomen, mits dit zo is ingesteld. Het wordt aangeraden om met gebruikersgroepen te werken, zeker als er veel gebruikers zijn.',
		'tables_select.details' => 'Deze optie bepaalt welke velden - als toevoeging aan degene die reeds zijn geselecteerd (Tables (modify) in aan te passen velden - in de lijst van bestanden komen die bekeken kunnen worden.
Deze lijst is niet zo belangrijk. Het komt zelden voor dat een gebruiker wel tabellen kan selecteren, maar niet kan aanpassen.',
		'non_exclude_fields.description' => 'Bepaalde velden zijn niet standaard beschikbaar. Deze velden kunnen hier expliciet, voor groepsleden, beschikbaar gemaakt worden.',
		'non_exclude_fields.details' => '"Toegestane uitgesloten velden" geven de mogelijkheid om tot in detail permissies aan velden toe te kennen. Standaard zijn deze velden niet beschikbaar voor gebruikers, maar beschikbaar gemaakt worden door ze hier te selecteren. 
Een toepassing van dit is dat pagina\'s normaal gesproken verborgen worden en dat de verberg optie niet beschikbaar is voor de gebruiker, tenzij hier geselecteerd. Dit betekend dat de gebruiker wel een nieuwe pagina kan creeren, maar niet verbergen, tenzij deze gebruiker via de gebruikersgroep de permissie heeft om de pagina te verbergen. 
Het heeft geen zin om hier velden te selecteren als men deze niet ook heeft geselecteerd in de tabel voor aan te passen velden.',
		'hidden.description' => 'Verbergt een gebruikersgroep',
		'hidden.details' => 'Ten gevolge van het verbergen (uitschakelen) van een gebruikersgroep, zullen de leden van deze groep niet de instellingen van deze groep overerven.',
		'lockToDomain.description' => 'Geef hier op van welk domein de gebruiker MOET inloggen.',
		'lockToDomain.details' => 'Sommige TYPO3 installatie\'s hebben meerdere domeinen. Deze optie zorgt ervoor dat gebruikers alleen van een bepaald domein kunnen inloggen.',
		'groupMods.description' => 'Selecteer beschikbare backendmodules voor de groepsleden.',
		'groupMods.details' => 'Dit bepaald welke menu items beschikbaar zijn voor groepsleden. 
De lijst van menu items worden automatisch aan de leden van de groep toegevoegt.',
		'inc_access_lists.description' => 'Selecteer hier welke pagina types, tabellen, modules zichtbaar zijn voor deze groep',
		'description.description' => 'Geef een korte beschrijving van de gebruikers groep, wat voor soort groep is het en wie er leden zouden moeten zijn. Dit is alleen voor intern gebruik.',
		'TSconfig.description' => 'Additionele configuratie d.m.v typoscript.',
		'TSconfig.syntax' => 'Typoscript zonder condities en constantes',
		'hide_in_lists.description' => 'Deze optie voorkomt dat gebruikersgroepen verschijnen in lijsten, waar men gebruikersgroepen kan selecteren.',
		'hide_in_lists.details' => 'Dit heeft gevolgen voor de lijst van gebruikersgroepen in het taakcentrum, bij to-do en de berichten, en in de Web>toegang module. 
Deze optie is erg handig als er algemene gebruikersgroepen zijn met globale instellingen waar alle gebruikers lid van zijn. Dit voorkomt dat men elkaar kan waarnemen als lid van een groep en men elkaar berichten en to-do kan sturen.',
		'subgroup.description' => 'Selecteer de beheergroepen die als leden tot deze groep behoren.',
		'subgroup.details' => 'De eigenschappen van de subgroepen worden toegevoegd aan de eigenschappen van deze groep, waardoor elk lid van deze groep alle eigenschappen van de groep en de subgroep heeft.
Deze feature is ideaal om een \'Supervisor\' gebruikersgroep aan te maken.',
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
		'db_mountpoints.details' => 'Sivujen puurakenteeseen, jota WEB-aliohjelmat käyttävät, tulee määritellä joitakin liityntä pisteitä. Tässä lisäät yhden tai useamman referenssin sivulle jotka edustavat sivuston rakenteen uutta juurta (alkupistettä).
Tätä kutsutaan \'Tietokannan asetukseksi" ("Database mount").
Tietokanta asetukset periytyvät käyttäjälle siltä käyttäjäryhmältä jonka jäsen hän on. Tämä ei riipu onko käyttäjälle konfiguroitu käyttäjäryhmän sisältämät asetukset. On kuitenkin suositeltavaa käyttää tämänkaltaisia käyttäjäryhmiä asetusten konfiguroimiseksi. Erityisesti jos näitä jaetaan useiden käyttäjien kesken.',
		'file_mountpoints.description' => 'Määrittele aloituskohta tiedostohakemistojen rakenteelle.',
		'file_mountpoints.details' => 'Tiedostohakemistoa käyttävät kaikki Tiedosto-aliohjelmat siirtyessään palvelimen tiedostohakemistoissa.
Huomaa, että kuten \'DB asetuksissa\' tiedostohakemistojen käyttöoikeudet käyttäjille periytyvät niistä käyttäjäryhmistä joiden jäseniä käyttäjät ovat.',
		'pagetypes_select.description' => 'Valitse mihin sivutyyppeihin käyttäjillä on oikeus.',
		'pagetypes_select.details' => 'Tämä valinta rajoittaa valintaehtojen lukumäärää käyttäjille kun he valitsevat sivun tyyppiä.',
		'tables_modify.description' => 'Valitse mihin tauluihin käyttäjillä on muutosoikeus.',
		'tables_modify.details' => 'Asetusten asettamisen tärkeä osa on kuinka määritellään käyttäjälle sallittujen tietokannan taulujen muutosoikeus.
Taulujen muutosoikeus asettaa oikeudet myös taulun valinnann eikä Sinun tarvitse asettaa tauluja tähän "Taulut (listaus)" laatikkoon.

<strong>Huomaa</strong> että tämä lista lisää valitut tiedot myös käyttäjän muihin käyttäjäryhmiin.',
		'tables_select.description' => 'Valitse mitkä taulut käyttäjät voivat nähdä tietolistoilla (\'muokkaa\' tauluja ei tarvitse lisätä uudelleen tässä!).',
		'tables_select.details' => 'Tämä määrittelee mitä tauluja - niiden lisäksi jotka olet valinnut "Taulut (muokkaa) laatikossa" - käyttäjä näkee. Käyttäjä ei siisvoi <em>muokata</em> taulua - ainoastaan valita tietoja ja katsoa sisältöä.
Tämä lista ei siis ole kovin tärkeä. On perin harvinaista että käyttäjä voi valita taulun muttei muuttaa sen sisältöä.',
		'non_exclude_fields.description' => 'Eräät taulujen tiedot eivät ole oletusarvoisesti käytettävissä. Nuo tiedot voidaan eksplisiittisesti saattaa voimaan ryhmän jäsenille tässä.',
		'non_exclude_fields.details' => '"Sallitut suljetut tiedot" antavat Sinullemahdollisuuden määritellä yksityiskohtasia oikeuksia tauluille. Oletusarvoisesti eivätmitkään näistä tiedoista ole sallittuja käyttäjille vaan niille on erityisesti annettava oikeudet valitsemalla tästä.
Esimerkki, sivut ovat oletuksena piilotettuja ja piilotettu kenttä ei ole käyttäjän saavutettavissa ilman että hänelle on annettu oikeuksia tällä "Sallitut suljetut tiedot" listalla. Joten käyttäjä voi luoda uuden sivun muttei voi piilottaa sivua. Tämä tietenkin jos käyttäjälle ei ole annettu oikeuksia "Sivu:Piilotettu" ("Page:Hidden") suljettu tietoon jonkin hänen käyttäjäryhmänsä kautta.
Ei ole kuitenkaan mieltää antaa käyttäjälle oikeuksia sellaisiin tietoihin eri tauluista johon hänellä ei ole muokkausoikeuksia.',
		'hidden.description' => 'Poista käyttäjäryhmän voimassaolo.',
		'hidden.details' => 'Jos poistat voimassaolon käyttäjäryhmältä',
		'lockToDomain.description' => 'Anna domainin nimi mihin käyttäjä on pakoitettu sisäänkirjoittautumaan.',
		'lockToDomain.details' => 'Typo3 järjestelmässä voi olla useita domaineja. Siksi tämä vaihtoehto varmistaa että käyttäjät pääsevät vain heille tarkoittuihin domaineihin.',
		'groupMods.description' => 'Valitse käytettävissä olevat tausta-aliohjelmat ryhmän käyttäjille.',
		'groupMods.details' => 'Tämä määrittelee mitkä \'valikko osat\' ovat käytettävissä ryhmälle.
Tämä aliohjelma lista lisätään kaikkiin muihinkin käyttäjän ryhmiin kuin myös vastaavaan tietoon käyttäjälle itselleen.',
		'inc_access_lists.description' => 'Valitse mitkä Sivu tyypit (Page type), Taulut (Table), Aliohjelmat (Module) ja Sallitut estetyt kentät (Allowed excludefields) ovat sallittuja tälle käyttäjäryhmälle.',
		'description.description' => 'Anna lyhyt kuvaus käyttäjäryhmästä, miksi se on olemassa ja minkälaisia käyttäjiä siihen kuuluu. Tämä tieto on vain sisäiseen käyttöön.',
		'TSconfig.description' => 'Lisäasetukset käyttäen TypoScript-tyylitietoja (Laajennettu eli Advanced).',
		'TSconfig.syntax' => 'TypoScript-tyyli ilman ehtoja (conditions) ja vakioita (constants).',
		'hide_in_lists.description' => 'Tämä valinta estää käyttäjäryhmää näkymästä listoilla kun käyttäjäryhmiä valitaan.',
		'hide_in_lists.details' => 'Tämä vaikuttaa käyttäjäryhmälistalle Toimanta Keskuksen To-Do ja Viesti osiin kuin myös Web>Oikeudet aliohjelmaan.
Vaihtoehto on erityisen hyödyllinen jos Sinulla on yleisiä käyttäjäryhmiä jotka tarvitsevat ylesiä ominaisuuksia. 
Jos et esimerkiksi halua tämän ryhmän lähettävän viestejä toisilleen tai et halua heidän näkevän toistensa To-Do listaa on tämä vaihtoehto mitä tarvitset.',
		'subgroup.description' => 'Valitse taustakäyttäjäryhmä, joka automaattisesti lisätään tämän ryhmän jäsenille.',
		'subgroup.details' => 'Ominaisuudet ja aliryhmät lisätään tämän ryhmän ominaisuuksiin. Periaatteessa ne yksinkertaisesti lisätään kaikkien kaikille ryhmän jäsenille.
Tämä mahdollistaa helpon tavan luoda \'Supervisor\' käyttäjäryhmän.',
	),
	'tr' => Array (
	),
	'se' => Array (
		'.description' => 'Dethär är backend-administrationens användargrupper som BE-användare kan använda. Dessa begränsar BE-användarnas rättigheter.',
		'title.description' => 'Backend-användargruppens namn',
		'db_mountpoints.description' => 'Bestäm startpunkter för användarnas sidträd.',
		'db_mountpoints.details' => 'The page tree used my all Web-submodules to navigate must have some points-of-entry defined. Here you should insert one or more references to a page which will represent a new root page for the page tree. This is called a \'Database mount\'.
DB mounts may be inherited by the users which are members of this group. This does depend on whether the user is configured to include the mounts set in the member groups. However it\'s recommended to use backend user groups like this to configure mounts. Especially if the need to be shared amoung many users.',
		'file_mountpoints.description' => 'Bestäm startpunkter för filhanteringsträdet.',
		'file_mountpoints.details' => 'The file folder tree is used by all File-submodules to navigate between the file folders on the webserver.
Notice as with \'DB mounts\' the file folder mounts may be inherited by the users which are members of this group.',
		'pagetypes_select.description' => 'Välj vilka "Typer" av sidor som medlemmarna kan använda',
		'pagetypes_select.details' => 'Denna option begränsar vilka sidor en användare kan ta i bruk.',
		'tables_modify.description' => 'Välj vilka tabeller en användare kan ändra.',
		'tables_modify.details' => 'An important part of setting permissions is to define which database tables a user is allowed to modify. 
Tables allowed for modification is automatically also allowed for selection and thus you don\'t need to set tables entered here in the "Tables (listing)" box.

<strong>Notice</strong> that this list adds to the fields selected in other member groups of a user.',
		'tables_select.description' => 'Välj vilka tabeller användarna kan se i listan (\'ändra\' tabellerna behöver inte fyllas i här också!).',
		'tables_select.details' => 'This determines which tables - in addition to those selected in the "Tables (modify)" box - may be viewed and listed for the user. He is thus not able to <em>edit</em> the table - only select the records and view the content.
This list is not so very important. It\'s a pretty rare situation that a user may select tables but not modify them.',
		'non_exclude_fields.description' => 'Vissa tabellfält är som standard spärrade. Dessa fält kan här öppnas för gruppens medlemmar.',
		'non_exclude_fields.details' => '"Allowed excludefields" allows you to detail the permissions granted to tables. By default all these fields are not available to users but must be specifically enabled by being selected here.
One application of this is that pages are usually hidden by default and that the hidden field is not available for a user unless he has been granted access by this list of "Allowed excludefields". So the user may create a new page, but cannot un-hide the page. Unless of course he has been assigned the "Page: Hidden" exclude field through one of his member groups.
Of course it does not make any sense to add fields from tables which are not included in the list of table allowed to be modified.',
		'hidden.description' => 'Spärra en användargrupp.',
		'hidden.details' => 'Om du spärrar en användargrupp kommer  de egenskaper, som du ställt in för alla medlemmar i gruppen att begränsas.',
		'lockToDomain.description' => 'Fyll i från vilken värddator användaren måste logga in.',
		'lockToDomain.details' => 'Ett TYPO3-system kan ha många domain under sig. Denna option säkerställer att användarna kan logga in endast från en viss värddator.',
		'groupMods.description' => 'Välj tillbudsstående moduler för gruppens medlemmar',
		'groupMods.details' => 'This determines which \'menu items\' are available for the group members.
This list of modules is added to any modules selected in other member groups of a user as well as the corresponding field of the user himself.',
		'inc_access_lists.description' => 'Välj om denna grupp skall kunna använda Sidtyp, Tabell, Modul och Godkända fält.',
		'description.description' => 'Fyll i en kort förklaring för användargruppen, gruppens uppgift och vilka medlemmarna är. Detta är endast för internt bruk.',
		'TSconfig.description' => 'Tilläggskonfigurering med TypoScript stilvärden (avancerad).',
		'TSconfig.syntax' => 'TypoScript stilar utan villkor och konstanter',
		'hide_in_lists.description' => 'Denna option förebygger att en användargrupp inte visas i listorna där användargrupper väljs.',
		'hide_in_lists.details' => 'This will affect the list of user groups in the Task Center To-Do and Messages parts as well as the Web>Access module.
The option is extremely useful if you have general user groups defining some global properties which all your users are members of. Then you would probably not like all those users to \'see\' each other through the membership of this group, for instance sending messages or To-Dos to each other. And this is what is option will prevent.',
		'subgroup.description' => 'Välj de backend-användargrupper som automatiskt ges åt medlemmar i denna grupp.',
		'subgroup.details' => 'The properties or subgroups are added to the properties of this groups and basically they will simply be added to the list of member groups of any user which is a member of this group.
This feature provides a great way to create \'Supervisor\' user groups.',
	),
	'pt' => Array (
	),
	'ru' => Array (
		'.description' => 'Ãğóïïû âíóòğåííèõ ïîëüçîâàòåëåé, äîñòóïíûå äëÿ ïîëüçîâàòåëåé áıêåíäà. ×ëåíñòâî â ãğóïïàõ îïğåäåëÿåò ïğàâà ïîëüçîâàòåëåé áıêåíäà.',
		'title.description' => 'Íàçâàíèå ãğóïïû ïîëüçâàòåëåé',
		'db_mountpoints.description' => 'Âûáğàòü èñõîäíóş òî÷êó äëÿ äåğåâà ñòğàíèö, âèäèìîãî ïîëüçîâàòåëÿìè.',
	),
	'ro' => Array (
	),
	'ch' => Array (
		'.description' => 'ÕâÊÇºó¶Ë¹ÜÀíÓÃ»§×é,¶Ôºó¶ËÓÃ»§ÓĞĞ§.ËüÃÇÎªºó¶ËÓÃ»§È·¶¨È¨ÏŞ.',
		'title.description' => 'ºó¶ËÓÃ»§×éµÄÃû³Æ',
		'db_mountpoints.description' => 'Î´ÓÃ»§Ò³ÃæÊ÷·ÖÅä¿ªÊ¼µã.',
		'db_mountpoints.details' => 'Ò³ÃæÊ÷Ê¹ÓÃËùÓĞµÄÕ¾µã×ÓÄ£¿é½øĞĞ¶¨Î»,
±ØĞëÒÑ¶¨ÒåÁËÒ»Ğ©½øÈëµã.
´Ë´¦ÄúÓ¦¸ÃÎªÒ»¸öÒ³Ãæ²åÈëÒ»¸öºÍ¶à¸ö²Î¿¼,
¸ÃÒ³Ãæ½«ÎªÒ³ÃæÊ÷ÏÔÊ¾Ò»¸öĞÂµÄ¸ùÒ³Ãæ.Õâ±»
³ÆÎª\'Êı¾İ¿âmount\'.
DB mountsÊôĞÔ¿ÉÒÔ´Ó×÷Îª×é³ÉÔ±µÄÓÃ»§´¦±»¼Ì³Ğ.
ÕâÈ¡¾öÓÚÔÚ³ÉÔ±×éÖĞÓÃ»§ÊÇ·ñ±»ÅäÖÃÎª°üÀ¨mounts set.
ÎŞÂÛÈçºÎ½¨ÒéÊ¹ÓÃºó¶ËÓÃ»§×éÀ´ÅäÖÃmounts.ÌØ±ğÊÇÔÚĞí¶à
ÓÃ»§Ö®¼äÓĞ¹²ÏíĞèÇóÊ±.',
		'file_mountpoints.description' => 'ÎªÎÄ¼şÄ¿Â¼Ê÷·ÖÅä¿ªÊ¼µã.',
		'file_mountpoints.details' => 'ËùÓĞµÄÎÄ¼ş×ÓÄ£¿éÓÃÎÄ¼şÄ¿Â¼Ê÷ÔÚÕ¾µã·şÎñÆ÷ÉÏµÄÎÄ¼şÄ¿Â¼Ö®¼ä½øĞĞ¶¨Î».
´øÓĞ\'DB mounts\'µÄÎÄ¼şÄ¿Â¼mounts¿ÉÄÜÊÇ´Ó×÷Îª×é³ÉÔ±µÄÓÃ»§ÄÇÀï¼Ì³Ğ
ÁË¸ÃÊôĞÔ.',
		'pagetypes_select.description' => 'Ñ¡Ôñ³ÉÔ±¿ÉÄÜÉèÖÃµÄÒ³ÃæÊôĞÔ.',
		'pagetypes_select.details' => '¶Ô½«Ñ¡ÔñÒ³ÃæÀàĞÍµÄÓÃ»§´ËÑ¡ÏîÏŞÖÆÆäÓĞĞ§Ñ¡ÔñµÄÊıÁ¿.',
		'tables_modify.description' => 'Ñ¡Ôñ³ÉÔ±¿ÉÄÜĞŞ¸ÄµÄ±í¸ñ.',
		'tables_modify.details' => 'ÉèÖÃÈ¨ÏŞµÄÒ»¸öÖØÒª²¿·ÖÊÇ¶¨ÒåÄÄĞ©Êı¾İ¿â±í¸ñÔÊĞíÓÃ»§ĞŞ¸Ä.
ÔÊĞíĞŞ¸ÄµÄ±í¸ñÒ²Í¬Ñù×Ô¶¯ÔÊĞíÑ¡Ôñ,Òò´ËÄú²»ĞèÒªÔÚ´Ë´¦"±í¸ñ(ÁĞ±í)"
¿òÖĞÊäÈë±í¸ñ.

<strong>×¢Òâ</strong>¸ÃÁĞ±íÌí¼Óµ½ÔÚÓÃ»§µÄÁíÒ»¸ö³ÉÔ±×éÖĞËùÑ¡Ôñ
µÄÓòÄÚ.',
		'tables_select.description' => 'Ñ¡Ôñ³ÉÔ±ÔÚ¼ÇÂ¼ÁĞ±íÖĞ¿ÉÄÜÒª²é¿´µÄ±í¸ñ(\'ĞŞ¸Ä\'±í¸ñ²»ĞèÒªÔÚ´Ë´¦ÖØĞÂÊäÈë!)',
		'tables_select.details' => '´ËÏî¾ö¶¨ÄÄĞ©±í¸ñ - ³ıÁËÔÚ"±í¸ñ(ĞŞ¸Ä)"¿òÖĞËùÑ¡ÔñµÄ±í¸ñÖ®Íâ - ¿ÉÄÜ±»
ÓÃ»§²é¿´ºÍÁĞ³öµÄ±í¸ñ. ÕâÑùËû²»ÄÜ<em>±à¼­</em>±í¸ñ - Ö»ÄÜÑ¡Ôñ
¼ÇÂ¼ºÍ²é¿´ÄÚÈİ.
´ËÁĞ±í²»ÊÇ·Ç³£ÖØÒª.Ò»°ã²»Ì«»á³öÏÖÓÃ»§Ñ¡Ôñ±í¸ñµ«²»ĞŞ¸ÄËüÃÇµÄÇé¿ö.',
		'non_exclude_fields.description' => 'Ä³Ğ©±í¸ñÓòÔÚÈ±Ê¡Çé¿öÏÂÊÇÎŞĞ§µÄ.ÄÇĞ©ÓòÔÚ´Ë¶ÔÓÚ×é³ÉÔ±¿ÉÓÃ.',
		'non_exclude_fields.details' => '"ÔÊĞíexcludefields"ÔÊĞíÄúÎª±í¸ñÏêÏ¸Ö¸Ã÷×¼ĞíµÄÈ¨ÏŞ.È±Ê¡Çé¿öÏÂËùÓĞµÄÕâĞ©
Óò¶ÔÓÚÓÃ»§ÊÇ²»¿ÉÓÃµÄ,µ«¿ÉÒÔÍ¨¹ıÔÚ´Ë´¦Ñ¡ÔñÀ´Ö¸¶¨Îª¿ÉÓÃ.
Ò»¸öÓ¦ÓÃÊµÀıÎªÍ¨³£Ò³ÃæÔÚÈ±Ê¡ÏÂÊÇ±»Òş²ØµÄ,²¢ÇÒ±»Òş²ØµÄÒ³Ãæ¶ÔÓÚÓÃ»§
ÊÇ²»¿ÉÓÃµÄ,³ı·ÇËû±»´ËÁĞ±í"ÔÊĞíexcludefields"×¼Ğí·ÃÎÊ. Òò´ËÓÃ»§¿ÉÒÔ´´½¨Ò»¸ö
ĞÂµÄÒ³Ãæ,µ«²»ÄÜ²»Òş²ØÒ³Ãæ.µ±È»³ı·ÇËûÒÑ¾­Í¨¹ıËûµÄ³ÉÔ±×éÖĞµÄÒ»¸ö×é±»
·ÖÅäÁË"Ò³Ãæ:Òş²Ø"²»°üÀ¨ÓòÊôĞÔ.
µ±È»,´ÓÄÇĞ©²»°üÀ¨ÔÚÔÊĞí±»ĞŞ¸ÄµÄ±í¸ñÁĞ±íÖĞµÄ±í¸ñÌí¼ÓÓòÒ²Ã»ÓĞÈÎºÎÒâÒå.',
		'hidden.description' => '½ûÖ¹Ò»¸öÓÃ»§×é.',
		'hidden.details' => 'Èç¹ûÄú½ûÖ¹ÁËÒ»¸öÓÃ»§×é,ÄÇÃ´¸Ã×éµÄËùÓĞÓÃ»§½«²»ÄÜ¼Ì³Ğ¸Ã×éÊÚÓèËûÃÇµÄÈÎºÎÊôĞÔ.',
		'lockToDomain.description' => 'ÊäÈëÓÃ»§±»ÒªÇóµÇÂ¼µÄÖ÷»úÃû³Æ.',
		'lockToDomain.details' => 'Ò»¸öTYPO3ÏµÍ³¿ÉÄÜÓĞ¶à¸öÓòÖ¸ÏòËü.Òò´Ë¸ÃÑ¡Ïî±£Ö¤ÓÃ»§Ö»¿ÉÒÔ´ÓÄ³Ò»¸öÖ÷»úÃû³ÆµÇÂ¼.',
		'groupMods.description' => 'Îª×é³ÉÔ±Ñ¡ÔñÓĞĞ§µÄºó¶ËÄ£¿é.',
		'groupMods.details' => '´ËÏî¾ö¶¨ÄÄĞ©\'²Ëµ¥Ïî\'¶ÔÓÚ×é³ÉÔ±¿ÉÓÃ.
´ËÄ£¿éÁĞ±í±»Ìí¼Óµ½ÈÎºÎÒ»¸öÓÃ»§µÄÆäËü³ÉÔ±×éÖĞËùÑ¡µÄÄ£¿éÒÔ¼°ÓÃ»§×Ô¼º
Ïà¹ØµÄÓò.',
		'inc_access_lists.description' => 'Ñ¡ÔñÒ³ÃæÀàĞÍ,±í¸ñ,Ä£¿éºÍÔÊĞíexcludefields·ÃÎÊÁĞ±í¶ÔÓÚ´Ë×éÊÇ·ñ¿ÉÓÃ.',
		'description.description' => 'ÊäÈëÒ»¸öÓÃ»§×éµÄ¼ò¶ÌÃèÊö,´´½¨Ä¿µÄºÍ×é³ÉÔ±. ÕâÖ»¹©ÄÚ²¿Ê¹ÓÃ.',
		'TSconfig.description' => 'Í¨¹ıTypoScriptÑùÊ½Öµ(¸ß¼¶)µÄ¸½¼ÓÅäÖÃ.',
		'TSconfig.syntax' => 'TypoScriptÑùÊ½²»´øÌõ¼şºÍ³£Á¿.',
		'hide_in_lists.description' => '´ËÑ¡Ïî½«·ÀÖ¹ÓÃ»§×éÏÔÊ¾ÔÚ±»Ñ¡ÖĞµÄÓÃ»§×éÁĞ±íÖĞ.',
		'hide_in_lists.details' => 'Õâ½«Ó°Ïìµ½To-DoÈÎÎñÖĞĞÄÄÚµÄÓÃ»§×éÁĞ±íºÍÏûÏ¢²¿·ÖÒÔ¼°Õ¾µã>·ÃÎÊÄ£¿é.
Èç¹ûÄúÓĞ¶¨ÒåÁËÒ»Ğ©È«¾ÖÊôĞÔµÄÒ»°ãÓÃ»§×é, ËùÓĞÄúµÄÓÃ»§¶¼ÊÇ³ÉÔ±, ¸ÃÑ¡
Ïî·Ç³£ÓĞÓÃ.È»ºóÄú²»Ï£ÍûËùÓĞµÄÄÇĞ©ÓÃ»§Í¨¹ı¸Ã×éµÄ³ÉÔ±¹ØÏµ\'¿´µ½\'ÆäËü
³ÉÔ±,ÀıÈç·¢ËÍÏûÏ¢»òÈÎÎñ¸øÆäËüÈË.Õâ¾ÍÊÇ´ËÑ¡ÏîËùÒª·ÀÖ¹µÄ.',
		'subgroup.description' => 'Ñ¡Ôñ±»×Ô¶¯°üÀ¨ÔÚ¸Ã×é³ÉÔ±ÖĞµÄºó¶ËÓÃ»§×é.',
		'subgroup.details' => 'ÊôĞÔºÍ×Ó×é±»Ìí¼Óµ½¸Ã×éµÄÊôĞÔÖĞ,²¢ÇÒ»ù±¾ÉÏËûÃÇ½«±»ÍêÈ«µÄÌí¼Óµ½×÷Îª
¸Ã×é³ÉÔ±µÄÈÎºÎÓÃ»§µÄ³ÉÔ±×éÁĞ±íÖĞ.
´ËÌØĞÔÌá¹©ÁËÒ»¸öºÜºÃµÄ·½·¨À´´´½¨\'¹ÜÀíÔ±\'ÓÃ»§×é.',
	),
	'sk' => Array (
	),
	'lt' => Array (
	),
	'is' => Array (
	),
	'hr' => Array (
		'.description' => 'Ovo je pozadinska administracija korisnièkih grupa dostupna pozadinskim korisnicima. Ovdje se odreğuju dozvole za pozadinske korisnike.',
		'title.description' => 'Naziv grupe pozadinskih korisnika',
		'db_mountpoints.description' => 'Pridrui poèetnu toèku stabla stranica korisnika.',
		'db_mountpoints.details' => 'Stablo stranica koje koriste svi podmoduli unutar Web navigacije mora imati definiranu toèku ulaska. Ovdje moete unjeti jednu ili više referenci na stranicu koja æe predstvljati novu poèetnu stranicu stabla. To se naziva \'Database mount\' (DB poveznica).
DB poveznica moe biti naslijeğena od korisnika koji je èlan grupe. Ovo zavisi od toga da li je korisnik konfiguriran tako da ukljuèuje svoje poveznice u grupu èiji je èlan. Preporuèljivo je koristiti pozadinske korisnièke grupe sliène ovoj za konfiguraciu poveznica, posebno ako je trebate dijeliti izmeğu mnogo korisnika.',
		'file_mountpoints.description' => 'Pridrui poèetnu toèku stabla pretinaca.',
		'file_mountpoints.details' => 'Stablo pretinaca koristi se od svih podmodula unutar menija \'Datoteka\' radi navigacije izmeğu pretinaca web sjedišta. Napomenimo da slièno kao i kod \'DB povezince\' (DB mount) povezinca pretinaca moe biti naslijeğena od korsinika koji je èlan grupe.',
		'pagetypes_select.description' => 'Selektirajte koji tip stranice èlanovi grupe mogu postaviti.',
		'pagetypes_select.details' => 'Ova opcija ogranièava broj izbora koji korisnik moe odabrati prilikom selektiranja tipa stranice.',
		'tables_modify.description' => 'Selektirajte koje tablice èlanovi grupe mogu modificirati.',
		'tables_modify.details' => 'Vaan dio postavljanja dozvola predstavlja definiranja tablica baze koje korsinici mgu mijenjati. Tablice nad kojima je omoguæeno mijenjanje takoğer je automatski omoguæena i selekcija i tako da nemate potrebu za postavljanjem tablica unutar "Tables (popis)" okvira.

<strong>Napomena:</strong>',
		'tables_select.description' => 'Selektirajte koje tablice èlanovi grupe mogu vidjeti u popisu (tablice nad kojima je omoguæeno modificiranje ne moraju ponovo biti unešene ovdje).',
		'tables_select.details' => 'Ovdje definirate koje tablice - u dodatku onih selektiranih u okviru "Tables (modify)" - mogu biti dodane na popis tablica koje korisnik moe pregledavati. Korisnik nije u moguænosti <em>editirati</em> tablice veæ samo selektirati zapis i pregledati njegov sadraj.
Ovaj popis nije pretjerano vaan. Prilièno su rijetke situacije u kojima korisnik moe selektirati tablice ali ih ne moe i mijenjati.',
		'non_exclude_fields.description' => 'Odreğena polja tablice poèetno nisu dostupna. Ta polja ovdje trebaju biti eksplicitno omoguæena èlanovima grupe.',
		'non_exclude_fields.details' => '"Allowed excludefields" omoguæavaju detaljnije dozvole nad dostupnim tablicama. Poèetno sva ova polja nisu dostupna korisnicima veæ moraju ovdje biti selektirana.
Jedana primjena ovog svojstva su stranice koje su obiæno poèetno definirane kao skrivene i polje koje sadri to svojstvo nije dostupno korisniku dokle god mu to pravo nije dano pomoæu "Allowed excludefields". Dakloe korisnik moe kreirati novu stranicu ali æe ona ostati skrivena dokle god on ne bude pridruen iskljuèenom polju "Page: Hidden" putm èlanstva u grupi.
Naravno nema nikakvog smisla dodavati polja tablica koje nisu ukljuèene  na popis tablica koje grupa moe mijenjati.',
		'hidden.description' => 'Onemoguæava grupu korisnika',
		'hidden.details' => 'Ukoliko onemoguæite grupu korisnika niti jedan èlan grupe neæe naslijediti svojstva ove grupe.',
		'lockToDomain.description' => 'Unesite naziv posluitelja (domene) putem kojega se prisiljava korisnika za prijavu.',
		'lockToDomain.details' => 'TYPO3 sustav omoguæava opsluivanje više domena. Ovom opcijom osiguravate da se korisnik moe prijaviti na sustav samo putem zadane domene (naziva posluitelja - URLa).',
		'groupMods.description' => 'Selektirajte dostupne pozadinske module èlanovima grupe.',
		'groupMods.details' => 'Ova opcija odreğuje koji sadraj menija æe biti dostupan èlanovima grupe.
Ova lista modula biti æe dodana listi modula koje korisnik dobiva kao èlan drugih grupa kao i odgovarajuèim postavkama samog korisnika.',
		'inc_access_lists.description' => 'Odaberite jesu li tip stranice, tablica, modul i "allowed excludefield" pristupne liste omoguæene za ovu grupu.',
		'description.description' => 'Unesite kratak opis korisnièke grupe, koja joj je namjena i tko moe postati njezin èlan. Ovo je samo za internu upotrebu.',
		'TSconfig.description' => 'Dodatna konfiguracija kroz unos vrijednosti u TypoScript stilu (napredna opcija).',
		'TSconfig.syntax' => 'TypoScript stil bez uvjeta i konstanti.',
		'hide_in_lists.description' => 'Ova opcija omoguæava skrivanje korisnièke grupe u popisu grupa.',
		'hide_in_lists.details' => 'Ova opcija ima efekt na korisnièke grupe unutar "Task Center To-Do" i "Messagess" dijela kao i Web>Pristup modula.
Ova opcija je izrazito korisna kada imate opæenite korisnièke grupe koje definiraju neka globalna svojstva èiji su èlanovi svi korisnici. Tada vjerojatno neæete htjeti da svi korisnici vide ostale kroz èlanstvo u ovoj grupi, tako da npr. ne mogu slati poruke ili dodjeljivati zadatke jedni drugima.',
		'subgroup.description' => 'Odaberite pozadinsku grupu korisnika u koja æe automatski biti dodani èlanovi ove grupe.',
		'subgroup.details' => 'Osobine ili podgrupe se dodaju osobinama ovih grupa i u osnovi one æe jednostavno biti dodane popisu grupa èlanica bilo kojeg korisnika koji je pripadnik ovih grupa.
Ova moguænost prua sjajan naèin da se kreiraju "nadzorne" korisnièke grupe.',
	),
	'hu' => Array (
		'.description' => 'Ez a Backend felhasználók által elérhetõ Backend adminisztrációs csoport. Jogosultságokat ad meg a Backend felhasználók részére.',
		'title.description' => 'Backend felhasználócsoport neve',
		'db_mountpoints.description' => 'A felhasználói oldalrendszerhez rendel kiinduló pontot.',
		'db_mountpoints.details' => 'Az összes, navigálásra használt web-almodul
oldalrendszerének rendelkeznie kell egy megadott belépési
ponttal. Itt egy vagy több hivatkozást kell beszúrni egy
oldalhoz, amely az oldalcsoporthoz új kiinduló oldalt ad
meg. Ezt hívják adatbázis csatolásnak. Az adatbázis
csatolások öröklõdhetnek a csoporthoz tartozó
felhasználóktól. Ez függ attól, hogy a felhasználónak a
csoportban van-e jogosultsága csatoláshoz. 
Tehát ajánlatos backend felhasználó csoportokat alkalmazni a
csatolások beállításához. Különosen akkor, ha szükséges a
megosztás sok felhasználó között.',
		'file_mountpoints.description' => 'A fájlkönyvtár csoporthoz rendel kiinduló pontot.',
		'file_mountpoints.details' => 'A fájl könyvtár-rendszert az összes, a webszerver
fájlkönyvtárai közötti navigálásra szánt fájl almodul
használja. Meg kell jegyezni, hogy az adatbázis
csatolásokhoz hasonlóan, a fájlkönyvtár csatolások is
öröklõdnek a csoporthoz tartozó felhasználóktól.',
		'pagetypes_select.description' => 'Válaszd ki, hogy a csoporttagok milyen típusú oldalakat állíthatnak be.',
		'pagetypes_select.details' => 'Ez az opció korlátozza az érvényes választási lehetõségek számát, ha a felhasználó az oldal típusának kiválasztására készül.',
		'tables_modify.description' => 'Válaszd ki, hogy a csoporttagok milyen táblákat módosíthatnak.',
		'tables_modify.details' => 'Az engedélybeállítások esetén fontos annak a megadása, hogy
melyik táblát módosíthatja egy felhasználó.
A módosításra engedélyezett táblák elérhetõek kiválasztásra
is, így nem szükséges az itt megadott táblákat a
"Táblák (listázás)" dobozban beállítani.',
		'tables_select.description' => 'Válaszd ki, hogy mely táblákat láthatják a tagok a listában (a módosítandó táblákat nem szükséges újra megadni!).',
		'tables_select.details' => 'Megadja, hogy melyik táblák - beleértve a
"Táblák (módosítás)" dobozban kiválasztottak is - láthatók és
listázhatók ki a felhasználó részére. Így nem képes
<em>szerkeszteni</em> a táblát, csak a rekordokat
kiválasztani és a tartalmukat megjeleníteni.
Ez a lista nem annyira fontos. Elég ritka az a helyzet, amikor
egy felhasználó táblákat választhat ki de nem módosíthatja
azokat.',
		'non_exclude_fields.description' => 'Bizonyos táblamezõk alapértelmezésben nem elérhetõek. Ezek explicit módon engedélyezhetõek a csoporttagok részére.',
		'non_exclude_fields.details' => 'A "mezõkizárás engedélyezés" lehetõvé teszi a táblákhoz
kiosztott engedélyek részletezését. Alapértelmezésként a
felhasználók nem érhetik el ezeket a mezõket, de speciálisan
elérhetõvé tehetik itt kiválasztva õket. Ennek egy
alkalmazása, hogy ezek az oldalak gyakran rejtettek
alapértelmezésben és az, hogy a rejtett mezõk sem
hozzáférhetõek egy felhasználó számára, hacsak nincs
jogosultsága a hozzáféréshez a "mezõkizárás engedélyezés"
alapján. Így a felhasználó új oldalt hozhat létre, de nem rejtheti el az oldalt. Hacsak nincs
hozzárendelve  "Oldal: rejtett"  kizáró mezõ a tagcsoportok
egyikén keresztül.
Természetesen ez nem érinti a mezõ hozzáadását olyan
tábkákból, amelyek nincsenek beszúrva a módosításra
engedélyezett tábla listában.',
		'hidden.description' => 'Nem engedélyez egy felhasználói csoportot.',
		'hidden.details' => 'Ha egy felhasználói csoport nincs engedélyezve, a csoportnak minden tagja nem örökli azokat a tulajdonságokat, amelyeket valószínûleg a csoportjukból kifolyólag kaptak.',
		'lockToDomain.description' => 'Add meg azt a hosztnevet, ahonnan a felhasználónak be kell jelentkeznie.',
		'lockToDomain.details' => 'A TYPO3 rendszernek több rámutató tartománypontja is lehet. Így ez az opció biztosítja, hogy a felhasználók csak bizonyos hosztnévrõl jelentkezhessenek be.',
		'groupMods.description' => 'Válaszd ki az csoporttagok számára elérhetõ backend modulokat.',
		'groupMods.details' => 'Meghatározza, hogy melyik menüpont érhetõ el a
felhasználócsoport részére.
Ez a modullista hozzáadódik bármelyik, más tagcsoport által
kiválasztottakhoz továbbá a megfelelõ magának a
felhasználónak a megfelelõ mezõje.',
		'inc_access_lists.description' => 'Válaszd ki, hogy az Oldaltípus, Tábla, Modul és Mezõkizárás engedélyezés listák elérhetõek-e ezen csoport részére.',
		'description.description' => 'Add meg a felhasználó csoport rövid leírását, mire való és ki lehet tag. Csak belsõ használatra.',
		'TSconfig.description' => 'Kiegészítõ beállítások TypoScript stílusú értékeken (Részletes).',
		'TSconfig.syntax' => 'TypoScript stílus feltételek és állandók nélkül.',
		'hide_in_lists.description' => 'Ez az opció megvédi a felhasználó csoportot a megjelenéstõl a csoportkiválasztó listában.',
		'hide_in_lists.details' => 'Ez érinteni fogja a Feladatközpont Teendõk és Üzenetek
részében levõ felhasználócsoportok listáját valamint a
Web>Hozzáférés modult. Az opció gyakran használatos
ha csak általános, néhány globális tulajdonsággal bíró
felhasználói csoportok vannak.
Valószínûleg nem szeretnénk, hogy az összes felhasználó lássa
egymást ezen csoportbeli tagság alapján, például
üzenetküldésre vagy a teendõk megosztására egymás között.
Ez az, amit ez az opció megakadályoz.',
		'subgroup.description' => 'Válaszd ki azon backend felhasználócsoportokat, amelyek automatikusan bekerülnek ennek a csoportnak a tagjai közé.',
		'subgroup.details' => 'A tulajdonságok vagy alcsoportok hozzáadódnak ezen csoport
tulajdonságaihoz és alapjában hozzáadódnak bármelyik
felhasználó csoport listájához, amelyeknek a felhasználó a
tagja.
Ez lehetõséget ad egy \'Supervisor\' csoport létrehozásához.',
	),
	'gl' => Array (
	),
	'th' => Array (
	),
	'gr' => Array (
	),
	'hk' => Array (
		'.description' => '³o¬O¤@­Ó«á¶ÔºŞ²z­û¨Ï¥ÎªÌ¸s²Õ¥i«ı«á¶Ô¨Ï¥ÎªÌ¨Ï¥Î¡C³o¨Ç¨M©w«á¶Ô¨Ï¥ÎªÌªº¦s¨úÅv¡C',
		'title.description' => '«á¶Ô¨Ï¥ÎªÌ¸s²Õªº¦WºÙ',
		'db_mountpoints.description' => '³]©w¨Ï¥ÎªÌ¦b¾ğ¹Ïªº°_©lÂI¡C',
		'db_mountpoints.details' => '³Q©Ò¦³ºô¯¸°Æ¼Ò²Õ©Ò¨Ï¥Îªººô­¶¾ğ¹Ï¤@©w­n¦³¤@¨Ç©w¹ï¤Fªº¶i¤JÂI¡C¦b³o¸Ì§AÀ³¸Ó¦V¤@­Óºô­¶´¡¤J¤@­Ó©Î¦h­Óªº°Ñ·Ó¡A¦Ó³o­Óºô­¶·|¥Nªíºô­¶¾ğ¹Ï¤¤¤@­Ó·sªº®Ú­¶¡C³oºÙ¬°¡u¸ê®Æ®w±¾ÂI¡v¡C¸ê®Æ®w±¾ÂI·|¥Ñ³Q©Ò¦³Äİ©ó¦P¤@¨Ï¥ÎªÌ¸s²Õªº¨Ï¥ÎªÌ©Ò©ÓÅ§¡C³o¦b¥G¨Ï¥ÎªÌ¬O§_³Q³]©w¬°¨Ï¥Î»P¸s²Õ¬Û¦Pªº±¾ÂI¡CµM¦Ó¡A§Ú­Ì«ØÄ³¨Ï¥Î«á¶Ô¨Ï¥ÎªÌ¸s²Õ¨Ó³]©w±¾ÂI¡C¯S§O¬O»İ­nµ¹«Ü¦h¨Ï¥ÎªÌ¦@¥Î®É¡C',
		'file_mountpoints.description' => '³]©wÀÉ®×¸ê®Æ§¨¾ğ¹Ïªº°_©lÂI',
		'file_mountpoints.details' => '©Ò¦³ÀÉ®×°Æ¼Ò²Õ¨Ï¥ÎÀÉ®×¸ê®Æ§¨¾ğ¹Ï¨Ó·ÈÄıºô¯¸¦øªA¾¹¤WªºÀÉ®×¸ê®Æ§¨¡C¯d·N¡A¥¿¦p¸ê®Æ®w±¾ÂI¤@¼Ë¡AÀÉ®×±¾ÂI¤]¥i¥H³Q©Ò¦³Äİ©ó¦P¤@¨Ï¥ÎªÌ¸s²Õªº¨Ï¥ÎªÌ©ÓÅ§¡C',
		'pagetypes_select.description' => '¿ï¾Ü¸s²Õ¦¨­û¥i¥H³]©wªººô­¶¡uºØÃş¡v',
		'pagetypes_select.details' => '³o­Ó¿ï¶µ¦b¥L­n·Ç³Æ¿ï¾Ü¤@­Óºô­¶ºØÃş®É¡A­­¨î¨Ï¥ÎªÌªº¦³®Ä¿ï¾Ü¼Æ¥Ø',
		'tables_modify.description' => '¿ï¾Ü¦¨­û¥i¥H­×§ï¨º¨Ç¸ê®Æªí®æ',
		'tables_modify.details' => '¤@­Ó³]©w¦s¨úÅv­­ªº­«­n³¡¤À¬O©w¸q¨Ï¥ÎªÌ¥i¥H­×§ï¨º¨Ç¸ê®Æªí®æ¡C®e³\\­×§ïªº¸ê®Æªí®æ·|¦P®É¦Û°Êªº®e³\\¿ï¾Ü¡A¦]¦¹§A¤£»İ­n³]©w¦b¡uªí®æ¡]ªí¦C¡^¡v¤¤¿é¤Jªºªí®æ¡C

<strong>¯d·N¡G</strong>³o­Ó¦W³æ¥[¶i¨Ï¥ÎªÌ¨ä¥L©ÒÄİªº¸s²Õ¤w¿ïªºÄæ¥Ø¤¤¡C',
		'tables_select.description' => '¿ï¾Ü­û¥i¥H¦b°O¿ıªí¦C¤¤¥i¥H¬İ¨ì¨º¨Ç¸ê®Æªí®æ¡]¡u­×§ï¡vªí®æ¤£»İ­n¦b³o¸Ì­«·s¿é¤J¡I¡^',
		'tables_select.details' => '¨M©w¨º¨Ç¸ê®Æªí - ¥[¤W¨º¨Ç¦b¡u¸ê®Æªí¡]­×§ï¡^¡v²°¤l¤¤ªº®Æªí - ¥i¥H³Q¨Ï¥ÎªÌ¹wÄı©M¦C¥X¡C¥L¦]¦¹¤£¯à<em>­×§ï</em>¸ê®Æªí - ¥u¯à¿ï¾Ü°O¿ı©MÆ[¬İ¤º®e¡C
³o¦W³æ¤£¬O«D±`­«­n¡C³o¬O¤@­Ó»á¬°¨u¨£ªº±¡ªp¡A¤@¦W¨Ï¥ÎªÌ¥i¥H¿ï¾Ü¸ê®Æªí¦ı¤£¯à­×§ï¥¦­Ì¡C',
		'non_exclude_fields.description' => '¬Y¨Ç¸ê®Æªí®æ¬O¹w³]¤£¯à¨Ï¥Îªº¡C¨º¨ÇÄæ¤£¯àª½±µ³Q±Ò°Êµ¹¸s²Õ¦¨­û¨Ï¥Î',
		'non_exclude_fields.details' => '¡u®e³\\ªºÃB¥~Äæ¦ì¡v®e³\\§A¸Ô²Óªº½á¤©¸ê®ÆªíªºÅv­­¡C¹w³]©Ò¦³³o¨Ç¦ì³£¤£¨Ñ¨Ï¥ÎªÌ¨Ï¥Î¡A¦ı¬O¦b³o¸Ì¿ï¾Üªº´N·|¯S§Oªº³Q±Ò°Ê¡C
³o¸Ìªº¤@­ÓÀ³¥Î¬Oºô­¶¹w³]¬OÁôÂÃªº¡A¦Ó³o­ÓÁôÂÃÄæ¦ì¬O¤£¨Ñ¨Ï¥ÎªÌ¹B¥Îªº¡A°£«D¥L³z¹L¡u®e³\\ªºÃB¥~Äæ¦ì¡v±o¨ì¦s¨úªºÅv­­¡A§_«h¥L¤£¥i¥H¨Ï¥Î³o­ÓÄæ¦ì¡C¦]¦¹¨Ï¥Î¥H«Ø¥ßºô­¶¡A¦ı¬O¥L¤£¯àÁôÂÃºô­¶¡C·íµM¥L³z¹L¥L©ÒÄİªº¨ä¤¤¤@­Ó¸s²Õ±o¨ì°tµ¹¡uºô­¶¡GÁôÂÃ¡vÃB¥~Äæ¦ì¨Ò¥~¡C
±q¤£®e³\\­×§ïªº¸ê®Æªí¤¤¥[¤JÄæ¦ì·íµM¨Ã¤£¦³·N¸q¡C',
		'hidden.description' => 'Ãö³¬¤@­Ó¨Ï¥ÎªÌ¸s²Õ',
		'hidden.details' => '°²¦p§AÃö³¬¤@­Ó¨Ï¥ÎªÌ¸s²Õ¡A©Ò¦³Äİ©ó¦¹¤@¸s²Õªº¨Ï¥ÎªÌ³£·|³Q¼vÅT¤£¯à©ÓÅ§¦¹¸s²Õ©Ò½ç¤©ªº¥ô¦ó¯S©Ê¡C',
		'lockToDomain.description' => '¿é¤J¥D¾÷ªº¦WºÙ¡A¨Ï¥ÎªÌ¥²¶·±q¦¹µn¤J',
		'lockToDomain.details' => '¤@­ÓTYPO3¨t²Î¥i¥H¦³¦h­Óºô°ì¨Ï¥Î¡C¦]¦¹¡A³o­Ó¿ï¶µ«OÃÒ¥u¥i¥H±q¬Y¤@¥D¾÷µn¤J',
		'groupMods.description' => '¬°¸s²Õ¦¨­û¿ï¾Ü¥i¥Îªº«á¶Ô¼Ò²Õ',
		'groupMods.details' => '¨M©w¨º¤@¨Ç¡u¿ï³æ¶µ¥Ø¡v¥i¨Ñ¸s²Õ¦¨­û¨Ï¥Î¡C
³o­Ó¼Ò²Õªº¦W³æ¦P®É³Q¥[¶i©Ò¦³³Q¨Ï¥ÎªÌ¨ä¥L¸s²Õ¤¤¿ï¾Üªº¼Ò²Õ©M¨Ï¥ÎªÌ¦Û¤v¬Û¹ïªºÄæ¥Ø¤¤¡C',
		'inc_access_lists.description' => '¿ï¾Üºô­¶¡NºØÃş¡N¼Ò²Õ©M³\\¥iªºÃB¥~Äæ±Ò°Êµ¹³o­Ó¸s²Õ¨Ï¥Î',
		'description.description' => '¿é¤J¨Ï¥ÎªÌ¸s²ÕªºÂ²µu´y­z¡A¨ä¥Øªº©M½Ö¤H¦¨¬°¦¨­û¡C¥u§@¤º³¡¨Ï¥Î¡C',
		'TSconfig.description' => '³z¹LTypoScript§ÎºA¼Æ­È¡]¶i¶¥¡^ªºªş¥[³]©w',
		'TSconfig.syntax' => '¨S¦³±ø¥ó©M±`­ÈªºTypoScript§ÎºA',
		'hide_in_lists.description' => '³o­Ó¿ï¶µ·|¨¾¤î¨Ï¥ÎªÌ¸s²Õ¦b¿ï¾Ü¦W³æ¤¤¥X²{',
		'hide_in_lists.details' => '³o·|¼vÅT¡u¤u§@¤¤¤ß¡v¤u§@²M³æ©M°T®§³¡¤À¡A»P¤Îºô­¶>Åv­­¼Ò²Õªº¨Ï¥ÎªÌ¸s²Õ¦W³æ¡C
°²¦p§A¦³¤@¯ëªº¨Ï¥ÎªÌ¸s²Õ©w¸q§A¸s²Õ¤¤ªº¨Ï¥ÎªÌ¾ãÅéªº¯S©Ê¡A³o­Ó¿ï¶µ¬O´N«D±`¦³¥Î¡CµM«á§A©Î³\\¤£³ßÅw©Ò¦³³o¨Ç¨Ï¥ÎªÌ³z¹L³o­Ó¸s²Õ­û¨­¥÷¡u¬İ¨ì¡v¹ï¤è¡A¨Ò¦p¶Ç°e°T®§©Î¤u§@²M³æµ¹¹ï¤è¡C¦Ó³o´N¬O³o­Ó¿ï¶µ©ÒÁ×§Kªº¡C',
		'subgroup.description' => '¿ï¾Ü¦Û°Ê¦a¥]¬A¦b³o­Ó¸s²Õ¦¨­ûªº«á¶Ô¨Ï¥ÎªÌ¸s²Õ',
		'subgroup.details' => '¯S©Ê©M°Æ¸s²Õ³Q¥[¶i³o­Ó¸s²Õ¤¤¡A¦Ó°ò¥»¤W¥L­Ì·|¥[¶i©Ò¦³¦¹¸s²Õªº¦¨­ûªº¦¨­û¸s²Õ¦W³æ¤¤¡C³o­Ó¥\\¯à´£«ı«Ü¦nªº¤èªk¥h«Ø¥ß¡uºÊ¹îªÌ¡v¨Ï¥ÎªÌ¸s²Õ',
	),
	'eu' => Array (
	),
	'bg' => Array (
		'.description' => 'Òîâà å backend àäìèíèñòğàòîğñêàòà ïîòğåáèòåëñêà ãğóïà íà ğàçïîëîæåíèå íà Backend ïîòğåáèòåëèòå. Â òàçè ãğóïà ñå îïğåäåëÿò ğàçğåøåíèÿòà çà Backend ïîòğåáèòåëèòå.',
		'title.description' => 'Èìå íà Backend ïîòğåáèòåëñêàòà ãğóïà',
		'db_mountpoints.description' => 'Çàäàâàíå íà ñòàğòîâàòà òî÷êà çà ïîòğåáèòåëñêîòî äúğâî.',
		'file_mountpoints.description' => 'Çàäàâàíå íà ñòàğòîâàòà òî÷êà çà ôàéëîâèòå ïàïêè äúğâî.',
		'pagetypes_select.description' => 'Ñåëåêòèğàéòå êîè \'Òèïîâe\' íà ñòğàíèöè, ÷ëåíîâåòå ùå ìîãàò äà íàãëàñÿâàò.',
		'tables_modify.description' => 'Ñåëåêòèğàéòå êîè òàáëèöè ìîãàò äà áúäàò ïğîìåíÿíè îò ÷ëåíîâåòå.',
	),
	'br' => Array (
		'.description' => 'Estes são os grupos de usuário do administrador disponíveis para os usuários da ferramenta de administração. Estes grupos determinam as permissões para os usuários do Administrador.',
		'title.description' => 'Nome do grupo de usuário do Administrador',
		'db_mountpoints.description' => 'Definir pontos de partida para as árvores de páginas dos usuários.',
		'db_mountpoints.details' => 'A árvore de páginas, usada para navegar por todos os submódulos incluídos em "Internet", deve possuir pontos-de-entrada definidos. Aqui você deve inserir uma ou mais referências para uma página, a qual representará uma nova página-raiz na árvore de páginas.
Os pontos de partida podem ser herdados pelos usuários que são membros deste grupo. Isso vai depender se o usuário estiver configurado para incluir os pontos de partida atribuídos aos membros do grupo. Entretanto, recomenda-se usar grupos de usuários-administradores como este para configurar os pontos de partida. Especialmente se houver necessidade de compartilhá-los entre muitos usuários.',
		'file_mountpoints.description' => 'Definir pontos de partida para a árvore de pastas de arquivos.',
		'file_mountpoints.details' => 'A árvore de pastas de arquivos é usada por todos os submódulos incluídos em "Arquivo" para navegar entre as pastas de arquivos do servidor.
Observe que, assim como os \'pontos de partida para a árvore de páginas\', os pontos de partida para a árvore de arquivos podem ser herdados pelos usuários membros deste grupo.',
		'pagetypes_select.description' => 'Selecionar quais \'Tipos\' de Páginas os membros podem alterar.',
		'pagetypes_select.details' => 'Esta opção limita o número de tipos válidos para o usuário, quando este for selecionar um tipo de página.',
		'tables_modify.description' => 'Selecionar quais tabelas os membros podem modificar.',
		'tables_modify.details' => 'Uma parte importante ao atribuir permissões é definir quais tabelas do banco de dados um usuário tem permissão para modificar.
Tabelas liberadas para modificação são automaticamente liberadas também para seleção e portanto você não precisa incluir novamente na caixa "Tabelas (lista)" as tabelas selecionadas aqui.

<strong>Observe</strong> que esta lista se soma aos campos selecionados nos outros grupos de membros de um usuário.',
		'tables_select.description' => 'Selecionar quais tabelas os membros podem visualizar na lista de registros (tabelas \'modificáveis\' não precisam ser reinseridas aqui).',
		'tables_select.details' => 'Determina quais tabelas - além das selecionadas na caixa "Tabelas (modificar)" - podem ser visualizadas e listadas pelo usuário. Porém, ele não é capaz de <em>editar</em> a tabela - apenas selecionar os registros e visualizar o conteúdo.
Esta lista não é tão importante. É uma das raras situações em que um usuário pode selecionar tabelas mas não modificá-las.',
		'non_exclude_fields.description' => 'Certos campos da tabela não estão disponíveis no modo padrão. Esses campos podem ser explicitamente habilitados aos membros do grupo aqui.',
		'non_exclude_fields.details' => '"Habilitar campos ocultos" permite a você configurar detalhadamente as permissões de acesso às tabelas. Por padrão, estes campos não são disponibilizados aos usuários, portanto precisam ser especificamente habilitados através desta opção.
Uma utilidade para esta opção é que as páginas são normalmente ocultas por padrão, e a opção "oculta" não está disponível ao usuário a não ser que tenha o acesso atribuído através da listagem de "Habilitar campos ocultos". Desta forma, o usuário pode criar uma página, mas não pode torná-la visível. A não ser, claro, se estiver atribuído a ele o campo "Página: oculta" através de um dos grupos de usuário.
É logico também que não fará sentido nenhum adicionar campos de tabelas que não estejam incluídas na listagem de tabelas modificáveis.',
		'hidden.description' => 'Desativa um grupo de usuários.',
		'hidden.details' => 'Se você desabilita um grupo de usuários, os membros desse grupo não mais herdarão quaisquer propriedades que este grupo tenha atribuído a eles.',
		'lockToDomain.description' => 'Digite o nome de domínio através do qual o usuário deverá fazer o login.',
		'lockToDomain.details' => 'Um sistema TYPO3 pode ter vários domínios direcionados a ele. Assim, esta opção garante que os usuários façam seu login apenas a partir de um determinado nome de domínio.',
		'groupMods.description' => 'Seleciona os módulos de administração disponibilizados aos membros do grupo.',
		'groupMods.details' => 'Esta opção determina quais \'ítens de menu\' serão disponibilizados aos membros do grupo.
Esta lista de módulos se soma aos módulos selecionados nos outros grupos ao qual o usuário pertence, assim como no campo correspondente ao usuário em si.',
		'inc_access_lists.description' => 'Selecione se as listas de acesso Tipo de Página, Tabela, Módulo e Permitir campos excluídos estão disponíveis para este grupo.',
		'description.description' => 'Digite uma breve descrição do grupo de usuários, para que ele serve e quem deve ser membro. Apenas para uso interno.',
		'TSconfig.description' => 'Configurações adicionais através de valores de estilo TypoScript (Avançado).',
		'TSconfig.syntax' => 'Estilo TypoScript sem condições e constantes.',
		'hide_in_lists.description' => 'Esta opção previne que o grupo de usuário seja mostrado em listas onde outros grupos de usuários estejam selecionados.',
		'hide_in_lists.details' => 'Afeta a listagem de grupos de usuários nos módulos A Fazer e Mensagens, no Centro de Tarefas, assim como no módulo Internet>Acesso.
Esta opção é muito útil se você possui grupos genéricos de usuários, com definições de algumas propriedades globais, dos quais todos seus usuários são membros. Assim, provavelmente você não gostaria que todos esses usuários \'vejam\' uns aos outros durante a participação no grupo, como por exemplo enviando mensagens ou A Fazer a cada usuário. Isto é o que esta opção previne.',
		'subgroup.description' => 'Selecione os usuários-administradores que serão automaticamente incluídos como membros deste grupo.',
		'subgroup.details' => 'As propriedades dos subgrupos são adicionadas às propriedades destes grupos, e basicamente estes subgrupos são simplesmente adicionados à listagem de grupos de usuários vista por qualquer membro deste grupo.
Esta característica é uma ótima forma de criar grupos de usuários \'Supervisores\'.',
	),
	'et' => Array (
	),
	'ar' => Array (
	),
	'he' => Array (
		'.description' => '××œ×• ×§×‘×•×¦×•×ª ××©×ª××©×™× ×©×œ ×”×××©×§ ×”××—×•×¨×™ ×”×–××™× ×•×ª ×¢×‘×•×¨ ××©×ª××©×™ ×××©×§ ×”××—×•×¨×™. ×”× ××’×“×™×¨×™× ×”×¨×©××•×ª ×œ××©×ª××©×™ ×××©×§ ×”××—×•×¨×™.',
		'title.description' => '×©× ×©×œ ×§×‘×•×¦×ª ××©×ª××©×™× ×©×œ ×××©×§ ×”××—×•×¨×™.',
		'db_mountpoints.description' => '×”×§×¦×” × ×™×§×•×“×•×ª ×”×ª×—×œ×” ×©×œ ×¢×¥ ×“×¤×™× ×©×œ ××©×ª××©.',
		'db_mountpoints.details' => '×¢×¥ ×“×¤×™×, ×©××©×ª××©×™× ×‘×• ×›×œ ×ª×ª-××•×“×•×œ×™× ×‘××™× ×˜×¨× ×˜ ×›×“×™ ×œ× ×•×•×˜, ×—×•×‘×” ×©×™×”×™×• ×œ×• × ×§×•×“×•×ª ×›× ×™×¡×” ××•×’×“×¨×•×ª. ×›××Ÿ ×¢×œ×™×š ×œ×”×›× ×™×¡ ×¡×™××•×›×™×Ÿ ×œ×“×£ ×©×ª×™×™×¦×’ ×“×£ ×©×•×¨×© ×—×“×©×” ×‘×¢×¥ ×“×¤×™×. ×–×” × ×§×¨× "××•×¦×‘ ×××’×¨ ××™×“×¢". ××©×ª××©×™× ×‘×§×‘×•×¦×” ×–×• ×™×›×•×œ×™× ×œ×¨×©×ª ××•×¦×‘×™ ×××’×¨ ××™×“×¢. ×–×” ×ª×œ×•×™ ×‘×”×’×“×¨×” ××¦×œ ×”××©×ª××© ×× ×¢×œ×™×• ×œ×¨×©×ª ××•×¦×‘×™× ×”××•×’×“×¨×™× ×‘×§×‘×•×¦×•×ª ××©×ª××©×™× ××œ×™×”× ×”×•× ××©×ª×™×™×š. ×‘×›×œ ×–××ª, ××•××œ×¥ ×œ×”×©×ª××© ×‘×§×‘×•×¦×•×ª ××©×ª××©×™ ×××©×§ ××—×•×¨×™ ×‘×¦×•×¨×” ×”×–×• ×›×“×™ ×œ×”×’×“×™×¨ ××•×¦×‘×™×. ×‘××™×•×—×“ ×× ×™×© ×¦×•×¨×š ×œ×©×ª×£ ××•×ª×• ×‘×™×Ÿ ××©×ª××©×™× ×¨×‘×™×.',
		'file_mountpoints.description' => '×”×§×¦×” × ×™×§×•×“×•×ª ×”×ª×—×œ×” ×©×œ ×¢×¥ ×ª×™×§×™×•×ª ×§×‘×¦×™×.',
		'file_mountpoints.details' => '×›×œ ×”×ª×ª-××•×“×•×œ×™× ×©×œ "×§×•×‘×¥" ××©×ª××©×™× ×‘×¢×¥ ×§×‘×¦×™× ×›×“×™ ×œ× ×•×•×˜ ×‘×™×Ÿ ×ª×™×§×™×•×ª ×¢×œ ×”×©×¨×ª. × × ×œ×¦×™×™×Ÿ, ×©××©×ª××©×™× ×‘×§×‘×•×¦×” ×™×›×•×œ×™× ×œ×¨×©×ª ××•×¦×‘×™ ×§×‘×¦×™×, ×›××• ××•×¦×‘×™ ×××’×¨ ××™×“×¢.',
		'pagetypes_select.description' => '×‘×—×¨ ××™×–×” "×¡×•×’×™×" ×©×œ ×“×¤×™× ×™×›×•×œ×™× ×œ×§×‘×•×¢ ×”××©×ª××©×™×.',
		'pagetypes_select.details' => '××¤×©×¨×•×ª ×–×• ××’×‘×™×œ×” ××¡×¤×¨ ×‘×—×™×¨×•×ª ×œ××©×ª××© ×›××©×¨ ×”×•× ×¢×•××“ ×œ×‘×—×•×¨ ×¡×•×’ ×“×£.',
		'tables_modify.description' => '×‘×—×¨ ××™×–×” ×˜×‘×œ××•×ª ×™×›×•×œ×™× ×”××©×ª××©×™× ×œ×¢×¨×•×š.',
		'tables_modify.details' => '×©×œ×‘ ×—×©×•×‘ ×‘×”×’×“×¨×ª ×”×¨×©××•×ª ×”×•× ×œ×¦×™×™×Ÿ ××™×–×” ×˜×‘×œ××•×ª ×”××©×ª××© ×™×›×•×œ ×œ×¢×¨×•×š. ×˜×‘×œ××•×ª, ×©×™×© ×¢×œ×™×”× ××™×©×•×¨ ×¢×¨×™×›×” - ×™×© ×¢×œ×™×”× ×’× ××™×©×•×¨ ×‘×—×™×¨×” ××•×˜×•××˜×™×ª, ×œ×›×Ÿ ××™×Ÿ ×¦×•×¨×š ×œ×¦×™×™×Ÿ ×˜×‘×œ××•×ª ×©×¦×™×™× ×ª ×›××Ÿ ×’× ×‘"×˜×‘×œ××•×ª(×¨×©×™××”)".
<strong>×©×™× ×œ×‘,</strong> ×›×™ ×¨×©×™××” ×–×• ××ª×•×•×¡×¤×ª ×œ×©×“×•×ª ×©× ×‘×—×¨×• ×¢×‘×•×¨ ××©×ª××© ×‘×§×‘×•×¦×•×ª ××—×¨×•×ª ××œ×™×”× ×”×•× ××©×ª×™×™×š.',
		'tables_select.description' => '×‘×—×¨ ××™×–×” ×˜×‘×œ××•×ª ×”××©×ª××©×™× ×™×›×•×œ×™× ×œ×¨××•×ª ×‘×¨×©×™××•×ª ×¨×©×•××•×ª (×œ× × ×“×¨×© ×œ×”×›× ×™×¡ ×›××Ÿ ×˜×‘×œ××•×ª "×¢×¨×•×š" ×©×•×‘)',
		'tables_select.details' => '×–×” ××’×“×™×¨ ××™×–×• ×˜×‘×œ××•×ª, ×‘× ×•×¡×£ ×œ××œ× ×©× ×‘×—×¨×• ×‘-"×˜×‘×œ××•×ª(×¢×¨×•×š)", ××©×ª××© ×™×›×•×œ ×œ×¨××•×ª. ×œ×›×Ÿ ×”×•× ×œ× ×™×›×•×œ <em>×œ×¢×¨×•×š</em>××ª ×”×˜×‘×œ×” - ×¨×§ ×œ×‘×—×•×¨ ×¨×©×•××•×ª ×•×œ×¨××•×ª ××ª ×”×ª×•×›×Ÿ.
×”×¨×©×™××” ×œ× ×—×©×•×‘×” ×›×œ ×›×š. ×“×™ × ×“×™×¨ ×©××©×ª××© ×™×›×•×œ ×œ×‘×—×•×¨ ×˜×‘×œ××•×ª ××‘×œ ×œ× ×œ×¢×¨×•×š ××•×ª×.',
		'non_exclude_fields.description' => '×©×“×•×ª ×˜×‘×œ×” ××¡×•×™××•×ª ×œ× ×–××™× ×•×ª ×›×‘×¨×™×¨×ª ××—×“×œ. ×›××Ÿ × ×™×ª×Ÿ ×œ×”×¤×•×š ××•×ª× ×œ×–××™× ×•×ª ×œ××©×ª××©×™× ×‘×§×‘×•×¦×”.',
		'non_exclude_fields.details' => '×›××Ÿ × ×™×ª×Ÿ ×œ×¤×¨×˜ ×”×¨×©××•×ª ×©× ×ª×ª ×œ×˜×‘×œ××•×ª. ×›×‘×¨×™×¨×ª ××—×“×œ, ×›×œ ×”×©×“×•×ª ×”××œ× ×œ× ×–××™× ×•×ª ×œ××©×ª××©×™× ×•×¦×¨×™×š ×œ×”×’×“×™×¨ ×’×™×©×” ××œ×™×”× ×›××Ÿ. ×œ×“×•×’××”, ××—×“ ×”×©×™××•×©×™× ×‘×¨×¢×™×•×Ÿ ×©×××—×•×¨×™ ×–×” - ×“×¤×™× ×—×“×©×™× × ×•×¦×¨×™× ×—×‘×•×™×™× ×•×× ×œ××©×ª××© ×œ× ××•×’×“×¨×ª ×’×™×©×” ×œ×©×“×” "×—×‘×•×™" - ×”×•× ××™× ×• ×™×›×•×œ ×œ×©×—×¨×¨ ××ª ×”×“×£. ×œ××©×ª××© ××—×¨ (××‘×§×¨) ××•×¤×™×¢ ×”×©×“×”, ×•××– ×”×•× ×™×›×•×œ ×œ×©×—×¨×¨ ××ª ×”×“×£ ××—×¨×™ ×‘×“×™×§×ª ×ª×•×›×Ÿ.',
		'hidden.description' => '×”×•×¤×š ×§×‘×•×¦×ª ××©×ª××©×™× ×œ×œ× ×–××™× ×”.',
		'hidden.details' => '×× ××ª×” ×”×•×¤×š ×§×‘×•×¦×ª ××©×ª××©×™× ×œ×œ× ×–××™× ×” - ×›×œ ×”××©×ª××©×™× ×‘×§×‘×•×¦×” ×œ× ×™×¨×©×• ×›×œ ×”×’×“×¨×” ××§×‘×•×¦×” ×–×• ×›×ª×•×¦××”.',
		'lockToDomain.description' => '×”×›× ×¡ ×©× ×“×•××™×™×Ÿ ×©×¨×§ ××× ×• ××©×ª××© ×™×›×•×œ ×œ×”×›× ×¡ ×œ××¢×¨×›×ª.',
		'lockToDomain.details' => '××¢×¨×›×ª TYPO3 ×™×›×•×œ×” ×œ××¤×©×¨ ×œ×“×•××™×™× ×™× ×¨×‘×™× ×œ×”×¦×‘×™×¢ ××œ×™×”. ×œ×›×Ÿ, ××¤×©×¨×•×ª ×–×• ××‘×˜×™×—×” ×©××©×ª××©×™× ×™×›×•×œ×™× ×œ×”×›× ×¡ ×¨×§ ××©× ×“×•××™×™×Ÿ ××¡×•×™×.',
		'groupMods.description' => '×‘×—×¨ ××•×“×•×œ×™× ×‘×××©×§ ××—×•×¨×™ ×©×–××™× ×™× ×œ××©×ª××©×™× ×‘×§×‘×•×¦×”.',
		'groupMods.details' => '×–×” ×§×•×‘×¢ ××™×–×” "×¤×¨×™×˜×™ ×ª×¤×¨×™×˜" ×–××™× ×™× ×œ××©×ª××©×™ ×”×§×‘×•×¦×”. ×¨×©×™××” ×–×• ×©×œ ××•×“×•×œ×™× ××ª×•×•×¡×¤×ª ×œ×›×œ ×”××•×“×•×œ×™× ×©× ×‘×—×¨×• ×‘×§×‘×•×¦×•×ª ××©×ª××©×™× ××—×¨×•×ª ×©×œ ×”××©×ª××©, ×›××• ×’× ×©×“×” ×©×” ×‘×”×’×“×¨×•×ª ×©×œ ×”××©×ª××© ×¢×¦××•.',
		'inc_access_lists.description' => '×‘×—×¨ ×× ×’×™×©×” ×œ×¡×•×’ ×“×£, ×˜×‘×œ×”, ××•×“×•×œ ×•×©×“×•×ª ××™×•×—×“×•×ª ××•×ª×¨×•×ª ×œ×§×‘×•×¦×” ×–×•.',
		'description.description' => '×”×›× ×¡ ×ª×™××•×¨ ×§×¦×¨ ×©×œ ×”×§×‘×•×¦×”, ××” ×”×™× ×•××™ ×”× ×”××©×ª××©×™×. ×–×” ×œ×©×™××•×© ×¤× ×™××™ ×‘×œ×‘×“.',
		'TSconfig.description' => '×”×’×“×¨×•×ª × ×•×¡×¤×•×ª ×“×¨×š ×¢×¨×›×™× TypoScript (××ª×§×“×).',
		'TSconfig.syntax' => '×‘×¡×’× ×•×Ÿ TypoScript ×œ×œ× conditions ×•-constants.',
		'hide_in_lists.description' => '××¤×©×¨×•×ª ×–×• ×ª×× ×¢ ××”×§×‘×•×¦×” ×œ×”×•×¤×™×¢ ×‘×¨×©×™××•×ª ×©×œ ×§×‘×•×¦×•×ª ××©×ª××©×™×.',
		'hide_in_lists.details' => '×–×” ×™×©×¤×™×¢ ×¢×œ ×¨×©×™××ª ×§×‘×•×¦×•×ª ××©×ª××©×™× ×‘××¨×›×– ××©×™××•×ª ×•××•×“×•×œ ××™× ×˜×¨× ×˜>×’×™×©×”. ×”×‘×—×™×¨×” ×”×™× ×” ×××•×“ ×©×™××•×©×™×ª ×× ×™×© ×œ×›× ×§×‘×•×¦×•×ª ××©×ª××©×™× ×›×œ×œ×™×•×ª, ×©××’×“×™×¨×•×ª ××¤×©×¨×•×™×•×ª ×›×œ×œ×™×•×ª ×•×›×œ ×”××©×ª××©×™× ×©×™×™×›×™× ××œ×™×”×. ××–, ××ª× ×‘×˜×— ×œ× ×ª×¨×¦×• ×©×›×œ ×”××©×ª××©×™× ×”×œ×œ×• ×™×¨××• ×–×” ××ª ×–×”.',
		'subgroup.description' => '×‘×—×¨ ×§×‘×•×¦×•×ª ××©×ª××©×™× ×©×œ ×××©×§ ××—×•×¨×™ ×©× ×›×œ×œ×™× ××•×˜×•××˜×™×ª ×¢×‘×•×¨ ××©×ª××©×™× ×‘×§×‘×•×¦×” ×–×•.',
		'subgroup.details' => '×”×’×“×¨×•×ª ×©×œ ×ª×ª-×§×‘×•×¦×•×ª ××ª×•×•×¡×¤×•×ª ×œ×”×’×“×¨×•×ª ×©×œ ×§×‘×•×¦×•×ª ××œ×• ×•×¤×©×•×˜ ×™×ª×•×•×¡×¤×• ×œ×¨×©×™××ª ×§×‘×•×¦×•×ª ××©×ª××©×™× ×©×œ ××©×ª××© ×©×©×™×™×š ××œ×™×”×. ×”×’×“×¨×” ×–×• × ×•×ª× ×ª ×“×¨×š ×˜×•×‘×” ×œ×™×¦×•×¨ "×§×‘×•×¦×•×ª ×× ×”×œ×™×".',
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