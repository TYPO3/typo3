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
		'title.description' => 'Anna tiedostoasetuksen (Filemount) nimi',
		'path.description' => 'Anna tiedostoasetusten polku, joko relatiivinen tai absoluuttinen, riippuen BASE asetuksista.',
		'path.details' => 'Jos Base on asetettu relatiiviseksi, voimassa oleva polku löytyy hakemiston fileadmin/ alta www-palvelimessa.
Näin ollen Sinun tulee asettaa hakemisto \'fileadmin/\' poluksi. Esimerkiksi jos haluat tiedostoasetukseksi "fileadmin/user_upload/all/" on Sinun annettava PATH tiedoksi "user_upload/all/".
Jos BASE on absoluuttinen, on Sinun annettava absoluuttinen polku palvelimessa, esim. /home/ftp_upload" tai "C:/home/ftp_upload". 

<strong>Huomautus:</strong> Kaikissa tapuksissa, varmista että palvelinkäyttäjä jolla PHP toimii on <em>ainakint</em> luku-oikeudet polkuun. Jos näin ei ole, tiedostoasetukset eivät yksinkertaisesti tule näkyviin eikä varoituksia.
Jos Sinulla on ongelmia - erityisesti absoluuttisten asetusten kanssa - yrita asettaa jotain \'yksinkertaista\' kuten relatiivinen asetus fileadmin hakemiston sisällä. Jos tämätoimii oikein yritä asettaa absoluuttinen polku.

Voi olla että myös PHP-asetukset aiheuttavat rajoituksia Sinulle. kuten esimerkikis safe-moodi asetukset. Käytä silloin relatiivisia asetuksia.',
		'hidden.description' => 'Käytä tätä vaihtoehtoa poistaaksesi tiedostoasetukset väliaikaisesti.',
		'hidden.details' => 'Kenelläkään taustkäyttäjälle ei ole enää oikeuksia tiedostoasetukseen. Tämä koskee myös \'Admin\'-käyttäjiä.',
		'base.description' => 'Tällä päätellään onko PATH kentän tieto tunnistettava absoluuttiseksi vai relatiiviseksi poluksi fileadmin/ hakemiston alla olevaksi alihakemistoksi',
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
		'.description' => 'Poveznice (filemounts) opisuje putanju na posluitelju, relativnu ili apsolutnu.',
		'.details' => 'Kreiranjem zapisa poveznice i postavljenjem reference na nju unutar pozadinske grupe korisnika moemo dozvoliti korisnicima da koriste poveznicu unutar Datoteka>Popis (File>List) modula.
Trebate kreirati i postaviti barem jednu poveznicu s pretincem \'_temp_\' ukoliko elite da korisnici mogu prenositi datoteke putem web preglednika.
Poveznice takoğer mogu konfigurirati putanju tako da se moe pristupati datotekama do kojih korisnici imaju FTP pristup. Nemojte zaboraviti postaviti dozvole nad datotekama tako da korisnik, pod èijim ovlastima je pokrenut web posluitelj, moe imati barem dozvolu èitanja.',
		'_.seeAlso' => 'be_korisnici,
be_grupe',
		'title.description' => 'Unesite naslov za poveznicu',
		'path.description' => 'Unesite putanju poveznice, relativnu ili apsolutnu u zavisnosti od postavljenje opcije BASE.',
		'path.details' => 'Ukoliko je BASE opcija postavljena kao relativna onda putanja pokazuje na potpretinac "fileadmin/" web sjedišta. Tada morate unjeti potpretinac unutar "fileadmin/" kao putanju. Na primjer elite li povezati pristup do "fileadmin/user_uploads/all/" onda unesite vrijednost "user_uploads/all" kao vrijednost putanje.
Ukoliko je BASE opcija postavljena kao apsolutna onda ptrbate unjeti apsolutnu putanju na posluitelju, npr. "/home/ftp_upload" ili "C:/home/ftp_upload".

<strong>Napomena:</strong> U svakom sluèaju budite sigurni da korisnik pod èijim ovlastima je pokrenut web posluitelj ima <em>najmanje>7em> prava èitanja unesene putanje. Ukoliko nema tih prava povezani sadraj neæe se pojaviti bez ikakvih upozorenja.
Imate li problema - posebno s apsolutnim povezivanjem - pokušajete povezati nešto "jednostavnije" kao što je relativna ptanja unutar fileadmin pretinca. Ukoliko to radi ispravno probajte s apsolutnom putanjom.

Vaša PHP konfiguracija takoğer moe nametnuti odreğena ogranièenja ukoliko je safe-mod ili slièna znaèajka omoguæena. Tada koristite ralativnu putanju.',
		'_path.seeAlso' => 'sys_filemounts:base',
		'hidden.description' => 'Korištenjem ove opvije privremeno onemoguæavate poveznicu.',
		'hidden.details' => 'Svi pozadinski korisnici koriste',
		'base.description' => 'Odreğuje da li æe vrijednost PATH polja biti prepoznat kao apsolutni  put na posluitelju ili relativni put u odnosu na fileadmin/ podpretinac web sjedišta.',
		'_base.seeAlso' => 'sys_filemounts:path',
	),
	'hu' => Array (
		'.description' => 'A filemount egy relatív vagy abszolút file elérési utat jelent a szerveren.',
		'.details' => 'Egy file mount rekord készítésével és egy hivatkozás elhelyezésével egy Backend felhasználócsoportnak elérést lehet adni a file mounthoz a File>Lista modulban.
Legalább egy filemount-ot kell készíteni és beállítani egy \'_temp_\' könyvtárral, ha azt akarod, hogy a felhasználók file-okat tudjanak feltölteni a böngészõn keresztül.
A filemount-ok elérés is biztosíthatnak egy olyan útvonalhoz a szerveren, amihez a felhasználónak van FTP elérése. Ne felejtsünk el megfelelõ file-jogosultságokat adni, tehát a felhasználónak legalább olvasási joga kell hogy legyen az FTP könyvtárhoz.',
		'title.description' => 'Add meg a filemount nevét',
		'path.description' => 'Add meg a filemount elérési útját, relatív vagy abszolút módon a BASE beállításoktól függõen.',
		'path.details' => 'Ha a BÁZIS relatívra van állítva, a mountolt útvonal a "fileadmin/" alkönyvtárban található. Ekkor be kell írnod a "fileadmin/" alkönyvtárban található elérési utyat. Tehát ha mountolni akarod a "fileadmin/user_uploads/all/" útvonalat, akkor "user_uploads/all" értéket kell az ÚTVONAL mezõbe írni.
Ha a BÁZIS abszolút, akkor a teljes szerver elérési utat be kell írni, pl. "/home/ftp_upload" vagy "C:/home/ftp_upload". 

<strong>Megjegyzés:</strong> Figyeljünk, hogy a webszerver felhasználó, aki a PHP-t futtatja rendelkezzen <em>legalább</em> olvasási joggal az útvonalhoz. Ha mégsem, a mount egyszerûen nem fog megjelenni, és hibaüzenetet sem küld.
Ha problémánk adódik - különösen az abszolút mount-okkal - próbáljuk valami egyszerût mountolni, mint pl. egy relatív útvonalat a fileadmin-ban. Ha ez mûködik, akkor próbáljuk abszolút úttal.

A PHP beállításunk még egyéb akadályokat állíthat, ha a safe-mode típusú lehetõségek engedélyezve vannak. Ekkor használjunk relatív utakat.',
		'hidden.description' => 'Akkor használd, ha ideiglenesen le akarsz tiltani egy filemount-ot.',
		'hidden.details' => 'A mount-ot használó összes backend felhasználónak megszünt az elérése, beleértve az \'Admin\' felhasználókat is.',
		'base.description' => 'Meghatározza, hogy a PATH mezõ értéke abszolút szerverútként vagy a fileadmin/ alkönyvtárhoz képest relatív módon legyen értelmezve.',
	),
	'gl' => Array (
	),
	'th' => Array (
	),
	'gr' => Array (
	),
	'hk' => Array (
		'.description' => 'ÀÉ®×±¾ÂI´y­z¤@­Ó¦øªA¾¹¤¤ªº¸ô®|¡A¬Û¹ï©Îµ´¹ïªº',
		'.details' => '³z¹L«Ø¥ß¤@­ÓÀÉ®×±¾ÂI©M¦b«á¶Ô¨Ï¥ÎªÌ¸s²Õ¤¤©ñ¸m¤@­Ó°Ñ·Ó¡A§A¥i¥H³\\¥i¤@¦W¨Ï¥ÎªÌ¦s¨ú¦bÀÉ®×>ªí¦C¼Ò²Õ¤¤ªºÀÉ®×±¾ÂI¡C
§A»İ­n«Ø¥ß©M³]©w³Ì¤Ö¤@­Ó¥H ¡u_temp_¡v ªºÀÉ®×±¾ÂI¡A°²¦p§A·Q¨Ï¥ÎªÌ³z¹Lºô­¶·ÈÄı¾¹¤W¸üÀÉ®×¡C
ÀÉ®×±¾ÂI¤]³\\¦P®É³]©w¹ï¤@­Ó¦øªA¾¹¤Wªº¸ô®|ªº¦s¨úÅv­­¡A¹ï¦¹¨Ï¥ÎªÌ¦³ ftp ¦s¨úÅv­­¡C¥u­nºò°O¥¿½T¦a³]©w¦ø¾¹¤WªºÀÉ®×¦s¨úÅv­­¡A¥Hª´ºô­¶¦øªA¾¹¨Ï¥ÎªÌ¡]PHP¹B¦æ®Éªº¨Ï¥ÎªÌ¡^¦³³Ì°ò¥»ªº¾\\ÅªÅv­­¦s¨ú FTP ¸ê®Æ§¨¡C',
		'title.description' => '¿é¤JÀÉ®×±¾ÂIªº¦WºÙ',
		'path.description' => '¿é¤JÀÉ®×±¾ÂIªº¸ô®|¡A¬Û¹ïªº©Îµ´¹ïªº¡Aµø¥G°ò¥»ªº³]©w',
		'path.details' => '°²¦p°ò¥»³]©w¬°¬Û¹ïªº¡A³Q±¾ªº¸ô®|·|¦bºô¯¸¡ufileadmin¡vªº¤l¸ê®Æ§¨¤¤¡C§AÀ³¸Ó¿é¤J¡ufileadmin¡vªº¤l¸ê®Æ§¨¬°¸ô®|¡C¨Ò¦p¡A§A·Q±¾¤W¡ufileadmin/user_uploads/all¡vªº¦s¨úÅv¡A§A´NÀ³¸Ó¿é¤J¡uuser_uploads/all¡v§@¬°¸ô®|ªº­È¡C
°²¦p°ò¥»³]©w¬Oµ´¹ïªº¡A§AÀ³¸Ó¿é¤J¦øªA¾¹¤Wªºµ´¹ï¸ô®|¡A¨Ò¦p¡G¡u/home/ftp_upload¡v©Î¡uC:/home/ftp_upload¡v

<strong>¯d·N¡G</strong>¦b¥ô¦ó±¡ªp¤U¡AªÖ©wºô­¶¦øªA¾¹¹B¦æPHPªº¨Ï¥ÎªÌ¦³¸ô®|<em>³Ì°ò¥»</em>ªºÅª¨úÅv­­¡C°²¦p¨S¦³¡A±¾ÂI´N¤£·|¥X²{¡A¦Ó¥B¤£·|¨S¦³¥ô¦óÄµ§i¡C
°²¦p§A¦³°İÃD - ¯S§O¬Oµ´¹ï±¾ÂI - ¹Á¸Õ±¾¤J¤@¨Ç¡uÂ²³æ¡v¡A¦p¤@­Ó¦bfileadmin¤¤ªº¬Û¹ï¸ô®|¡C°²¦p¹B§@¨}¦n¡A¦A¹Á¸Õµ´¹ï¸ô®|¡C

§Aªº PHP ³]©w¤]³\\¦P¼Ë·|¥[¤W¨ä¥L­­¨î©ó§A¡A°²¦p±Ò°Ê¤FÃş¦ü¦w¥ş¼Ò¦¡(safe-mode)µ¥ªº¥\\¯à¡C¼Ë´N­n¥Î¬Û¹ï¸ô®|¡C',
		'hidden.description' => '¨Ï¥Î³o­Ó¿ï¶µ¨Ó¼È®ÉÃö³¬ÀÉ®×±¾ÂI¡C',
		'hidden.details' => '©Ò¦³¨Ï¥Î±¾ÂIªº«á¶Ô¨Ï¥ÎªÌ±N¤£¯à¦s¨úÂI¡C¥]¬A¤F¡uºŞ²z­û¡v¨Ï¥ÎªÌ¡C',
		'base.description' => '¨M©w¸ô®|Äæªº­È¬O³Q¬İ¬°¦øªA¾¹¤Wªºµ´¹ï¸ô®|©Î¬O¤@­Ó¬Û¹ï©óºô¯¸¤¤fileadminªº¤l¸ê®Æ§¨ªº¸ô®|',
	),
	'eu' => Array (
	),
	'bg' => Array (
		'.description' => 'Ôàéë çàêà÷âàíåòî (Filemounts) îïèñâà ôàéë ïúòÿ íà ñúğâúğà, îòíîñèòåëåí èëè àáñîëşòåí.',
		'title.description' => 'Âúâåäåòå èìå çà ôàéë çàêà÷âàíåòî (filemount)',
		'path.description' => 'Âúâåäåòå ïúòÿ íà ôàéë çàêà÷âàíåòî (filemount), îòíîñèòåëåí èëè àáñîëşòåí â çàâèñèìîñò îò íàñòğîéêèòå íà BASE.',
		'hidden.description' => 'Èçïîëçâàéòå òàçè îïöèÿ çà äà èçêëş÷èòå âğåìåííî ôàéë çàêà÷âàíåòî (filemount).',
		'hidden.details' => 'Âñè÷êè backend ïîòğåáèòåëè èçïîëçâàùè çàêà÷âàíåòî (mount), íÿìà äà èìàò ïîâå÷å äîñòúï. Òîâà âêëş÷âà è \'Àäìèí\' ïîòğåáèòåëèòå.',
		'base.description' => 'Îïğåäåëÿíå äàëè ñòîéíîñòòà íà ÏÚÒ (PATH) ïîëåòî ìîæå äà áúäå ğàçïîçíàòî êàòî àáñîëşòåí ïúò íà ñúğâúğà èëè êàòî îòíîñèòåëåí ïúò êúì ôàéëàäìèí/ ïîäïàïêà íà óåá ñòğàíèöàòà.',
	),
	'br' => Array (
		'.description' => 'Pontos-de-entrada descrevem um caminho de arquivo no servidor, relativo ou absoluto.',
		'.details' => 'Ao criar um registro de ponto-de-entrada e colocar uma referência a ele num grupo de usuário Administrador, você pode conceder acesso ao ponto-de-entrada a um usuário no módulo Arquivo>Lista.
Você deve criar ao menos um ponto-de-entrada com uma pasta \'_temp_\' nela se quiser que usuários sejam capazes de enviar arquivos através de um navegador web.
Pontos-de-entrada também podem configurar acesso a um caminho no servidor onde o usuário tenha acesso via FTP. Apenas lembre de criar permissões de arquivo corretamente no servidor para que o usuário do servidor (aquele que está rodando o PHP) tenha ao menos capacidade de leitura no diretório FTP.',
		'title.description' => 'Insira um título para o ponto-de-entrada',
		'path.description' => 'Insira o caminha para o ponto-de-entrada, relativo ou absoluto, dependendo das configurações da BASE.',
		'path.details' => 'Se BASE for configurada como relativa, o caminho montado será encontrado na subpasta "fileadmin/" do site. Então você deve inserir uma subpasta de "fileadmin/" como caminho. Por exemplo, se você quiser montar acesso para "fileadmin/user_uploads/all", então insira o valor "user_uploads/all" como o valor de PATH.
Se BASE for absoluta, você deve inserir o caminho absoluto no servidor. Por exemplo "/home/ftp_upload" ou "C:/home/ftp_upload".

<strong>Atenção:</strong> Em todo caso, certifique-se de que o usuário do servidor web rodando PHP tenha <em>ao menos</em> permissão de leitura no caminho. Se não, o ponto-de-entrada simplesmente não aparecerá, sem advertências.
Se você tiver problemas - especialmente com montagens absolutas - tente montar algo "simples", como um caminho relativo em "fileadmin".  Se isto funcionar corretamente, tente o caminho absoluto.

Sua configuração de PHP também pode impor outras restrições se características como \'safe-modo\' estiverem habilitadas. Então use caminhos relativos.',
		'hidden.description' => 'Use esta opção para desativar o ponto-de-entrada temporariamente.',
		'hidden.details' => 'Todos os usuários-administradores usando o ponto-de-entrada não terão mais acesso a ele. Isto inclui usuários \'Admin\'.',
		'base.description' => 'Determina se o valor do campo PATH deve ser reconhecido como um caminho absoluto no servidor ou um caminho relativo à pasta \'fileadmin/\' do site.',
	),
	'et' => Array (
	),
	'ar' => Array (
		'.description' => 'ãåêÉ ÇäåäáÇÊ ÊÙÈÑ Ùæ Ùåâ åÓÇÑ Çäåäá Ùäé ÇäÓêÑáÑ ÓèÇÁ æÓÈêÇ Ãè å×äâÇ',
		'.details' => 'Ùæ ×Ñêâ ÅæÔÇÁ ÓÌä äåäá ÑÇÈ× è èÖÙç ãæâ×É ÑÌèÙ áê åÌåèÙÉ ÇäåÓÊÎÏåêæ ÇäåÕååêæ. è êÊå Ğäã Ùæ ×Ñêâ ÇäÏÎèä Åäé Çäåäá ÇäÑÇÈ×  áê ÇäÌÇæÈ: åäá < ÇäâÇÆåÉ.
ÃæÊ åÍÊÇÌ äÅæÔÇÁ Ùäé ÇäÃâä åäá ÑÇÈ× èÇÍÏ ÈåÌäÏ ÅÓåç ÇäåÌäÏ ÇäåÄâÊ \'_temp_\'  .',
	),
	'he' => Array (
		'.description' => '××•×¦×‘×™ ×§×‘×¦×™× ××ª××¨×™× × ×ª×™×‘ ×§×‘×¦×™× ×¢×œ ×”×©×¨×ª, ×™×—×¡×™ ××• ××•×—×œ×˜.',
		'.details' => '×“×¨×š ×™×¦×™×¨×ª ×¨×©×•××” ×©×œ ××•×¦×‘ ×§×‘×¦×™× ×•×”× ×—×ª ×”×ª×™×™×—×¡×•×ª ××œ×™×• ×‘×§×‘×•×¦×ª ××©×ª××©×™ ×××©×§ ××—×•×¨×™, ×”× ×š ×™×›×•×œ ×œ××¤×©×¨ ×’×™×©×ª ××©×ª××©×™× ×œ××•×¦×‘ ×§×‘×¦×™× ×‘××•×“×•×œ "×§×•×‘×¥>×¨×©×™××”". ×¢×œ×™×š ×œ×™×¦×•×¨ ×•×œ×”×’×“×™×¨ ×œ×¤×—×•×ª ××•×¦×‘ ×§×‘×¦×™× ××—×“ ×¢× ×ª×™×§×™×™×” "_temp_" ×‘×ª×•×›×• ×× ×”× ×š ×¨×•×¦×” ×œ××¤×©×¨ ×œ××©×ª××©×™× ×œ×”×¢×œ×•×ª ×§×‘×¦×™× ×“×¨×š ×“×¤×“×¤×Ÿ ××™× ×˜×¨× ×˜. ××•×¦×‘×™ ×§×‘×¦×™× ×™×›×•×œ×™× ×’× ×œ×¢×¦×‘ ×’×™×©×” ×œ× ×ª×™×‘ ×¢×œ ×”×©×¨×ª, ××œ×™×• ×™×© ×œ××©×ª××© ×’×™×©×ª FTP. ×¦×¨×™×š ×œ×–×›×•×¨ ×¨×§ ×œ×”×’×“×™×¨ ×”×¨×©××•×ª ×§×‘×¦×™× ×¢×œ ×”×©×¨×ª ×‘×¦×•×¨×” × ×›×•× ×”, ×›×š ×©××©×ª××© ×©×œ ×”×©×¨×ª (×©×”-PHP ××©×ª××© ×‘×–×”×•×ª×•) × ×•×©× ×œ×¤×—×•×ª ×–×›×•×ª ×§×¨×™××” ×œ×ª×™×§×™×™×ª FTP ×–×•.',
		'title.description' => '×”×›× ×¡ ×©× ×©×œ ××•×¦×‘ ×§×‘×¦×™×.',
		'path.description' => '×”×›× ×¡ × ×ª×™×‘ ×©×œ ××•×¦×‘ ×§×‘×¦×™×, ×™×—×¡×™ ××• ××•×—×œ×˜, ×ª×œ×•×™ ×‘×”×’×“×¨×ª×• ×©×œ ×‘×¡×™×¡ (BASE).',
		'path.details' => '×‘××™×§×¨×” ×‘×• ×”×‘×¡×™×¡ (BASE) ××›×•×•×Ÿ ×œ×”×™×•×ª ×™×—×¡×™, ×”× ×ª×™×‘ ×©××•×¦×‘ × ××¦× ×‘×ª×ª-×ª×™×§×™×™×” "fileadmin/" ×©×œ ×”××ª×¨. ×œ×›×Ÿ ×¢×œ×™×š ×œ×¦×™×™×Ÿ ×ª×ª-×ª×™×§×™×™×” ×©×œ "fileadmin/" ×‘× ×ª×™×‘. ×œ×“×•×’××”, ×× ×‘×¨×¦×•× ×š ×œ×™×¦×•×¨ ××•×¦×‘ ×œ-"fileadmin/user_uploads/all/" ×¢×œ×™×š ×œ×¦×™×™×Ÿ "user_uploads/all" ×‘×ª×•×¨ ×¢×¨×š ×œ×©×“×” "× ×ª×™×‘". ×‘××™×“×” ×•×‘×¡×™×¡ (BASE) ×”×™× ×• ××•×—×œ×˜ - ×¢×œ×™×š ×œ×¦×™×™×Ÿ × ×ª×™×‘ ××•×—×œ×˜ ×¢×œ ×”×©×¨×ª, ×›×’×•×Ÿ "/home/ftp_upload" ××• "C:/home/ftp_upload".
<strong>×”×¢×¨×”:</strong>
×‘×›×œ ××§×¨×”, × × ×œ×•×•×“×¢ ×©××©×ª××© ×©×‘×–×”×•×ª×• ××©×ª××© ×”-PHP × ×•×©× ×œ×¤×—×•×ª ×–×›×•×ª ×§×¨×™××” ×œ× ×ª×™×‘. ×× ×œ× - ×”××•×¦×‘ ×¤×©×•×˜ ×œ× ×™×•×¤×™×¢, ×œ×œ× ×›×œ ××–×”×¨×”. ×× ×™×© ×‘×¢×™×•×ª , ×‘××™×•×—×“ ×¢× ××•×¦×‘×™× ××•×—×œ×˜×™× - × ×¡×” ×œ×¦×•×¨ ××•×¦×‘ ×¤×©×•×˜ ×™×•×ª×¨ - ×›××• ×™×—×¡×™ ×œ "fileadmin". ×× ×–×” ×¢×•×‘×“ ×›×¨×¦×•×™ - × ×¡×” × ×ª×™×‘ ××•×—×œ×˜.
×ª×¦×•×¨×ª PHP ×™×›×•×œ×” ×œ×”×˜×™×œ ×”×’×‘×œ×•×ª × ×•×¡×¤×•×ª. ×œ×›×Ÿ ×›×“××™ ×™×•×ª×¨ ×œ×”×©×ª××© ×‘× ×ª×™×‘×™× ×™×—×¡×™×™×.',
		'hidden.description' => '×”×©×ª××© ×‘××¤×©×¨×•×ª ×–×• ×›×“×™ ×œ×”×¤×•×š ××•×¦×‘ ×§×‘×¦×™× ×œ×œ× ×–××™×Ÿ ×–×× ×™×ª.',
		'hidden.details' => '×›×œ ××©×ª××©×™ ×××©×§ ×”××—×•×¨×™ ×œ× ×™×§×‘×œ×• ××™×©×•×¨ ×’×™×©×” ×™×•×ª×¨. ×–×” ×›×•×œ×œ ××©×ª××©×™ "Admin" ×’×.',
		'base.description' => '×§×•×‘×¢ ×× ×¢×¨×š ×©×œ ×©×“×” "× ×ª×™×‘" ×”×•× × ×ª×™×‘ ××•×—×œ×˜ ××• ×™×—×¡×™ ×œ×ª×™×§×™×™×” fileadmin.',
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