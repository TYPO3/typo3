<?php
/**
* Default  TCA_DESCR for "sys_action"
*/

$LOCAL_LANG = Array (
	'default' => Array (
		'.description' => 'An \'Action\' is a configuration of a specific task which may be performed by assigned usergroups via the Task Center.',
		'.details' => 'Currently an Action may be configured to create additional backend usergroups without being and admin-user and by selecting from a limited set of options. Another option is to let an action initiate a SQL SELECT-query in the database and return the result as a CSV file.
Actions can be assigned to a Backend usergroup and they are activated by a single click in the Task Center.',
		'title.description' => 'Enter the title of the action. This is shown in the list of available actions in the Task Center.',
		'description.description' => 'Describe what the action does or allows to do.',
		'hidden.description' => 'Check this option if you wish to disable the availability of the action or non-admin users.',
		'hidden.details' => 'This option is a great way to disable an action during changes made to it because it still allows you as an \'Admin\'-user to activate it for test purposes.',
		'type.description' => 'Select the action type.',
		'type.details' => '<strong>"Create Backend User"</strong> allows to create backend users with a limited set of options. This action type is meant for semi-administrators among your users which is in charge of daily user administration - still without being full fledged \'Admin\'-users with \'a License to Kill everything\'.
When you select this option, you get to enter a \'template\' user, enter a prefix which the new users will automatically have and whether or not a user home-dir is created in the proces. Finally you can select a limited number of usergroups which the can be selected among.

<strong>"SQL-query"</strong> allows to make a fixed SQL SELECT-query in the database returned as CSV lists. When you have created an action of this type, you need to go to the Tools>DBint module and enter the Advanced Search feature. Here you can design your SQL-query. When you have designed it to select what you want correctly, you can select this action (by it\'s name) and save the query to the action there. From that point it will be effective from the Task Center. (Notice that the form of output selected in the Advanced Search function is also stored, so make sure to select CSV output there!)
',
		'assign_to_groups.description' => 'Select the backend users groups allowed to activate the action in the Task Center.',
		't1_userprefix.description' => 'Enter a prefix which is forcibly prepended to new usernames (eg. "news_")',
		't1_allowed_groups.description' => 'Enter the Backend user groups which the user performing the action is able to choose among (if any).',
		't1_create_user_dir.description' => 'If checked, a private home-directory is also created during user creation. ',
		't1_create_user_dir.details' => '<strong>Notice:</strong> $TYPO3_CONF_VARS["BE"]["userHomePath"] must be configured correctly along with $TYPO3_CONF_VARS["BE"]["lockRootPath"] and writable!',
		't1_copy_of_user.description' => 'Insert a current Backend user which will be used as a template for the new users created. ',
		't1_copy_of_user.details' => 'All values are copied to the new user, except username, password, name and email is of course overridden.
If the template user is a member of a group which is not defined among the "Groups which may be assigned through the action" that group is still set for the user and cannot be removed by the user carrying out the action.',
		't4_recordsToEdit.description' => '[FILL IN] sys_action->t4_recordsToEdit',
		't3_listPid.description' => '[FILL IN] sys_action->t3_listPid',
		't3_tables.description' => '[FILL IN] sys_action->t3_tables',
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
		'.description' => '\'Toiminto\' (\'Action\') on erityisen tapahtuman konfiguraatio joka voidaan määritellä erityiselle käyttäjäryhmälle Tapahtuma Keskuksessa (Task Center).',
		'.details' => 'Nykyisellään Toimnto voidaankonfiguroida luomalla ylimääräinen taustakäyttäjäryhmä olematta admin-käyttäjiä sekä valitsemalla rajoitettu joukko vaihtoehtoisia valintoja. Toinen mahdollisuus on antaa Toiminnon käynnistää SQLSELECT-kysely tietokantaan ja saatu tulos voidaan palauttaa CSV tiedostona.
Toiminnot voidaan määritellä Tausta käyttäjäryhmälle ja ne voidaan aktivoida yhdellä näpsäytyksellä Tapahtuma Keskuksessa (Task Center).',
		'title.description' => 'Anna Toiminnon otsikko. Tämä näkyy mahdollisten toimintojen listalla Tapahtuma Keskuksessa (Task Center).',
		'description.description' => 'Kuvaa mitä toiminto tekee tai mitä se mahdollistaa tehtäväksi.',
		'hidden.description' => 'Merkkaaa tämä vaihtoehto jos haluat poistaa valinnan mahdollisuuden tai poistaa sen ei-admin käyttäjiltä.',
		'hidden.details' => 'Tämä vaihtoehto on tehokas tämä poistaa toiminto käytöstä muutosten ajaksi koska se silti jää Admin käyttäjille mahdolliseksi.',
		'type.description' => 'Valitse toiminnon tyyppi.',
		'type.details' => '<strong>"Luo Tausta käyttäjä"</strong> mahdollistaa taustakäyttäjän luomisen jolla on rajoitettu määrä toimintaoikeuksia. Tämä toiminto on tarkoitettu puoli-administraattoreille jotka vastaavat päivittäisestä käyttäjähallinnasta - kuitenkin ilman lisenssiä "Ammu kaikki eteentuleva". Kun valitset tämän vaihtoehdon, on Sinun annettav a "malli" käyttäjä, annettava etuliitejoka lisätään uudelle käyttäjälle automaattisesti ja luodaanko tälle käyttäjälle kotihakemisto. Lopuksi valitset rajoitetun joukon käyttäjäryhmiä joista voidaan valita.

<strong>"SQL-kysely"</strong> mahdollistaa kiinteän SQL SELECT-kyselyn tekemisen tietokannasta joka palauttaa CSV listan. Luodessasi tämän tyyppisen toiminnon, tarvitsee Sinun siirtyä Työkalut>DBint (Tools>DBint) aliohjelmaan ja siellä Laajennettu Haku (Advanced Search). Siellä suunnittele SQL-kyselyn.Kun olet suunnitellut sen hakemaan oikein haluamasi tiedot,voit valita tämän toiminnon (sen nimellä) ja tallentaa kyselyn toiminoksi. Näin sen valittavissa toiminoksi  Tapahtuma Keskuksessa (Task Center). (Huomaa että tulosteen haluttu muoto valitaan, tallentaen se, Laajennettu Haku (Advanced Search) osassa, joten varmista että se on CSV tuloste.',
		'assign_to_groups.description' => 'Anna Tausta käyttäjäryhmät joilla on oikeus käynnistää toiminto Tapahtuma Keskuksessa (Task Center).',
		't1_userprefix.description' => 'Anna etuliite joka lisätään (aina) uusiin käyttäjänimiin. (Esim. "news_").',
		't1_allowed_groups.description' => 'Anna Taustakäyttäjäryhmät jotka toimintoa suorittava käyttäjä on oikeutettu valitsemaan.',
		't1_create_user_dir.description' => 'Jos merkattu, yksityinen koti-hakemisto luodaan samalla kuin käyttäjän muut tiedot.',
		't1_create_user_dir.details' => '<strong>Huomaa:</strong> $TYPO3_CONF_VARS["BE"]["userHomePath"] tulee olla konfiguroitu oikei, kuten myös $TYPO3_CONF_VARS["BE"]["lockRootPath"] ja niiden tulee olla kirjoitettavissa!',
		't1_copy_of_user.description' => 'Anna nykyinen Tausta käyttäjä jota käytetään mallina uusi käyttäjiä luotaessa.',
		't1_copy_of_user.details' => 'Kaikki tiedot kopioidaan uudelle käyttäjälle, paitsi käyttäjätunnus, salasana, nimi ja sähköpostiosoite.
Jos mallin käyttäjä onjonkin ryhmän jäsen jota ei ole määritelty "Ryhmään joka voidaan asettaa toiminnolla", asetetaan tämä ryhmäjoka tapauksessa käyttäjälle eikä sitä voi poistaa käyttäjä joka suorittaa toiminnon.',
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
		'.description' => 'Egy \'Mûvelet\' egy adott feladat beállítása, amit egy hozzárendelt felhasználócsoport hajthat végre a Feladatközponton keresztül.',
		'.details' => 'Jelenleg egy Mûvelet beállítható, hogy létrehozzon backend felhasználócsoportokat anélkül, hogy admin-felhasználó lenne és csak korlátozott jogai lennének. Egy másik lehetõség engedni, hogy egy mûvelet elindítson egy SQL SELECT-lekérdezést és a visszakapott eredményt CSV file-ba mentse.
A Mûveletek backend felhasználócsoportokhoz rendelhetõk és egy egyszerû kattintással aktiválhatók.',
		'title.description' => 'Add meg a mûvelet megnevezését. Ez fog megjelenni a Feladatközpontban az elérhetõ mûveletek között.',
		'description.description' => 'Írd le, mit csinál és mit engedélyez a mûvelet.',
		'hidden.description' => 'Válaszd ki, ha nem-admin felhasználók számára nem akarod elérhetõvé tenni a mûveletet.',
		'hidden.details' => 'Ez egy nagyszerû módja, hogy kikapcsold a mûveletet vbáltoztatások alatt, mivel ez lehetõvé teszi neked, mint admin-felhasználónak a mûvelet aktiválását teszt célokra.',
		'type.description' => 'Válaszd ki a mûvelet típusát.',
		'type.details' => 'A <strong>"Backend felhasználó létrehozása"</strong> lehetõvé teszi új backend felhasználók készítését korlátozott jogokkal. Ez közép-adminisztrátorok számára fontos, akik a napi felhasználó karbantartást végzik - anélkül, hogy teljes joggal rendelkezzenek bármi kiírtásához. Ha kiválasztod, meg kell adnod egy sablon felhasználót, egy prefixet, amivel az új felhasználók neve kezdõdni fog, és megadhatsz egy home-könyvtárat is. Végül néhány felhasználócsoportot is megadhatsz, ahova tartozzon.

Az <strong>"SQL-lekérdezés"</strong> SELECT-lekérdezések eredményét menti egy CSV állományba. Egy ilyen típusú mûvelet létrehozásánál el kell menni az Eszközök>DBint modulba és bejelölni a Bõvített keresés opciót. Itt tudsz SQL-lekérdezést tervezni. Amikor megtervezted, hogy mit akarsz lekérdezni, kiválaszthatod ezt a mûveletet (a nevével) és elmentheted a lekérdezést a mûvelethez. Ettõl kezdve elérhetõ lesz a Feladatközpontban. (Figyeljünk, hogy a form kimenet értéke a Bõvített keresésben szintén tárolódik, válasszuk a CSV kimenetet!)',
		'assign_to_groups.description' => 'Add meg azon backend felhasználócsoportokat, melyek aktiválhatják a mûveletet a Feladatközpontban.',
		't1_userprefix.description' => 'Adj meg egy kötelezõ prefixet az új felhasználóneveknek (pl. "news_")',
		't1_allowed_groups.description' => 'Add meg azon backend felhasználócsoportokat, melyek közül a mûveletet elindító felhasználó választani tud (ha vannak).',
		't1_create_user_dir.description' => 'Ha kijelölt, akkor egy saját home-könyvtár is létrejön a felhasználó létrehozás közben.',
		't1_create_user_dir.details' => '<strong>Figyelem:</strong> $TYPO3_CONF_VARS["BE"]["userHomePath"] -t megfelelõen be kell állítani, és írhatóvá tenni a $TYPO3_CONF_VARS["BE"]["lockRootPath"] -val együtt.',
		't1_copy_of_user.description' => 'Az aktuális backend felhasználó beállítása sablonként az új felhaszáló létrehozásához.',
		't1_copy_of_user.details' => 'Az összes érték másolva lett az új feéhasználóhoz, kivéve a felhasználónevet, jelszót, nevet és email címet természetesen. 
Ha a sablon felhasználó tagja volt egy csoportnak, ami nincs beállítva a mûvelethez, akkor a csoport megmarad a felhasználónak és nem távolíthatja ezt el a mûveletet futtató felhasználó.',
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
		'.description' => 'Uma \'Ação\' é a configuração de uma tarefa específica, a qual pode ser executada por determinados grupos de usuários, através do Centro de Tarefas.',
		'.details' => 'No momento, uma Ação pode ser configurada para criar gupos de usuários de administração extras sem ser um usuário-admin e selecionando um conjunto limitado de opções.
Outra opção é deixar que uma ação inicie uma pesquisa SQL SELECT no banco de dados e devolva os resultados na forma de um arquivo CSV.
Ações podem ser delegadas a um grupo de usuário de administração e são ativadas com um único clique no Centro de Tarefas.',
		'title.description' => 'Digite o título da ação. Este será mostrado na lista de ações disponíveis no Centro de Tarefas.',
		'description.description' => 'Descreva o que a ação faz ou permite que se faça.',
		'hidden.description' => 'Marque esta opção se deseja desabilitar a disponibilidade da ação a usuários não-administradores.',
		'hidden.details' => 'Esta opção é uma boa maneira de desabilitar uma ação durante modificações feitas a ela, porque permite a você, como usuário-administrador, ativá-la para a realização de testes.',
		'type.description' => 'Selecione o tipo da ação.',
		'type.details' => '<strong>"Criar Usuário de Administração"</strong> permite criar usuários de administração com um conjunto limitado de opções. Este tipo de ação é para usuários semi-administradores responsáveis por manutenção diária - mas sem ser um usuário \'Admin\' completo com uma \'Licença para Matar tudo\'.
Ao selecionar esta opção, você pode inserir um usuário \'modelo\', inserir um prefixo que será gerado automaticamente para os novos usuários e selecionar se será criado ou não um diretório-raiz no processo. Finalmente, você pode selecionar um número limitado de grupos de usuários que podem ser selecionados junto.

<strong>"Pesquisa-SQL"</strong> permite realizar uma pesquisa fixa do tipo SQL SELECT no banco de dados e retornar como listas CSV. Ao criar uma ação deste tipo, você deve ir para o módulo Ferramentas>DBint e entrar na função Pesquisa Avançada. Quando você tiver configurado para selecionar corretamente o que você deseja, você pode selecionar esta ação (por seu nome) e ali salvar a pesquisa para a ação. Daí em diante estará ativado no Centro de Tarefas. (Note que a forma de resultado selecionada na Pesquisa Avançada também é guardada, portanto certifique-se de selecionar o resultado em CSV ali!)',
		'assign_to_groups.description' => 'Selecionar os grupos de usuários de administração autorizados a ativar a ação no Centro de Tarefas.',
		't1_userprefix.description' => 'Insira um prefixo que será incluído nos novos nomes de usuário (ex: "noticias_")',
		't1_allowed_groups.description' => 'Insira os grupos de usuários de administração que podem ser selecionados pelo usuário executando a ação.',
		't1_create_user_dir.description' => 'Se selecionado, um diretório-raiz pessoal também é criado durante a criação do usuário.',
		't1_create_user_dir.details' => '<strong>Atenção:</strong>$TYPO3_CONF_VARS["BE"]["userHomePath"] deve ser configurado corretamente junto com $TYPO3_CONF_VARS["BE"]["lockRootPath"] e com permissão para gravação!',
		't1_copy_of_user.description' => 'Insira um usuário de administração que será usado como modelo para a criação de novos usuários.',
		't1_copy_of_user.details' => 'Todos os dados são copiados para o novo usuário, com exceção de nome de usuário, senha, nome e senha.
Se o usuário modelo for membro de um grupo que não esteja definido entre "Grupos que podem ser definidos através da ação", este grupo ainda é ligado ao usuário e não pode ser removido pelo usuário executando a ação.',
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
	'ca' => Array (
	),
	'ba' => Array (
	),
	'kr' => Array (
	),
);
?>