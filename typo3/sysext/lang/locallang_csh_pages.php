<?php
/**
 * Default  TCA_DESCR for "pages"
 * TYPO3 CVS ID: $Id$
 */

$LOCAL_LANG = Array (
	'default' => Array (
		'title.description' => 'Enter the title of the page or folder.',
		'title.syntax' => 'You must enter a page title. The field is required.',
		'.description' => 'A \'Page\' record usually represents a webpage in TYPO3. All pages has an id-number by which they can be linked and referenced. The \'Page\' record does not itself contain the content of the page - for this purpose you should create \'Page content\' records.',
		'.details' => 'Depending on the \'Type\' of the page, it may also represent a general storage for database elements in TYPO3. In that case it is not necessarily available as a webpage but only internally in the page tree as a place to store items such as users, subscriptions etc.
The pages table is the very backbone in TYPO3. All records editable by the mainstream modules in TYPO3 must \'belong\' to a page. It\'s exactly like files and folders on your computers harddrive. 
The pages are organized in a tree structure which is not only a very handy way of organizing in general but also a optimal reflection of how you should organize the pages on your website. And thus you\'ll normally find that the page tree is a reflection of the website navigation itself.

Technically all database elements has a field \'uid\' which is a unique identification number. Further they must have a field \'pid\' which holds the uid-number of the page (page id) to which they belong. If the \'pid\' field is zero the record is found in the so called \'root\'. Only administrators are allowed access to the root and furthermore table records must be configured to either belonging to a page or being found in the root.',
		'doktype.description' => 'Select the page type. This affects whether the page represents a visible webpage or is used for other purposes.',
		'doktype.details' => 'The \'Standard\' type represents a webpage.
\'SysFolder\' represents a non-webpage - a folder acting as a storage for records of your choice.
\'Recycler\' is a garbage bin.

<B>Notice:</B> Each type usually has a specific icon attached. Also certain types may not be available for a user (so you may experience that some of the options is not available for you!). And finally each type is configured to allow only certain table records in the page (SysFolder will allow any record if you have any problems).',
		'TSconfig.description' => 'Page TypoScript configuration.',
		'TSconfig.details' => 'Basically \'TypoScript\' is a concept for entering values in a tree-structure. This is known especially in relation to creating templates for TYPO3 websites.
However the same principle for entering the hierarchy of values is used here to configure various features in relation to the backend, functions in modules, the Rich Text Editor etc. 
The resulting \'TSconfig\' for a page is actually an accumulation of all \'TSconfig\' values from the root of the page tree and outwards to the current page. And thus all subpages are affected as well. A print of the page TSconfig is available from the \'Page TSconfig\' menu in the \'Web>Info\' module (requires the extension "info_pagetsconfig" to be installed).
',
		'TSconfig.syntax' => 'Basic TypoScript syntax <em>without</em> \'Conditions\' and \'Constants\'.

It\'s recommended that only admin-users are allowed access to this field!',
	),
	'dk' => Array (
		'title.description' => 'Indtast titlen på siden eller mappen.',
		'title.syntax' => 'Du skal indtaste en sidetitel. Dette felt er påkrævet.',
		'.description' => 'Et "Side" element repræsenterer normalt en webside i TYPO3. Alle sider har et ID nummer med hvilket man kan referere eller linke til siderne. Side-elementet indeholder ikke selv sidens indhold - til dette formål bør du oprette "Indholdselementer".',
		'.details' => 'Afhængigt af sidens "Type" så kan en side også repræsentere en indholdsfolder for database elementer i TYPO3. I sådan et tilfælde er siden ikke nødvendigvis tilgængelig som en webside men kun internt i sidetræet som et sted, hvor elementer så som brugere, tilmeldinger etc. kan gemmes.
Side-tabellen er selve TYPO3s rygrad. Alle elementer (records) som kan redigeres med TYPO3 skal "tilhøre" en side. Det er præcis som med filer og mapper på din computers harddisk.
Siderne er organiseret i en træstruktur som ikke blot er en vældig praktisk metode til strukturering i almindelighed men som også er en optimal reflektion af, hvordan du bør organisere siderne på dit website. Og således vil du erfare at side-træet normalt er en reflektion af websitets navigationsstruktur.

Teknisk set har alle database elementer et felt, "uid", som er et unikt identifikationsnummer. Derudover skal alle elementer have et "pid" felt, som indeholder uid-nummeret på den side (page id) som de tilhører. Hvis pid-feltet er nul, så tilhører elementet "roden" af sidetræet. Det er kun administratorer, som har adgang til roden af sidetræet og desuden skal en tabel konfigureres til at tilhøre enten roden eller et sted i sidetræets grene.',
		'doktype.description' => 'Vælg sidens type. Dette påvirker hvorvidt siden repræsenterer en synlig webside eller bruges til andre formål.',
		'doktype.details' => '"Standard" typen repræsenterer en webside.
"SysFolder" repræsenterer en ikke-webside - en mappe som fungerer som opbevaringsplads for elementer efter dit valg.
"Recycler" er en skraldespand.

<b>Bemærk:</b> Hver type har normalt et særligt ikon tilknyttet. Desuden kan visse typer være utilgængelige for en bruger (så du kan opleve at nogle muligheder ikke er tilgængelige for dig!). Og endeligt så er hver type sat op til kun at tillade visse tabel-elementer på siden. (SysFolder vil dog tillade et hvilken som helst element hvis du skulle få nogle problemer).',
		'TSconfig.description' => 'TypoScript opsætningskode for siden.',
	),
	'de' => Array (
	),
	'no' => Array (
		'title.description' => 'Skriv inn tittelen på siden eller mappen.',
		'title.syntax' => 'Du må skrive inn en sidetittel. Dette feltet er påkrevd.',
		'.description' => 'Et "Side" element representerer normalt en webside i TYPO3. Alle sider har et ID nummer som kan benyttes for å referere eller linke til sidene. Side-elementet inneholder ikke sidens innhold selv – til dette formålet bør du opprette "Innholdselementer".',
		'.details' => 'Avhengig av sidens "Type" kan siden også være en innholdsmappe for database elementer i TYPO3. I slike tilfeller er ikke siden nødvendigvis tilgjengelig som en webside, men kun internt i sidetreet som et sted hvor elementer som brukere, innmeldinger etc. kan lagres.
Side-tabellen er selve TYPO3s ryggrad. Alle element (records) som kan redigeres med TYPO3 skal "tilhøre" en side. Det er akkurat som med filer og mapper på din datamaskins harddisk.
Sidene er organisert i en trestruktur som ikke bare er veldig praktisk metode for strukturering i alminnelighet, men som også er en optimal gjengivelse av hvordan du bør organisere sidene på webområdet ditt. På den måten vil du se at sidetreet normalt er en refleksjon av webområdets navigasjonstruktur.

Teknisk sett har alle database elementer et felt, "uid", som er et unikt identifikasjonsnummer. Utover dette må alle elementer ha et "pid" felt som inneholder uid-nummeret for den siden (page id) som de tilhører. Hvis pid-feltet er null tihører elementet "roten" av sidetreet. Det er kun administratorer som har tilgang til roten av sidetreet og videre må en tabell konfigureres til å tilhøre enten roten eller et sted i sidetreets grener.',
		'doktype.description' => 'Velg sidetype. Dette påvirker hvorvidt siden er en synlig webside eller brukes til andre formål.',
		'doktype.details' => '"Standard" typen er en webside.
"SysFolder" er en ikke-webside – en mappe som fungerer som oppbevaringsplass for valgfrie elementer.
"Papirkurv" er også et valg.

<b>Merk:</b> Hver type har normalt et eget ikon tilknyttet. Dessuten kan visse typer være utilgjengelige for en bruker (så du kan oppleve at noen muligheter ikke er tilgjengelige for deg!). Hver type er satt opp til kun å tillate visse tabellelementer på siden. (SysFolder vil dog tillate et hvilket som helst element hvis du skulle få problemer).',
		'TSconfig.description' => 'TypoScript konfigurasjonskode for siden.',
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
		'title.description' => 'Anna sivun tai hakemiston nimi.',
		'title.syntax' => 'Sinun tulee antaa nimi sivulle. Tämä on pakollinen tieto.',
		'.description' => '\'Sivu\'-tietue esittää useimmiten kokonaista www-sivua Typo3ssa. Kaikilla sivuilla on ID-numero, jonka perusteella niihin voidaan linkittää ja viitata. \'Sivu\'-tietueessa ei itsessään ole sisältöä, vaan tähän tarkoitukseen sinun on luotava \'Sivun sisältö\' -tietueita.',
		'.details' => '\'Tyyppi\' (\'\'Type\') valinnasta riippuen sivu voi toimia myös yleisenä Typo3 tietokannan elementtien varastona. Tässä tapauksessa sivut eivät ole välttämättä varsinaisia www-sivuston sivuja vaan sisäisiä sivuja puurakenteessa jonne on talletettu tietoja, kuten käyttäjät, linkit, linkkiluokat tms.
Sivutaulu on TYPO3n selkäranka. Kaikkien keskeisillä aliohjelmilla muokattavien tietojen TYPO3ssa on \'kuuluttavat\' sivulle. Tilanne on saman kaltainen kuin tiedostot ja hakemistot oman tietokoneesi kovalevyllä.
Sivut on järjestetty puurakenteeseen joka ei ole ainoastaan käytännöllinen menetelmä mutta  vastaa kuinka Sinun tulisi järjestää sivut  optimaalisesti www-sivustollesi. Näin huomaat normaalisti myös että puurakenne vastaa myös siirtymisiä www-sivustolla.

Teknisesti kaikissa tietokannan elementeissä on \'uid\' joka on yksilöllinen tunnus. Edelleen elementeillä tulee olla tieto \'pid\' joka sisältää kunkin sivun \'uid\'-tunnuksen jolle kukin elementti kuuluu. Jos \'pid\' tieto on nolla, tietoa kutsutaan silloin ns. juureksi (root). Vain pääkäyttäjillä on oikeus juuren käsittelyyn. Lisäksi kaikkien taulun tietojen tulee olla asetettuja kuulumaan joko johonkin sivuun tai juureen.',
		'doktype.description' => 'Valitse sivun tyyppi. Tämän tarkoituksena on määrittää näkyykö sivu tavallisena web-sivuna vai käytetäänkö sitä johonkin muuhun tarkoitukseen.',
		'doktype.details' => '\'Normaali\'-tyyppi on tavallinen esitettävä www-sivu.
\'SysFolder\' tarkoittaa ei-esitettävää sivua - se esittää hakemistoa jonne on tallennettu haluamiasi tietoja.
\'Recycler\' tarkoittaa roskakoria.

<B>Huomautus:</B>Eri tyypeillä on yleensä erilaiset ikonit. Kaikki tyypit eivät myöskään ole käytössä kaikilla käyttäjillä (voit ehkä huomata sen nytkin!). Ja lopuksi, erilaiset tyypit on konfiguroitu käyttämään ainoastaan määrittyjä taulutietoja sivuilla (SysFolder mahdollistaa minkä tahansa tiedon jos Sinulla on ongelmia).',
		'TSconfig.description' => 'Sivun TypoScript-asetukset.',
		'TSconfig.details' => 'Periaatteessa \'TypoScript\' on konsepti jolla annetaan arvoja puu-rakenteessa. Tämä tulee erityisesti esille luotaessa mallipohjia TYPO3 www-sivustoille.
Kuitenkin samaa periaatetta käytetään kun annetaan arvoja hierarkkisesti konfiguroitaessa erilaisia ominaisuuksia taustatoiminnoille, apuohjelmien toiminnalle jne.
Lopputuloksena syntyy sivulle \'TSconfig\' joka on itse asiassa kaikkien arvojen summa juuresta kullekin sivulle asti. Myös ko. sivun alasivut saavat vaikutuksen ylemmistä. Sivun TSconfig tiedoista saa tulosteen valikosta \'Sivun TSconfig\' (\'Page TSconfig\') \'Web>Info\' modulissa (lisäohjelma "info_pagetsconfig" tulee olla asennettuna).',
		'TSconfig.syntax' => 'Perus TypoScript syntaksi <em>ilman</em> \'Conditions\' ja \'Constants\'.

Suositellaan vain admin-käyttäjille pääsyä tähän tietoon!',
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
		'title.description' => 'Unesite naslov stranice ili pretinca',
		'title.syntax' => 'Obavezno je unjeti naslov stranice.',
		'.description' => 'Zapis \'stranica\' obièno predstavlja web stranicu u TYPO3 sustavu. Sve stranice imaju id broj pomoæu kojega mogu biti povezane i referencirane. Zapis \'Stranica\' ne obuhvaæa sam sadraj veæ je za to potrebno kreirati zapis \'Sadraj stranice\'.',
		'.details' => 'U zavisnosti od tipa stranice ona moe predstavljati repozitorij raznih podatakovnih elemenata u TYPO3 sustavu. U tom sluèaju nije nuno da predstavlja web stranicu veæ interno unutar stabla stranica moe predstavljati mjesto za pohranu raznih podataka kao što su korisnici, pretplate i slièno.
Stranice su duboko ukorijenjene u TYPO3. Svi zapisi koji se mogu editirati iz glavnih TYPO3 modula moraju pripadati nekoj stranici. To je vrlo slièno datotekama koje moraju pripadati pretincima.
Stranice su organizirane u stablo što nije samo prikladan naèin organiziranja podataka veæ i uobièajen naèin organiziranja stanica unutar web sjedišta. Takoğer, uobièajeno je da stablo stranica predstavlja i sam naèin organizacije navigacije.

Tehnièki gledano svi elementi baze imaju polje \'uid\' koje predstavlja jedinstveni identifikacioni broj. Takoğer moraju imati i polje \'pid\' koji sadri uid oznaku stranice kojoj odreğenmi element pripada. Ukoliko je \'pid\' oznaka nula zapis senalazi u poèetnoj (root) stranici. Samo administraotri imaju dozvolu pristupa poèetnoj stranici i osim toga zapisi moraju biti konfigurirani tako da pripadaju nekoj od stranica ili biti u poèetnoj stranici.',
		'doktype.description' => 'Selektirajte tip stranice. Ovo ima utjecaj na sadraj stranice, da li æe biti web stranica ili iskorištena za neku drugu svrhu.',
		'doktype.details' => 'Standardni tip predstavlja web stranicu.
\'SysFolder\' (sistemski pretinac) predstavlja repozitorij - pretinac u koji moete spremiti razne zapise prema vlastitom izboru.
\'Recycler\' predstavlja kanticu za smeæe, to jest obrisani sadraj.

<B>Napomena:</B> Svaki tip obièno je specificiran odreğenom ikonom. Takoğer, glavni tipovi mogu biti nedostupni za korisnika (tako da moete imati osjeæaj da vam neke od opcija nisu dostupne!). I na kraju svaki tip stranice omoguæava samo odreğene zapise na stranici (ukoliko imate bilo kakvih problema SysFolder omoguæava svaki zapis).',
		'TSconfig.description' => 'Stranica TypoScript konfiguracije.',
		'TSconfig.details' => 'U osnovi \'TypoScript\' je koncept za unos vrijednosti putem strukture stabla. Princip je poznat iz naèina kreiranja predloaka za TYPO3 web sjedište.
Takoğer, ovdje se primjenjuje jednak princip hijerarhijskog unosa vrijednosti za potrebe konfiguriranja razlièitih znaèajki u odnosu na osnovu sustava (backend), funkcije modula, WYWIWYG editora i slièno. 
Konaèni \'TSconfig\' stranice je rezultat akumuliranja svih \'TSconfig\' vrijednosti od poèetne stranice stabla prema trenutnoj stranici. Na taj naèin sve prethodne stranice u hijerarhiji utjeèu na konfiguraciju. Ispis stranice TSconfig moguæ je iz menija \'Stranica TSconfig\' u \'Web>Info\' modulu (zahtijeva da bude instalirano proširenje "info_pagetsconfig").',
		'TSconfig.syntax' => 'Osnovna TypoScript sintaksa <em>bez</em> \'uvjeta\' i \'konstanti\'.

Preporuèljivo je da samo korisnici s administratorskim dozvolama imaju pristup do ovog polja!',
	),
	'hu' => Array (
		'title.description' => 'Add meg az oldal vagy könyvtár címét.',
		'title.syntax' => 'Meg kell adnod egy oldalcímet. Ez a mezõ kötelezõ.',
		'.description' => 'A \'Page\' rekord általában egy weboldalt jelent a TYPO3-ban. Minden oldalnak van egy id-száma, amivel hivatkozni lehet rá. A \'Page\' rekord nem tartalmazza az oldal tartalmát - ezért létre kell hozni \'Oldal tartalom\' rekordokat.',
		'.details' => 'Az oldal típusától függõen ez is az adatbázis elemek tárolását
képviseli a TYPO3-ban. Abban az esetben , ha nem szükséges
az elérés weblapként, hanem csak belsõleg az oldalrendszeren
belül olyan tárolási hely gyanánt, mint felhasználók,
feliratkozások, stb.
Az oldalak táblája adja a TYPO3 gerincét. A TYPO3-ban
minden fõáramú modul által szerkeszthetõ rekord egy oldalhoz
\'tartozik\'. Pontosan úgy, mint a fájlok és könyvtárak a
merevlemezen.
Az oldalak egy faszerû struktúrába vannak rendezve, amely
nemcsak könnyen kezelhetõséget biztosít, hanem egy
optimális tükre annak, hogy hogyan kell az oldalakat
szervezni a webhelyen belûl. És így rájöhetünk arra, hogy a
fastruktúra magának a webhely navigációnak a
visszatükrözõdése.

Technikailag minden adatbázis elemnek van egy \'uid\' azaz
egyedi azonosító számot képviselõ mezõje. Továbbá ezek
az elemeknek rendelkezniük kell egy \'pid\' szülõazonosító
mezõvel, amely azonosítja azt az oldalt, ahova tartoznak.
Ha egy \'pid\' mezõ zérus, akkor a rekord az ún. \'gyökérben\'
található. A gyökérhez csak az adminisztrátorok férnek hozzá
illetve a táblarekordokat úgy kell konfigurálni, hogy vagy egy
oldalhoz vagy a gyökérhez tartozzanak.',
		'doktype.description' => 'Válaszd ki az oldal típusát. Ettõl függ, hogy az oldal látható vagy egyéb célokra szolgál.',
		'doktype.details' => 'A \'Standard\' típus egy weboldalt képvisel, a \'SysFolder\'
- amely nem weboldal - egy olyan könyvtár, amely az általad
kiválasztott rekordok kezelését végzi, a \'Recycler\' pedig
egy lomtár.

<B>Figyelem:</B> Minden típushoz egy speciális ikon van
csatolva. Vannak bizonyos, felhasználók által el nem érhetõ
típusok (a tapasztalat szerint olyanok, amit nem tudsz elérni).
És végül minden típus úgy van konfigurálva, hogy csak
az oldal bizonyos táblarekordjait engedélyezik (a SysFolder
engedélyez bármely rekordot probléma esetén).',
		'TSconfig.description' => 'Az oldal TypoScript beállítása.',
		'TSconfig.details' => 'Alapjában a \'TypoScript\' egy koncepció értékek elhelyezésére a 
fastruktúrában. Ez különösen jól ismert a TYPO3 weboldalak
és a sablonkészítés kapcsolatában.
Tehát ugyanaz az beviteli hierarchia alapelv használatos itt is,
amely beállít számos elemet a backenddel kapcsolatban,
mûveleteket a modulokban, a Rich Text Editor, stb.
Ez eredmény \'TSconfig\' egy oldalra nézve az gyökértõl az adott
oldalig az összes oldal \'TSconfig\' értékek halmozása. Így
minden aloldal is érintve van. A TSconfig oldal nyomata
elérhetõ az \'Oldal TSconfig\' menübõl, amely a \'Web>Info\'
modulban található (szükséges az "info_pagetsconfig"
kiterjesztés elõzetes telepítése).',
		'TSconfig.syntax' => 'Alap TypoScript szintaxis \'Feltételek\' és \'Konnstansok\' <em>nélkül</em>.

Javaslat: csak admin-felhasználóknak legyen engedélyezve a mezõ elérése!',
	),
	'gl' => Array (
	),
	'th' => Array (
	),
	'gr' => Array (
	),
	'hk' => Array (
		'title.description' => '¿é¤Jºô­¶©Î¸ê®Æ§¨ªº¦WºÙ',
		'title.syntax' => '§A¥²¶·­n¿é¤J¤@­Óºô­¶¦WºÙ¡C³oÄæ¬O¥²¶·ªº¡C',
		'.description' => '¦b TYPO3 ¤¤¤@­Ó¡uºô­¶¡v°O¿ı³q±`¥Nªí¤@­Óºô­¶¡C©Ò¦³ºô­¶³£¦³¤@­Ó½s¸¹¡A³z¹L³o­Ó½s¸¹ºô­¶¥i¥H³Q³sµ²©M°Ñ·Ó¡C¡uºô­¶¡v°O¿ı¥»¨­¤£§t¦³ºô­¶ªº¤º®e - ­n¹F¦¨³o¥Øªº§AÀ³¸Ó«Ø¥ß¡uºô­¶¤º®e¡v°O¿ı¡C',
		'.details' => 'µø¥Gºô­¶ªº¡uºØÃş¡v, ¥¦¤]¥i¥H¥Nªí¦b TYPO3 ¤¤¤@­Ó´¶³qªº¸ê®Æ®w¤¸¥óÀx¦s¡C¦b³o­Ó±¡ªp¤U¡A¥¦¤£¶·¬O¤@­Ó¥iÆ[¬İªººô­¶¡A¦Ó¥u¬Oºô­¶¾ğ¹Ï¤¤¤º¦b§@¬°Àx¦sª«¥ó¡]¨Ï¥ÎªÌ©M­q¾\\µ¥¡^ªº¦a¤è¡C
ºô­¶¸ê®Æªí¬O TYPO3 ªº¤¤¼Ï¡C©Ò¦³¥i¥H¸g TYPO3 ¥D¼Ò²Õ­×§ïªºªº°O¿ı¤@©w¡uÄİ©ó¡v¤@­Óºô­¶¡C´N¦n¹³§Aªº¹q¸£µwºĞ¤¤ªº¸ê®Æ§¨©MÀÉ®×¤@¼Ë¡C
ºô­¶³Q²ÕÂ´¦¨¤@­Ó¡u¾ğ¹Ï¡v¡A¥¦¤£³æ¬O¤@­Ó«D±`¤è«Kªº²ÕÂ´¡A¤]³Ì¦³®Ä¤Ï¬M§AÀ³¸Ó¦p¦ó²ÕÂ´§Aºô¯¸ªººô­¶¡C¦]¦¹¡A³q±`§A·|µo²{ºô­¶¾ğ¹Ï¤Ï¬Mºô¯¸¨­ªº¿ï³æ¾É¦V¡C
§Ş³N¤W¡A©Ò¦³ªº¸ê®Æ®w¤¸¥ó³£¦³¤@­Ó¡uUID¡vÄæ¡A¥¦¬O¿W¤@ªº¡u»{ÃÒ¡v½s¸¹¡C°£¦¹¤§¥~¡A¥¦­Ì¤@©w¦³¤@­Ó¡uPID¡vÄæ¡A¥¦§t¦³¥L­Ì©ÒÄİªººô­¶ªº½s¸¹¡C°²¦p¡uPID¡vÄæ¬O¹s¡A°O¿ı´N¬O¦b³QºÙ¬°¡u®Ú¡v¤¤¡C¥u¦³ºŞ²z­û³Q³\\¥i¦s¨ú®Ú¡A¦Ó¥B¸ê®Æªí°O¿ı¤@©w­n³]©w¬°Äİ©ó¤@­Óºô­¶©Î¦ì©ó®Ú¤¤¡C',
		'doktype.description' => '¿ï¾Üºô­¶ºØÃş¡C³o¼vÅTºô­¶¬O¤@­Ó¥iÆ[¬İªººô­¶©Î¬O§@¬°¨ä¥L¥Î³~¡C',
		'doktype.details' => '¡u¼Ğ·Ç¡vÃş«¬¥Nªí¤@­Óºô­¶¡C
¡u¨t²Î¸ê®Æ§¨¡v¥Nªí¤@­Ó«Dºô­¶ - ¤@­Ó¸ê®Æ§¨§@¬°§A¿ï¾Üªº°O¿ıªºÀx¦s¡C
¡u¦^¦¬µ©¡v¬O¤@­Ó©U§£½c¡C

<B>¯d·N¡G</B>¨C¤@Ãş³q±`³£ªş¦³¤@­Ó¯S©wªº¹Ï¥Ü¡C¦P®É¹ï¬Y­Ó¥Î®a¥i¯à¤£¯à¨Ï¥Î¬Y¨ÇºØÃş¡]¦]¦¹§A¥i¯à¸g¾ú¨ì§A¤£¯à¨Ï¥Î¬Y¨Ç¿ï¶µ¡^¡C³Ì«á¨C¤@­ÓÃş«¬³Q³]©w¨Ó®e³\\¥u¦³¬Y¨Ç¸ê®Æªí°O¿ı¦s©óºô­¶¤¤¡]¨t²Î¸ê®Æ§¨·|®e³\\¥ô¦ó°O¿ı¡A°²¦p§A¦³¥ô¦ó°İÃD¡^¡C',
		'TSconfig.description' => 'ºô­¶ TypoScript ³]©w¡C',
		'TSconfig.details' => '°ò¥»¤W¡@¡uTypoScript¡v¬O¤@­Ó¦b¾ğ¹Ï¤¤¿é¤J¼Æ­Èªº·§©À¡C¯S§O¬OÃö«Y¨ì¬° TYPO3 ºô¯¸«Ø¥ß¼Ëª©®É¡C
µM¦Ó¬Û¦Pªº­ì«h¾A¥Î©ó¿é¤J¼Æ­È¨t²Î¥H³]©w»P«á¶Ô¨t²Î¤¤¤£¦Pªº¥\\¯à, ¼Ò²Õªº¥\\¯à, ¦h¥\\¯à¤å¦r½s¿è¾¹µ¥¡C
¤@­Óºô­¶ªº³Ì²×¡uTSconfig¡v¹ê¦b¬O²Ö¿n¤F±qºô­¶¾ğ¹Ïªº®Ú­¶¦Ü²{¦³ºô­¶ªº©Ò¦³¡uTSconfig¡v¼Æ­È¡C¦Ó¦]¦¹©Ò¦³°Æ­¶³£³Q¼vÅT¡C§A¥i¥H¦b¡uWeb>Info¡v¡]§A»İ­n¦w¸Ë "info_pagetsconfig" ©µ¦ù¤u¨ã¡^¼Ò²Õ¤¤ªº¡uPage TSconfig¡v¿ï³æ¤¤¨ì¤@­Óºô­¶ªº ¡uTSconfig¡v°Æ¥»¡C',
		'TSconfig.syntax' => '°ò¥»ªº TypoScript »yªk <em>¨S¦³</em>¡u±ø¥ó¡v©M¡u±`­È¡v¡C

§Ú­Ì«ØÄ³¥u¦³ºŞ²z­û¯Å¨Ï¥ÎªÌ¤~³Q³\\¥i¦s¨ú³o­ÓÄæ¥Ø¡C',
	),
	'eu' => Array (
	),
	'bg' => Array (
		'title.description' => 'Âúâåäåòå çàãëàâèå íà ñòğàíèöàòà èëè ïàïêàòà.',
		'title.syntax' => 'Òğÿáâà äà âúâåäåòå çàãëàâèå íà ñòğàíèöàòà. Ïîëåòî å èçèñêâàíå.',
		'.description' => 'Çàïèñ \'Ñòğàíèöà\' îáèêíîâåííî ïğåäñòàâëÿâà óåá ñòğàíèöà â TYPO3. Âñè÷êè ñòğàíèöè èìàò id-íîìåğ ïî êîéòî ìîãàò äà áúäàò ñâúğçâàíè èëè ğåôåğåíöèğàíè. Çàïèñà \'Ñòğàíèöà\' íå ñúäúğæà â ñåáå ñè ñúäúğæàíèåòî íà ñòğàíèöàòà - çà òàçè öåë òğÿáâà äà ñúçäàäåòå çàïèñ \'Ñúäúğæàíèå íà ñòğàíèöàòà\'.',
		'doktype.description' => 'Èçáåğåòå òèï íà ñòğàíèöàòà. Òîâà çàñÿãà äàëè ñòğàíèöàòà ùå ïğåäñòàâëÿâà âèäèìà óåá ñòğàíèöà èëè å ñå èçïîëçâà çà äğóãè íóæäè.',
		'TSconfig.description' => 'Êîíôèãóğàöèÿ íà TypoScript ñòğàíèöàòà.',
		'TSconfig.syntax' => 'Îñíîâíèÿ TypoScript ñèíòàêñèñ <em>áåç</em> \'Óñëîâèÿ\' and \'Êîíñòàíòè\'.

Ïğåïîğú÷èòåëíî å ñàìî Àäìèí ïîòğåáèòåëè äà èìàò ğàçğåøåí äîñòúï äî òîâà ïîëå!',
	),
	'br' => Array (
		'title.description' => 'Digite o título da página ou pasta.',
		'title.syntax' => 'Você precisa digitar um título para a página. Este campo é obrigatório.',
		'.description' => 'Um registro do tipo \'Página\' normalmente representa uma página web no TYPO3. Todas as páginas possuem um número de identificação através do qual elas podem ser acessadas e referenciadas. O registro \'página\' não armazena em si o conteúdo da página - para essa finalidade você deve criar registros do tipo \'Conteúdo de página\'.',
		'.details' => 'Dependendo do \'Tipo\' da página, ela pode representar um armazém de elementos da base de dados do TYPO3. Neste caso, não estará necessariamente disponível como uma página web, mas apenas internamente na árvore de páginas, como um local para armazenar ítens como usuários, assinaturas, etc. A tabela de páginas é a estrutura básica no TYPO3. Todos os registros editáveis pelos módulos principais do TYPO3 precisam \'pertencer\' a uma página. É exatamente como os arquivos e pastas do disco rígido de seu computador.
As páginas são organizadas dentro de uma estrutura de árvore, a qual é não apenas uma forma bastante prática de organização de modo geral, mas também uma representação apropriada de como você deve organizar as páginas dentro do seu site. Desta forma, você perceberá que a árvore de páginas é uma representação da própria estrutura de navegação do site.

Tecnicamente, todos os elementos do banco de dados possuem um campo \'uid\', que contém um número de identificação único. Além disso, possuem também um campo \'pid\', que contém o número de identificação da página à qual eles pertencem. Se o campo \'pid\' é zero, o registro se encontra na chamada \'raiz\'. Apenas usuários-administradores possuem acesso à raiz e portanto os registros precisam ser configurados, ou para pertencer a uma página, ou para serem encontrados na raiz.',
		'doktype.description' => 'Selecione o tipo da página. Esta opção define se a página representa uma página web visível ou se é usada para outras finalidades.',
		'doktype.details' => 'O tipo \'Padrão\' representa uma página web. \'Pasta de Sistema\' representa uma página não-web - uma pasta atuando como armazém de registros à sua escolha. \'Lixeira\' representa um local para exclusão de registros.

<B>Nota:</B> Cada tipo normalmente possui um ícone específico. Além disso, certos tipos podem não estar disponíveis a todos os usuários (assim talvez algumas das opções podem não estar disponíveis para você!). E finalmente, cada tipo é configurado para permitir apenas certas opções de registros dentro da página (o tipo \'Pasta de Sistema\' permitirá qualquer registro, caso você encontre algum problema).',
		'TSconfig.description' => 'Configuração TypoScript da página.',
		'TSconfig.details' => 'Basicamente, \'TypoScript\' é um conceito para atribuir valores em uma estrutura de árvore. Este conceito é observado especialmente durante a criação de modelos para sites TYPO3.
Entretanto, o mesmo princípio para atribuição da hierarquia de valores é usado aqui para configurar várias características em relação à administração do site, às funções dos módulos, ao editor Rich Text, etc. O \'TSconfig\' resultante para a página é na verdade o acúmulo de todos os valores \'TSconfig\' herdados desde a raiz do site até a página atual. E da mesma forma, todas as subpáginas serão afetadas também. 
Uma visualização do TSconfig da página está disponível no menu \'TSconfig da página\', presente no módulo \'Web>Info\' (requer a instalação da extensão "info_pagetsconfig").',
		'TSconfig.syntax' => 'Sintaxe básica TypoScript <em>sem</em> \'Condições\' e \'Constantes\'.

É recomendado que apenas os usuários-administradores tenham acesso permitido a este campo!',
	),
	'et' => Array (
	),
	'ar' => Array (
	),
	'he' => Array (
		'title.description' => '×”×›× ×¡ ×©× ×”×“×£ ××• ×”×ª×™×§×™×™×”',
		'title.syntax' => '×¢×œ×™×š ×œ×”×–×™×Ÿ ×©× ×”×“×£. ×”×©×“×” × ×“×¨×©.',
		'.description' => '×¨×©×•××” ××¡×•×’ "×“×£" ×‘×“×¨×š ×›×œ×œ ××™×™×¦×’ ×“×£ ××™× ×˜×¨× ×˜ ×‘-TYPO3. ×œ×›×œ ×”×“×¤×™× ×™×© ××¡×¤×¨ ×–×”×•×ª ×œ×¤×™×• × ×™×ª×Ÿ ×œ×¦×•×¨ ×§×™×©×•×¨×™× ××œ×™×”×. ×¨×©×•××” ××¡×•×’ "×“×£" ×œ× ××›×™×œ×” ×ª×•×›×Ÿ ×“×£ ×‘×ª×•×›×”. ×‘×©×‘×™×œ ×–×” ×¢×œ×™×š ×œ×¦×•×¨ ×¨×©×•××•×ª ××¡×•×’ "×ª×•×›×Ÿ ×“×£".',
		'.details' => '×ª×œ×•×™ ×‘×¡×•×’ ×©×œ ×“×£, ×–×” ×™×›×•×œ ×œ×”×•×•×ª ××§×•× ××™×—×¡×•×Ÿ ×›×œ×œ×™ ×œ×¤×¨×˜×™ ×××’×¨ ××™×“×¢ ×‘-TYPO3. ×‘××§×¨×” ×›×–×”, ×–×” ×œ× ×‘×”×›×¨×— ×–××™×Ÿ ×‘×ª×•×¨ ×“×£ ××™× ×˜×¨× ×˜, ××œ× ×¤× ×™××™×ª, ×‘×ª×•×¨ ××§×•× ×œ××—×¡×Ÿ ×¤×¨×™×˜×™× ×›××•: ××©×ª××©×™×, ×›×ª×•×‘×•×ª ×•×›×•\'. ×˜×‘×œ×ª ×“×¤×™× ×”×™× "×¢××•×“ ×”×©×“×¨×”" ×‘-TYPO3. ×›×œ ×¨×©×•××•×ª ×©× ×™×ª× ×™× ×œ×¢×¨×™×›×” ×¦×¨×™×›×™× "×œ×”×©×ª×™×™×š" ×œ×“×£. ×–×” ×‘×“×™×•×§ ×›××• ×§×‘×¦×™× ×•×ª×™×§×™×•×ª ×¢×œ ×”×“×™×¡×§ ×”×§×©×™×—. ×“×¤×™× ××¡×•×“×¨×™× ×‘×¦×•×¨×ª ×¢×¥, ×©×”×™× ×œ× ×¨×§ ×¦×•×¨×” × ×•×—×”, ××œ× ×’× ××¨××” ××™×š ×¢×œ×™×š ×œ×¡×“×¨ ×“×¤×™× ×‘××ª×¨.
×˜×›× ×™×ª, ×œ×›×œ ×”×¨×›×™×‘×™× ×‘×××’×¨ ××™×“×¢ ×™×© ×©×“×” "uid" ×©×”×•× ××¡×¤×¨ ×–×™×”×•×™ ×™×™×—×•×“×™. ×‘× ×•×¡×£ ×™×© ×’× ×©×“×” "pid" ×©××›×™×œ ××¡×¤×¨ uid ×©×œ ×“×£, ××œ×™×• ×”×•× ×©×™×™×š. ×× pid ×”×•× 0 - ×”×¨×©×•××” × ××¦××ª ×‘×©×•×¨×©. ×¨×§ ×× ×”×œ×™× ×¨×©××™× ×œ×’×©×ª ×œ×©×•×¨×© ×•×¢×œ ×›×œ ×¨×©×•××•×ª ×œ×”×©×ª×™×™×š ××• ×œ×©×•×¨×© ××• ×œ×“×£.',
		'doktype.description' => '×‘×—×¨ ×¡×•×’ ×”×“×£. ×–×” ××’×“×™×¨ ×× ×”×“×£ ××™×™×¦×’ ×“×£ ××™× ×˜×¨× ×˜ ××• ×‘×©×™××•×© ×œ×¦×¨×›×™× ××—×¨×™×.',
		'doktype.details' => '×¡×•×’ "×¨×’×™×œ" ××™×™×¦×’ ×“×£ ××™× ×˜×¨× ×˜. "SysFolder" ××™× ×• ××™×™×¦×’ ×“×£ ××™× ×˜×¨× ×˜, ××œ× ×ª×™×§×™×™×” ×‘×” × ×™×ª×Ÿ ×œ××—×¡×Ÿ ×¨×©×•××•×ª ×©×ª×‘×—×¨. "×¡×œ ××™×—×–×•×¨" - ×”×•× ×¤×— ××©×¤×”.
<b>×”×¢×¨×”:</b>×œ×›×œ ×¡×•×’ ×‘×“×¨×š ×›×œ×œ ×™×© ×¡××œ ××•×¦××“. ×™×›×•×œ ×œ×”×™×•×ª ×©×¡×•×’×™× ××¡×•×™××™× ×œ× × ×’×™×©×™× ×œ××©×ª××©. ×•×œ×‘×¡×•×£, ×›×œ ×¡×•×’ ××•×’×“×¨ ×›×š, ×©×”×•× ×××¤×©×¨ ×¨×§ ×¨×©×•××•×ª ××¡×•×™××•×ª ×‘×“×£ (SysFolder ×™××¤×©×¨ ×›×œ ×¨×©×•××”, ×× ×™×© ×œ×›× ×‘×¢×™×” ×¢× ×–×”).',
		'TSconfig.description' => '×ª×¦×•×¨×ª TypoScript ×©×œ ×“×£.',
		'TSconfig.details' => '×¢×§×¨×•× ×™×ª, "TypoScript" ×”×™× ×©×™×˜×” ×œ×¡×“×¨ ×¢×¨×›×™× ×‘×¦×•×¨×ª ×¢×¥. ×–×” ×™×“×•×¢ ×‘×¢×™×§×¨ ×™×—×¡×™×ª ×œ×™×¦×™×¨×ª ×ª×‘× ×™×•×ª ×œ××ª×¨×™ TYPO3. ×‘×›×œ ×–××ª, ×‘××•×ª×• ×¢×™×§×¨×•×Ÿ ×œ×¡×“×¨ ×¢×¨×›×™× ××©×ª××©×™× ×’× ×›×“×™ ×œ×”×’×“×™×¨ ×ª×›×•× ×•×ª ×©×•× ×•×ª ×‘×××©×§ ×”××—×•×¨×™, ×¤×¢×•×œ×•×ª ×‘××•×“×•×œ×™×, Rich Text Editor, ×•×›×•\' ×œ×‘×¡×•×£, "TSconfig" ×©×œ ×“×£ ×”×™× ×• ×‘×¢×¦× ××•×¡×£ ×©×œ ×›×œ ××¨×›×™ "TSconfig" ××”×©×•×¨×© ×©×œ ×¢×¥ ×“×¤×™× ×•×¢×“ ×œ×“×£ × ×•×›×—×™. ×œ×›×Ÿ, ×›×œ ×ª×ª-×“×¤×™× ××•×©×¤×¢×™× ×’× ×”×. ×”×§×•×“ ×©×œ "TSconfig" × ×™×ª×Ÿ ×œ×¨××•×ª ×‘×ª×¤×¨×™×˜ -"TSconfig ×©×œ ×“×£"×‘××•×“×•×œ "××™× ×˜×¨× ×˜->××™×“×¢" (×“×•×¨×© ×ª×•×¡×¤×ª "info_pagetsconfig" ×©×ª×”×™×” ××•×ª×§× ×ª)',
		'TSconfig.syntax' => '×ª×—×‘×™×¨ TypoScript ×‘×¡×™×¡×™ <em>×œ×œ×</em> "Conditions" ×•-"Constants".
××•××œ×¥ ×©×¨×§ ×œ×× ×”×œ×™× ×ª×”×™×” ×’×™×©×” ×œ×©×“×” ×–×”.',
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