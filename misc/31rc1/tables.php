<?

/*
Customization: 

This document (tables.php) holds all customized values 
that makes Typo work against a given MySQL database. 

This is the setup of tables / fields 

tables.php Typo3 version 3.1b3, 20-2-2001
*/








/*
************************
$pagesTypes

this array defines the different types of pages (the obligatory table "pages"!)
Here you can set the icon and especially you can define which tables are allowed on a certain pagetype (doktype)
NOTE: The "default" entry in the $pagesTypes-array is the "base" for all types, and for every 
type the entries simply overrides the entries in the "default" type!!
************************
*/

$pagesTypes = Array(
	"3" => Array(
		"icon" => "pages_link.gif",
		"allowedTables" => ""
	),
	"4" => Array(
		"icon" => "pages_shortcut.gif",
		"allowedTables" => ""
	),
	"5" => Array(
		"icon" => "pages_notinmenu.gif"
	),
	"199" => Array(		// TypoScript: Limit is 200. When the doktype is 200 or above, the page WILL NOT be regarded as a "page" by TypoScript. Rather is it a system-type page
		"type" => "sys",
		"icon" => "spacer_icon.gif",
		"allowedTables" => ""
	),
	"254" => Array(	
		"type" => "sys",
		"icon" => "sysf.gif",
		"allowedTables" => "*"
	),
	"255" => Array(		// Typo3: Doktype 255 is a recycle-bin. Documents will be put here when deleted. See manual
		"type" => "sys",
		"icon" => "recycler.gif",
		"allowedTables" => "*"
	),
	"default" => Array(
		"type" => "web",
		"icon" => "pages.gif",
		"allowedTables" => "pages,tt_content,tt_links,tt_board,tt_guest,tt_address,tt_calender,tt_products,sys_template,sys_domain,sys_note",
		"onlyAllowedTables" => "0"
	)
);









/*****************************************************************************************************************

$tc:

this array defines the tables and the relationship between them 
and how the fields in tables are treated and so on


See documentation for the syntax and list of required tables/fields!


tables:
 must contain:
   uid, pid, (tstamp??)

pages:
must ALSO contain
  deleted, (doktype??)
  
  
NOTE: You cannot use the single-quote (') in textlabels for tablenames or fields (unless you escape it like this: (\')). This will not work out when the label is transferred to JavaScript in ti_inc.php. You MAY use double-qoute (")!
		Labels for item-lists may include single-quotes and they MUST NOT be escaped!!
NOTE: The (default) icon for a table is defined 1) as a giffile named "gfx/i/[tablename].gif" or 2) as the value of [table][ctrl][iconfile]
NOTE: [table][ctrl][rootLevel] goes NOT for pages. Apart from that if rootLevel is true, records can ONLY be created on rootLevel. If it's false records can ONLY be created OUTSIDE rootLevel

******************************************************************************************/ 

/*


Languages:

All labels are split by "|".
The order of the languages can be seen in typo3/lang/lang.php

Currently it's:

default | Danish | German | Norwegian | Italian | French

*/


// ******************************************************************
// This is the mandatory pages table. 
// ******************************************************************

$tc[pages] = Array (
	"ctrl" => Array (
		"label" => "title",
		"tstamp" => "tstamp",
		"sortby" => "sorting",
		"title" => "Page|Side|Seite|Side|Pagina|Page",
		"type" => "doktype",
		"delete" => "deleted",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"enablecolumns" => Array (
			"fe_group" => "fe_group",
			"disabled" => "hidden",
			"starttime" => "starttime",
			"endtime" => "endtime"
		),
		"mainpalette" => 1,
		"useColumnsForDefaultValues" => "doktype,fe_group,hidden"
	),
	"interface" => Array (
		"showRecordFieldList" => "doktype,title,alias,hidden,starttime,endtime,fe_group,url,target,no_cache,shortcut,keywords,description,newUntil,lastUpdated,cache_timeout",
		"maxDBListItems" => 30,
		"maxSingleDBListItems" => 50
	),
	"columns" => Array (	
		"doktype" => Array (
			"exclude" => 1,	
			"label" => "Type:|Type:|Typ:|Type:|Tipo:|Type:",
			"config" => Array (
				"type" => "select",	
				"items" => Array (	
					Array("Standard|Standard|Standard|Standard|Standard|Standard", "1"),
					Array("Advanced|Avanceret|Erweitert|Avansert|Avanzato|Avancé", "2"),
					Array("External URL|Extern URL|Externe URL|Ekstern URL|URL esterno|URL externe", "3"),
					Array("Shortcut|Genvej|Shortcut|Snarvei|Collegamento|Raccourci", "4"),
					Array("Not in menu|Ikke i menu|Nicht im Menü|Ikke I Meny|Non presente nel menu|Absent du menu", "5"),
					Array("-----", "--div--"),
					Array("Spacer|Mellemrum|Abstand|Mellomrom|Spacer|Délimiteur", "199"),
					Array("SysFolder|SysFolder|SysOrdner|SysMappe|Cartella di sistema|Dossier système", "254"),
					Array("Recycler|Papirkurv|Papierkorb|Papirkurv|Cestino|Corbeille", "255")
				),
				"default" => "1"
			)
		),
		"hidden" => Array (
			"exclude" => 1,	
			"label" => "Hide page:|Skjul side:|Seite verstecken:|Skjul side:|Nascondi pagina:|Cacher page:",
			"config" => Array (
				"type" => "check",
				"default" => "1"
			)
		),
		"starttime" => Array (
			"exclude" => 1,	
			"label" => "Start:|Start:|Start:|Start:|Inizio:|Lancement:",
			"config" => Array (
				"type" => "input",
				"size" => "7",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0"
			)
		),
		"endtime" => Array (
			"exclude" => 1,	
			"label" => "Stop:|Stop:|Stop:|Stopp:|Fine:|Arrêt:",
			"config" => Array (
				"type" => "input",
				"size" => "7",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0",
				"range" => Array (
					"upper" => mktime(0,0,0,12,31,2020),
					"lower" => mktime(0,0,0,date("m")-1,date("d"),date("Y"))
				)
			)
		),
		"layout" => Array (
			"exclude" => 1,
			"label" => "Layout:|Layout:|Layout:|Layout:|Layout:|Préparation-type:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("Normal|Normal|Normal|Normal|Normale|Normal", "0"),
					Array("Layout 1|Layout 1|Layout 1|Layout 1|Layout 1|Préparation-type 1", "1"),
					Array("Layout 2|Layout 2|Layout 2|Layout 2|Layout 2|Préparation-type 2", "2"),
					Array("Layout 3|Layout 3|Layout 3|Layout 3|Layout 3|Préparation-type 3", "3")
				),
				"default" => "0"
			)
		),
		"fe_group" => Array (
			"exclude" => 1,	
			"label" => "Access:|Adgang:|Zugriff:|Adgang:|Accesso:|Accès:",
			"config" => Array (
				"type" => "select",	
				"items" => Array (
					Array("", 0),
					Array("Hide at login|Skjul ved login|Beim Login verstecken|Skjul ved login|Nascondo al login|Cacher à la connexion", -1),
					Array("__Usergroups:__|__Brugergrupper:__|__Benutzergruppen:__|__Brukergrupper:__|__Gruppo utenti:__|__Groupes utilisateurs:__", "--div--")
				),
				"foreign_table" => "fe_groups"
			)
		),
		"title" => Array (
			"label" => "Pagetitle:|Sidetitel:|Seitentitel:|Sidetittel:|Titolo pagina:|Titre visible sur le côté:",
			"config" => Array (
				"type" => "input",	
				"size" => "30",
				"max" => "256",
				"eval" => "required"
			)
		),
		"subtitle" => Array (
			"exclude" => 1,	
			"label" => "Subtitle:|Undertitel:|Untertitel:|Undertittel:|Sottotitolo:|Sous-titre:",
			"config" => Array (
				"type" => "input",	
				"size" => "30",
				"max" => "256",
				"eval" => ""
			)
		),
		"target" => Array (
			"exclude" => 1,	
			"label" => "Target:|Target:|Target:|Target:|Obiettivo:|Cible:",
			"config" => Array (
				"type" => "input",	
				"size" => "7",
				"max" => "20",
				"eval" => "trim",
				"checkbox" => ""
			)
		),
		"alias" => Array (
			"label" => "Alias:|Alias:|Alias:|Alias:|Alias:|Alias:",
			"config" => Array (
				"type" => "input",	
				"size" => "10",
				"max" => "20",
				"eval" => "nospace,alphanum_x,lower,unique"
			)
		),
		"url" => Array (
			"label" => "URL:|URL:|URL:|URL:|URL:|URL:",
			"config" => Array (
				"type" => "input",		
				"size" => "25",
				"max" => "80",
				"eval" => "trim"
			)
		),
		"urltype" => Array (
			"label" => "Type:|Type:|Typ:|Type:|Tipo:|Type:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("", "0"),
					Array("http://", "1"),
					Array("ftp://", "2"),
					Array("mailto:", "3")
				),
				"default" => "1"
			)
		),
		"lastUpdated" => Array (
			"exclude" => 1,	
			"label" => "Last updated:|Sidst opdateret:|Letzte Änderung:|Sist oppdatert:|Ultimo aggiornamento:|Dernière mise à jour:",
			"config" => Array (
				"type" => "input",
				"size" => "12",
				"max" => "20",
				"eval" => "datetime",
				"checkbox" => "0",
				"default" => "0"
			)
		),
		"newUntil" => Array (
			"exclude" => 1,	
			"label" => "\'New\' until:|\'Ny\' indtil:|\'Neu\' bis:|\'Ny\' inntil:|\'Nuovo\' dal|\'Nouveau\' jusqu\'à:",
			"config" => Array (
				"type" => "input",
				"size" => "7",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0"
			)
		),
		"cache_timeout" => Array (
			"exclude" => 1,	
			"label" => "Cache expires:|Cachen udløber:|Cache verfällt:|Cachen utløper:|Scadenza cache:|Renouvellement du cache:",
			"config" => Array (
				"type" => "select",
				"items" => Array (	
					Array("Default|Standard|Standard|Standard|Predefinito|Par défaut", 0),
					Array("1 min|1 min|1 Min|1 min|1 min|1 min", 60),
					Array("5 min|5 min|5 Min|5 min|5 min|5 min", 5*60),
					Array("15 min|15 min|15 Min|15 min|15 min|15 min", 15*60),
					Array("30 min|30 min|30 Min|30 min|30 min|30 min", 30*60),
					Array("1 hour|1 time|1 Stunde|1 time|1 ora|1 heure", 60*60),
					Array("4 hours|4 timer|4 Stunden|4 timer|1 ore|4 heures", 4*60*60),
					Array("1 day|1 dag|1 Tag|1 dag|1 giorno|1 jour", 24*60*60),
					Array("2 days|2 dage|2 Tage|2 dager|2 giorni|2 jours", 2*24*60*60),
					Array("7 days|7 dage|7 Tage|7 dager|7 giorni|7 jours", 7*24*60*60),
					Array("1 month|1 måned|1 Monat|1 måned|1 mese|1 mois", 31*24*60*60)
				),
				"default" => "0"
			)
		),
		"no_cache" => Array (
			"exclude" => 1,	
			"label" => "No cache:|Ingen cache:|Nicht cachen:|Ingen cache:|No cache:|Pas de cache:",
			"config" => Array (
				"type" => "check"
			)
		),
		"no_search" => Array (
			"exclude" => 1,	
			"label" => "No search:|Ingen søgning:|Nicht suchen:|Ingen søking:|No ricerca:|Pas de recherche:",
			"config" => Array (
				"type" => "check"
			)
		),
		"shortcut" => Array (
			"label" => "Shortcut to page:|Genvej til side:|Shortcut zur Seite:|Snarvei til side:|Collegamento alla pagina:|Raccourci vers page:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "db",
					"allowed" => "pages",
				"size" => "3",
				"maxitems" => "1",
				"minitems" => "1",
				"show_thumbs" => "1"
			)
		),
		"keywords" => Array (
			"exclude" => 1,	
			"label" => "Keywords (,):|Nøgleord (,):|Stichworte (,):|Nøkkelord (,):|Parole chiavi:|Mots-clé (,):",
			"config" => Array (
				"type" => "text",
				"cols" => "40",
				"rows" => "3"
			)
		),
		"description" => Array (
			"exclude" => 1,	
			"label" => "Description:|Beskrivelse:|Beschreibung:|Beskrivelse:|Descrizione:|Descrition:",
			"config" => Array (
				"type" => "input",		
				"size" => "40",
				"max" => "80",
				"eval" => "trim"
			)
		),
		"media" => Array (
			"exclude" => 1,	
			"label" => "Files:|Filer:|Dateien:|Filer:|Files:|Fichiers:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "gif,jpg,jpeg,tif,bmp,pcx,html,htm,ttf,txt,css",
				"max_size" => "500",
				"uploadfolder" => "uploads/media",
				"show_thumbs" => "1",
				"size" => "3",
				"maxitems" => "5",
				"minitems" => "0"
			)
		)
	),
	"types" => Array (					
		"1" => Array("showitem" => "hidden, doktype;;2;button, title;;3, subtitle"),
		"2" => Array("showitem" => "hidden, doktype;;2;button, title;;3, subtitle, keywords, description, media"),
		"3" => Array("showitem" => "hidden, doktype, title;;3, url;;4"),
		"4" => Array("showitem" => "hidden, doktype, title;;3, shortcut"),
		"5" => Array("showitem" => "hidden, doktype;;2;button, title;;3, subtitle"),
		"199" => Array("showitem" => "hidden, doktype, title"),
		"254" => Array("showitem" => "hidden, doktype, title"),
		"255" => Array("showitem" => "hidden, doktype, title")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "starttime,endtime,fe_group"),
		"2" => Array("showitem" => "layout, lastUpdated, newUntil, no_search"),
		"3" => Array("showitem" => "alias, target, no_cache, cache_timeout"),
		"4" => Array("showitem" => "urltype")
	)
);




// ******************************************************************
// This is the standard TypoScript content table, tt_content
// ******************************************************************

$tc[tt_content] = Array (
	"ctrl" => Array (
		"label" => "header",
		"sortby" => "sorting",
		"tstamp" => "tstamp",
		"title" => "Pagecontent|Sideindhold|Seiteninhalt|Sideinnhold|Contenuto pagina|Contenu de la page",
		"delete" => "deleted",
		"type" => "CType",
		"enablecolumns" => Array (
			"fe_group" => "fe_group",
			"disabled" => "hidden",
			"starttime" => "starttime",
			"endtime" => "endtime"
		),
		"typeicon_column" => "CType",
		"typeicons" => Array (
			"header" => "tt_content_header.gif",
			"textpic" => "tt_content_textpic.gif",
			"image" => "tt_content_image.gif",
			"bullets" => "tt_content_bullets.gif",
			"table" => "tt_content_table.gif",
			"splash" => "tt_content_news.gif",
			"uploads" => "tt_content_uploads.gif",
			"multimedia" => "tt_content_mm.gif",
			"menu" => "tt_content_menu.gif",
			"list" => "tt_content_list.gif",
			"mailform" => "tt_content_form.gif",
			"search" => "tt_content_search.gif",
			"login" => "tt_content_login.gif",
			"shortcut" => "tt_content_shortcut.gif",
			"script" => "tt_content_script.gif",
			"html" => "tt_content_html.gif"
		),
		"mainpalette" => "1",
		"thumbnail" => "image",
		"useColumnsForDefaultValues" => "colPos"
	),
	"interface" => Array (
		"showRecordFieldList" => "CType,header,header_link,bodytext,image,imagewidth,imageorient,media,records,colPos,starttime,endtime,fe_group"
	),
	"columns" => Array (	
		"CType" => Array (
			"label" => "Type:|Type:|Typ:|Type:|Tipo:|Type:",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("Header|Overskrift|Überschrift|Overskrift|Intestazione|Titre", "header"),
					Array("Text|Tekst|Text|Tekst|Testo|Texte", "text"),
					Array("Text w/image|Tekst m/Billede|Text m/Bild|Tekst m/bilde|Testo con immagini|Texte w/image", "textpic"),
					Array("Image|Billede|Bild|Bilde|Immagine|Image", "image"),
					Array("Bullet list|Punktliste|Punktliste|Punktliste|Elenco puntato|Liste à puces", "bullets"),
					Array("Table|Tabel|Tabelle|Tabell|Tavola|Table", "table"),
					Array("Filelinks|Fillinks|Dateilinks|Filhyperkoblinger|Link su file|Lien vers fichier", "uploads"),
					Array("Multimedia|Multimedie|Multimedia|Multimedia|Multimedia|Multimedia", "multimedia"),
					Array("Form|Formular|Formular|Formular|Form|Formulaire", "mailform"),
					Array("Search|Søgefelt|Suchen|Søk|Ricerca|Recherche", "search"),
					Array("__Advanced:__|__Avancerede:__|__Erweitert:__|__Avansert:__|__Avanzate:__|__Avancée__:", "--div--"),
					Array("Login|Login|Login|Login|Login|Identifiant", "login"),
					Array("Textbox|Tekstboks|Textbox|Tekstboks|Box di testo", "splash"),
					Array("Menu/Sitemap|Menu/Oversigt|Menü/Sitemap|Meny/Oversikt|Menu / mappa sito|Menu / Plan site", "menu"),
					Array("Insert records|Indsæt emner|Datensatz einfügen|Sett inn emner|Inserimento record|Insérer enregistrements", "shortcut"),
					Array("List of records|Liste af emner|Datensatz-Liste|Liste av emner|Lista dei record|Liste d'enregistrements", "list"),
					Array("Script|Script|Skript|Skript:|Script|Scripts", "script"),
					Array("HTML|HTML|HTML|HTML|HTML|HTML", "html")
				),
				"default" => "text"
			)
		),
		"hidden" => Array (
			"exclude" => 1,	
			"label" => "Hide:|Skjul:|Verstecken:|Skjul:|Nascondi:|Cacher:",
			"config" => Array (
				"type" => "check"
			)
		),
		"starttime" => Array (
			"exclude" => 1,	
			"label" => "Start:|Start:|Start:|Start:|Inizio:|Lancement:",
			"config" => Array (
				"type" => "input",
				"size" => "7",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0"
			)
		),
		"endtime" => Array (
			"exclude" => 1,	
			"label" => "Stop:|Stop:|Stop:|Stopp:|Fine:|Arrêt:",
			"config" => Array (
				"type" => "input",
				"size" => "7",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0",
				"range" => Array (
					"upper" => mktime(0,0,0,12,31,2020),
					"lower" => mktime(0,0,0,date("m")-1,date("d"),date("Y"))
				)
			)
		),
		"fe_group" => Array (
			"exclude" => 1,	
			"label" => "Access:|Adgang:|Zugriff:|Adgang:|Accesso:|Accès:",
			"config" => Array (
				"type" => "select",	
				"items" => Array (
					Array("", 0),
					Array("Hide at login|Skjul ved login|Beim Login verstecken|Skjul ved login|Nascondi al login|Cacher à la connexion", -1),
					Array("__Usergroups:__|__Brugergrupper:__|__Benutzergruppen:__|__Brukergrupper:__|__Gruppo Utenti:__|__Groupes utilisateurs:__", "--div--")
				),
				"foreign_table" => "fe_groups"
			)
		),
		"layout" => Array (
			"exclude" => 1,
			"label" => "Layout:|Layout:|Layout:|Layout:|Layout:|Préparation-type:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("Normal|Normal|Normal|Normal|Normale|Normal", "0"),
					Array("Layout 1|Layout 1|Layout 1|Layout 1|Layout 1|Préparation-type 1", "1"),
					Array("Layout 2|Layout 2|Layout 2|Layout 2|Layout 2|Préparation-type 2", "2"),
					Array("Layout 3|Layout 3|Layout 3|Layout 3|Layout 3|Préparation-type 3", "3")
				),
				"default" => "0"
			)
		),
		"colPos" => Array (
			"exclude" => 1,	
			"label" => "Columns:|Spalte:|Spalten:|Spalte:|Colonne:|Colonnes:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("Normal|Normal|Normal|Normal|Normale|Normal", "0"),
					Array("Left|Venstre|Links|Venstre|Sinistra|Gauche", "1"),
					Array("Right|Højre|Rechts|Høyre|Destra|Droite", "2"),
					Array("Border|Margin|Rand|Marg|Bordo|Bordure", "3")
				),
				"default" => "0"
			)
		),
		"date" => Array (
			"exclude" => 1,	
			"label" => "Date:|Dato:|Datum:|Dato:|Data:|Date:",
			"config" => Array (
				"type" => "input",
				"size" => "7",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0"
			)
		),
		"header" => Array (
			"label" => "Header:|Overskrift:|Überschrift:|Overskrift:|Intestazione:|Titre:",
			"config" => Array (
				"type" => "input",
				"max" => "256"
			)
		),
		"header_position" => Array (
			"label" => "Align:|Justering:|Justierung:|Justering:|Allineamento:|Alignement:",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("", ""),
					Array("Center|Center|Mitte|Senter|Centrato|Centré", "center"),
					Array("Right|Højre|Rechts|Høyre|Destra|Aligner à droite", "right"),
					Array("Left|Venstre|Links|Venstre|Sinistra|Aligner à gauche", "left")
				),
				"default" => ""
			)
		),
		"header_link" => Array (
			"label" => "Link:|Link:|Link:|Link:|Link:|Lien:",
			"config" => Array (
				"type" => "input",		
				"size" => "15",
				"max" => "80",
				"checkbox" => "",
				"eval" => "trim"
			)
		),
		"header_layout" => Array (
			"exclude" => 1,
			"label" => "Type:|Type:|Typ:|Type:|Tipo:|Type:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("Normal|Normal|Normal|Normal|Normale|Normal", "0"),
					Array("Layout 1|Layout 1|Layout 1|Layout 1|Layout 1|Préparation-type 1", "1"),
					Array("Layout 2|Layout 2|Layout 2|Layout 2|Layout 2|Préparation-type 2", "2"),
					Array("Layout 3|Layout 3|Layout 3|Layout 3|Layout 3|Préparation-type 3", "3"),
					Array("Layout 4|Layout 4|Layout 4|Layout 4|Layout 4|Préparation-type 4", "4"),
					Array("Layout 5|Layout 5|Layout 5|Layout 5|Layout 5|Préparation-type 4", "5"),
					Array("Hidden|Skjult|Versteckt|Skjult|NAscosto|Caché", "100")
				),
				"default" => "0"
			)
		),
		"subheader" => Array (
			"exclude" => 1,	
			"label" => "Subheader:|Manchet:|Untertitel:|Undertittel:|Sottointestazione:|Sous-titre:",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"max" => "256"
			)
		),
		"bodytext" => Array (
			"label" => "Text:|Tekst:|Text:|Tekst:|Testo:|Texte:",
			"config" => Array (
				"type" => "text",
				"cols" => "48",
				"rows" => "5"
			)
		),
		"text_align" => Array (
			"exclude" => 1,	
			"label" => "Align:|Justering:|Justierung:|Justering:|Allineamento:|Alignement:",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("", ""),
					Array("Center|Center|Mitte|Senter|Centrato|Centré", "center"),
					Array("Right|Højre|Rechts|Høyre|Destra|Aligner à droite", "right"),
					Array("Left|Venstre|Links|Venstre|Sinistra|Aligner à gauche", "left")
				),
				"default" => ""
			)
		),
		"text_face" => Array (
			"exclude" => 1,
			"label" => "Fontface:|Skrifttype:|Schrift:|Skrifttype:|Tipo font:|Police de caractères:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("Default|Standard|Standard|Standard|Predefinito|Par défaut", "0"),
					Array("Times", "1"),
					Array("Verdana", "2"),
					Array("Arial", "3")
				),
				"default" => "0"
			)
		),
		"text_size" => Array (
			"exclude" => 1,
			"label" => "Size:|Størrelse:|Grösse:|Størrelse:|Dimensione:|Taille:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("Default|Standard|Standard|Standard|Predefinito|Valeur par défaut", "0"),
					Array("1: Small|1: Lille|1: Klein|Liten|1: Piccolo|1: Petite", "1"),
					Array("2: Medium|2: Medium|2: Mittel|Medium|2: Medio|2: Moyenne", "2"),
					Array("3: Large|3: Stor|3: Gross|Stor|3: Grande|3: Grande", "3"),
					Array("4: Header 1|4: Overskrift 1|4: Überschrift 1|Overskrift 1|4: Intestazione 1|4: Titre 1", "4"),
					Array("5: Header 2|5: Overskrift 2|5: Überschrift 2|Overskrift 2|5: Intestazione 2|5: Titre 2", "5"),
					Array("Default +1|Standard +1|Standard +1|Standard +1|Predefinito +1|Valeur par défaut +1", "10"),
					Array("Default -1|Standard -1|Standard -1|Standard -1|Predefinito -1|Valeur par défaut -1", "11")
				),
				"default" => "0"
			)
		),
		"text_color" => Array (
			"exclude" => 1,
			"label" => "Color:|Farve:|Farbe:|Farge:|Colore:|Couleur:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("Default|Standard|Standard|Standard|Predefinito|Valeur par défaut", "0"),
					Array("Color 1|Farve 1|Farbe 1|Farge 1|Colore 1|Couleur 1", "1"),
					Array("Color 2|Farve 2|Farbe 2|Farge 2|Colore 2|Couleur 2", "2"),
					Array("None|Ingen|Keine|Ingen|Nessuno|Aucune", "200"),
					Array("-----","--div--"),
					Array("Black|Sort|Schwarz|Svart|Nero|Noir", "240"),
					Array("White|Hvid|Weiss|Hvit|Bianco|Blanc", "241"),
					Array("Dark gray|Mørkegrå|Dunkelgrau|Mørkegrå|Grigio scuro|Gris sombre", "242"),
					Array("Gray|Grå|Grau|Grå|Grigio|Gris clair", "243"),
					Array("Silver|Lysegrå|Silber|Lysegrå|Argento|Argent", "244"),
					Array("Red|Rød|Rot|Rød|Rosso|Rouge", "245"),
					Array("Navy blue|Mørk blå|Marine Blau|Marineblå|Blu|Bleu marine", "246"),
					Array("Yellow|Gul|Gelb|Gul|Giallo|Jaune", "247"),
					Array("Green|Grøn|Grün|Grønn|Verde|Vert", "248"),
					Array("Olive|Oliven|Olivgrün|Oliven|Verde oliva|Kaki", "249"),
					Array("Maroon|Rødbrun|Rotbraun|Rødbrun|Marrone|Maron", "250")
				),
				"default" => "0"
			)
		),
		"text_properties" => Array (
			"exclude" => 1,
			"label" => "Properties:|Egenskaber:|Eigenschaften:|Egenskaper:|Proprieta:|Propriétés:",
			"config" => Array (
				"type" => "check",
				"items" => Array (	
					Array("Bold|Fed|Fett|Fet||Gras", ""),
					Array("Italics|Kursiv|Kursiv|Kusiv||Italiques", ""),
					Array("Underline|Understreg|Unterstrichen|Understrek||Souligné", ""),
					Array("Uppercase|Store bogstaver|Grossbuchstaben|Store bokstaver|Maiuscolo|Majuscules", "")
				),
				"cols" => 4
			)
		),
		"image" => Array (
			"label" => "Images:|Billeder:|Bilder:|Bilder:|Immagini:|Images:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "gif,jpg,jpeg,tif,bmp,pcx,pdf,ai,tga,png",
				"max_size" => "1000",
				"uploadfolder" => "uploads/pics",
				"show_thumbs" => "1",
				"size" => "3",
				"maxitems" => "200",
				"minitems" => "0"
			)
		),
		"imagewidth" => Array (
			"exclude" => 1,	
			"label" => "Width (pixels):|Bredde (pixels):|Breite (pixels):|Bredde (pixels):|Larghezza (pixel):|Largeur (pixels):",
			"config" => Array (
				"type" => "input",	
				"size" => "4",
				"max" => "4",
				"eval" => "int",
				"checkbox" => "0",
				"range" => Array (
					"upper" => "999",
					"lower" => "25"
				),
				"default" => 0
			)
		),
		"imageheight" => Array (
			"exclude" => 1,	
			"label" => "Height (pixels):|Højde (pixels):|Höhe (Pixel):|Høyde (pixels):|Altezza(pixel):|Hauteur (pixels):",
			"config" => Array (
				"type" => "input",	
				"size" => "4",
				"max" => "4",
				"eval" => "int",
				"checkbox" => "0",
				"range" => Array (
					"upper" => "700",
					"lower" => "25"
				),
				"default" => 0
			)
		),
		"imageorient" => Array (
			"label" => "Position:|Position:|Position:|Posisjon:|Posizione:|Position:",
			"config" => Array (
				"type" => "select",	
				"items" => Array (
					Array("Above, center|Over, center|Oben mittig|Over, senter|Centrato|Au dessus, centré", 0),
					Array("Above, right|Over, højre|Oben rechts|Over, høyre|Destra|Au dessus, à droite", 1),
					Array("Above, left|Over, venstre|Oben links|Over, venstre|Sinistra|Au dessus, à gauche", 2),
					Array("Below, center|Under, center|Unten mittig|Under, senter|Centrato alla fine|En dessous, centré", 8),
					Array("Below, right|Under, højre|Unten rechts|Under, høyre|Destra alla fine|En dessous, à droite", 9),
					Array("Below, left|Under, venstre|Unten links|Under, venstre|Sinistra alla fine|En dessous, à gauche", 10),
					Array("In text, right|I tekst, højre|Im Text rechts|I tekst, høyre|Destra nel testo|Dans le texte, à droite", 17),
					Array("In text, left|I tekst, venstre|Im Text links|I tekst, venstre|Sinistra nel testo|Dans le texte, à gauche", 18),
					Array("__No wrap:__|__Ingen ombryd:__|__Kein Umbruch:__|__Ingen pakning:__|__Senza contorno:__|__Pas de retour à la ligne__", "--div--"),
					Array("In text, right|I tekst, højre|Im Text rechts|I tekst, høyre|Destra nel testo|Dans le texte, à droite", 25),
					Array("In text, left|I tekst, venstre|Im Text links|I tekst, venstre|Sinistra nel testo|Dans le texte, à gauche", 26)
				),
				"default" => "8"
			)
		),
		"imageborder" => Array (
			"exclude" => 1,	
			"label" => "Border:|Kant:|Rahmen:|Ramme:|Bordo:|Bordure:",
			"config" => Array (
				"type" => "check"
			)
		),
		"image_noRows" => Array (
			"exclude" => 1,	
			"label" => "No rows:|Ingen rækker:|Keine Reihen:|Ingen rekker:|No colonne:|Pas de ligne:",
			"config" => Array (
				"type" => "check"
			)
		),
		"image_link" => Array (
			"exclude" => 1,	
			"label" => "Link:|Link:|Link:|Link:|Link:|Lien:",
			"config" => Array (
				"type" => "input",		
				"size" => "15",
				"max" => "80",
				"checkbox" => "",
				"eval" => "trim"
			)
		),
		"image_zoom" => Array (
			"exclude" => 1,	
			"label" => "Click-enlarge:|Klik-forstør:|Klick-vergrössern:|Klikk-forstør:|Clicca per allargare:|Cliquer pour agrandir:",
			"config" => Array (
				"type" => "check"
			)
		),
		"image_effects" => Array (
			"exclude" => 1,	
			"label" => "Effects:|Effekter:|Effekte:|Effekter:|Effetti:|Effets:",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("(None)|(Ingen)|(kein)|(Ingen)|(Nessuno)|(Aucun)", 0),
					Array("Rotate 90 CW|Rotér 90 med uret|Drehen 90 mit Uhr|Roter 90 med klokken|Rotazione 90 gr.|Rotation 90° (sens des aiguilles d'une montre)", 1),
					Array("Rotate -90 CCW|Rotér 90 mod uret|Drehen 90 gegen Uhr|Roter 90 mot klokken|Rotazione -90 gr.|Rotation -90° (sens contraire)", 2),
					Array("Rotate 180|Rotér 180|Drehen 180|Roter 180|Rotazione 180 gr.|Rotation 180 ° (tour complet)", 3),
					Array("Grayscale|Gråtone|Graubild|Gråtone|Scala di grigi|Nuances de gris", 10),
					Array("Sharpen|Skarpere|Schärfen|Skarpere|Schiarito|Plus précis", 11),
					Array("Normalize|Normalisér|Normalisieren|Normaliser|Normalizzato|Normaliser", 20),
					Array("Contrast|Kontrast|Kontrast|Kontrast|Contrastato|Plus de contraste", 23),
					Array("Brighter|Lysere|Heller|Lysere|Luminoso|Plus clair", 25),
					Array("Darker|Mørkere|Dunkler|Mørkere|Scuro|Plus sombre", 26)
				)
			)
		),
		"image_frames" => Array (
			"exclude" => 1,	
			"label" => "Frames:|Rammer:|Rahmen:|Ramme:|Frames:|Frames:",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("(None)|(Ingen)|(kein)|(Ingen)|(Nessuno)|(Aucun)", 0),
					Array("Frame 1|Ramme 1|Rahmen 1|Ramme 1|Frame 1|Frame 1", 1),
					Array("Frame 2|Ramme 2|Rahmen 2|Ramme 2|Frame 2|Frame 2", 2),
					Array("Frame 3|Ramme 3|Rahmen 3|Ramme 3|Frame 3|Frame 3", 3),
					Array("Frame 4|Ramme 4|Rahmen 4|Ramme 4|Frame 4|Frame 4", 4),
					Array("Frame 5|Ramme 5|Rahmen 5|Ramme 5|Frame 5|Frame 5", 5),
					Array("Frame 6|Ramme 6|Rahmen 6|Ramme 6|Frame 6|Frame 6", 6),
					Array("Frame 7|Ramme 7|Rahmen 7|Ramme 7|Frame 7|Frame 7", 7),
					Array("Frame 8|Ramme 8|Rahmen 8|Ramme 8|Frame 8|Frame 8", 8)
				)
			)
		),
		"image_compression" => Array (
			"exclude" => 1,	
			"label" => "Compr:|Kompr:|Kompr:|Kompr:|Compress.|Compr:",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("Default|Standard|Standard|Standard|Predefinito|Valeur par défaut", 0),
					Array("Dont change!|Ingen ændringer!|Nicht ändern|Ikke endre!|Non cambiare !|Ne pas modifier!", 1),
					Array("GIF/256", 10),
					Array("GIF/128", 11),
					Array("GIF/64", 12),
					Array("GIF/32", 13),
					Array("GIF/16", 14),
					Array("GIF/8", 15),
					Array("PNG", 39),
					Array("PNG/256", 30),
					Array("PNG/128", 31),
					Array("PNG/64", 32),
					Array("PNG/32", 33),
					Array("PNG/16", 34),
					Array("PNG/8", 35),
					Array("JPG/Very High|JPG/Meget høj|JPG/sehr hoch|JPG/Meget høy|JPG/Altissima risoluzione|JPG/Très haute résolution", 21),
					Array("JPG/High|JPG/Høj|JPG/hoch|JPG/Høy|JPG/Alta risoluzione|JPG/Haute résolution", 22),
					Array("JPG/Medium|JPG/Medium|JPG/mittel|JPG/Medium|JPG/Media risoluzione|JPG/Résolution moyenne", 24),
					Array("JPG/Low|JPG/Lav|JPG/niedrig|JPG/Lav|JPG/Bassa risoluzione|JPG/Basse résolution", 26),
					Array("JPG/Very Low|JPG/Meget lav|JPG/sehr niedrig|JPG/Meget lav|JPG/Bassissima risoluzione|JPG/Très basse résolution", 28)
				)
			)
		),
		"imagecols" => Array (
			"label" => "Columns:|Kolonner:|Spalten:|Kolonner:|Colonne:|Colonnes",
			"config" => Array (
				"type" => "select",	
				"items" => Array (	
					Array("1", 0),
					Array("2", 2),
					Array("3", 3),
					Array("4", 4),
					Array("5", 5),
					Array("6", 6),
					Array("7", 7),
					Array("8", 8)
				),
				"default" => 0
			)
		),
		"imagecaption" => Array (
			"label" => "Caption:|Billedtekst:|Bildtext:|Bildetekst:|Caption:|Titre:",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "3"
			)
		),
		"imagecaption_position" => Array (
			"exclude" => 1,	
			"label" => "Align:|Justering:|Justierung:|Justering:|Alliniamento:|Alignement:",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("", ""),
					Array("Center|Center|Mitte|Senter|Centrato|Centré", "center"),
					Array("Right|Højre|Rechts|Høyre|Destra|Aligner à droite", "right"),
					Array("Left|Venstre|Links|Venstre|Sinistra|Aligner à gauche", "left")
				),
				"default" => ""
			)
		),
		"cols" => Array (
			"label" => "Table Columns:|Tabelkolonner:|Tabellenspalten:|Tabellkolloner:|Colonne Tabella:|Colonnes du frame:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("Auto|Auto|Auto|Auto|Auto|Auto", "0"),
					Array("1", "1"),
					Array("2", "2"),
					Array("3", "3"),
					Array("4", "4"),
					Array("5", "5"),
					Array("6", "6"),
					Array("7", "7"),
					Array("8", "8"),
					Array("9", "9")
				),
				"default" => "0"
			)
		),
		"pages" => Array (
			"label" => "Startingpoint:|Udgangspunkt:|Ausgangspunkt:|Utgangspunkt:|Punto di partenza:|Point de démarrage:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "db",
					"allowed" => "pages",
				"size" => "3",
				"maxitems" => "22",
				"minitems" => "0",
				"show_thumbs" => "1"
			)
		),
		"recursive" => Array (
			"exclude" => 1,	
			"label" => "Recursive:|Rekursivt:|Recursive:|Rekursivt:|Recorsivita:|Niveaux d\'aborescence:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("", "0"),	
					Array("1 level|1 niveau|1 Ebene|1 nivå|1 livello|1 niveau", "1"),
					Array("2 levels|2 niveauer|2 Ebenen|2 nivåer|2 livello|2 niveaux", "2"),
					Array("3 levels|3 niveauer|3 Ebenen|3 nivåer|3 livello|3 niveaux", "3"),
					Array("4 levels|4 niveauer|4 Ebenen|4 nivåer|4 livello|4 niveaux", "4"),
					Array("Infinite|Uendeligt|Unendlich|Uendeligt|Infinito|Illimité", "250")
				),
				"default" => "0"
			)
		),
		"menu_type" => Array (
			"label" => "Type:|Type:|Typ:|Type:|Tipo:|Type:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("Menu of these pages|Menu af disse sider|Menü dieser Seiten|Meny av disse sider|Menu di questa pagina|Menu des pages", "0"),
					Array("Menu of subpages to these pages|Menu af undersider til disse sider|Menü der Unterseiten|Meny av undersider og disse sider|Menu di sottopagina di questa pagina|Menu des sous-pages", "1"),
					Array("Menu of subpages to these pages + sections|Menu af undersider til disse sider + sektioner|Menü der Unterseiten (mit Seiteninhalt)", "7"),
					Array("Sitemap|Side-oversigt (Sitemap)|Sitemap|Oversikt|Mappa del sito|Plan du site", "2"),
					Array("Section index (pagecontent w/Index checked)|Sektions-oversigt (sideindhold m/Index afkrydset)|Abschnittsübersicht (mit Seiteninhalt)", "3"),
					Array("Overview of these pages (with description)|Oversigt over disse sider (med beskrivelse)|Übersicht der Unterseiten (mit Beschreibung)", "4"),
					Array("Recently updated pages|Senest opdaterede sider|Geänderte Seiten", "5"),
					Array("Related pages (based on keywords)|Relaterede sider (baseret på nøgleord)|Verwandte Seiten (nach Stichworten)", "6")
				),
				"default" => "0"
			)
		),
		"list_type" => Array (
			"label" => "List of elements:|Liste af elementer:|Liste der Elemente:|Liste av elementer:|Lista degli elementi:|Liste des éléments:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("Addresses|Adresser|Adressen|Adresser|Indirizzi|Adresses", "0"),
					Array("Links|Links|Links|Hyperkobling|Links|Liens", "1"),
					Array("Guestbook|Gæstebog|Gästebuch|Gjestebok|Libro Visitatori|Livre d'or", "3"),
					Array("Board|Forum|Forum|Forum|Board|Forum", "4"),
					Array("Products|Produkter|Produkte|Produkter|Prodotti|Produits", "5"),
					Array("To-Do|To-Do|To-Do|To-Do|Promemoria|Liste des tâches restant à effectuer", "6"),
					Array("Calendar|Kalender|Kalender|Kalender|Calendario|Calendrier", "7")
				),
				"default" => "0"
			)
		),
/*		"form_type" => Array (
			"exclude" => 1,	
			"label" => "Formtype:|Form type:|Formular Typ:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("", ""),
					Array("Formmail", "formmail.php"),
					Array("Database", "index.php")
				),
				"default" => ""
			)
		),*/
		"select_key" => Array (
			"exclude" => 1,	
			"label" => 'CODE:|KODE:|CODE:|KODE:|CODICE:|CODE:',  //'"WHERE key =":',
			"config" => Array (
				"type" => "input",		
				"size" => "20",
				"max" => "80",
				"eval" => "trim"
			)
		),
		"table_bgColor" => Array (
			"exclude" => 1,	
			"label" => "Backgr. Color:|Baggr. Farve:|Hintergr. Farbe:|Bakgrunns farge:|Colore di sfondo:|Couleur de fond:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("Default|Standard|Standard|Standard|Predefinito|Valeur par défaut", "0"),
					Array("Color 1|Farve 1|Farbe 1|Farge 1|Colore 1|Couleur 1", "1"),
					Array("Color 2|Farve 2|Farbe 2|Farge 2|Colore 2|Couleur 2", "2"),
					Array("None|Ingen|Keine|Ingen|Nessuno|Aucune", "200"),
					Array("-----","--div--"),
					Array("Black|Sort|Schwarz|Svart|Nero|Noir", "240"),
					Array("White|Hvid|Weiss|Hvit|Bianco|Blanc", "241"),
					Array("Dark gray|Mørkegrå|Dunkelgrau|Mørkegrå|Grigio scuro|Gris sombre", "242"),
					Array("Gray|Grå|Grau|Grå|Grigio|Gris clair", "243"),
					Array("Silver|Lysegrå|Silber|Lysegrå|Argento|Argent", "244")
				),
				"default" => "0"
			)
		),
		"table_border" => Array (
			"exclude" => 1,	
			"label" => "Border:|Ramme:|Rahmen:|Ramme:|Bordo:|Bordure:",
			"config" => Array (
				"type" => "input",	
				"size" => "3",
				"max" => "3",
				"eval" => "int",
				"checkbox" => "0",
				"range" => Array (
					"upper" => "20",
					"lower" => "0"
				),
				"default" => 0
			)
		),
		"table_cellspacing" => Array (
			"exclude" => 1,	
			"label" => "Cellspacing:|Cellemellemrum:|Zellenabstand:|Cellemellomrom:|Spazio celle:|Espacement entre cellules:",
			"config" => Array (
				"type" => "input",	
				"size" => "3",
				"max" => "3",
				"eval" => "int",
				"checkbox" => "0",
				"range" => Array (
					"upper" => "20",
					"lower" => "0"
				),
				"default" => 0
			)
		),
		"media" => Array (
			"label" => "Files:|Filer:|Dateien:|Filer:|Files:|Fichiers:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "gif,jpg,jpeg,tif,bmp,psd,pcx,ttf,html,htm,css,rtf,txt,doc,ppt,wpd,sdw,pdf,ai,zip,tar,tgz,gz,wav,mp3,mov,avi,asf,mpg",
				"max_size" => "2000",
				"uploadfolder" => "uploads/media",
				"show_thumbs" => "1",
				"size" => "3",
				"maxitems" => "5",
				"minitems" => "0"
			)
		),
		"multimedia" => Array (
			"label" => "File:|Fil:|Datei:|Fil:|File:|Fichier:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "txt,html,htm,class,swf,swa,dcr,wav,avi,au,mov,asf,mpg,wmv",
				"max_size" => "2000",
				"uploadfolder" => "uploads/media",
				"size" => "2",
				"maxitems" => "1",
				"minitems" => "0"
			)
		),
		"filelink_size" => Array (
			"label" => "Show filesize:|Vis filstørrelse:|Zeige Dateigrösse|Vis filstørrelse:|Mostra dimensione files:|Afficher taille du fichier:",
			"config" => Array (
				"type" => "check"
			)
		),
		"records" => Array (
			"label" => "Items:|Emner:|Objekte:|Emner:|Items:|Eléments:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "db",
					"allowed" => "tt_content,tt_address,tt_links,tt_board,tt_guest,tt_calender,tt_products",
				"size" => "5",
				"maxitems" => "200",
				"minitems" => "0",
				"show_thumbs" => "1"
			)
		),
		"spaceBefore" => Array (
			"exclude" => 1,	
			"label" => "Before:|Før:|Vor:|Før:|Prima:|Avant:",
			"config" => Array (
				"type" => "input",	
				"size" => "3",
				"max" => "3",
				"eval" => "int",
				"checkbox" => "0",
				"range" => Array (
					"upper" => "50",
					"lower" => "0"
				),
				"default" => 0
			)
		),
		"spaceAfter" => Array (
			"exclude" => 1,	
			"label" => "After:|Efter:|Nach:|Etter:|Dopo:|Après:",
			"config" => Array (
				"type" => "input",	
				"size" => "3",
				"max" => "3",
				"eval" => "int",
				"checkbox" => "0",
				"range" => Array (
					"upper" => "50",
					"lower" => "0"
				),
				"default" => 0
			)
		),
		"section_frame" => Array (
			"exclude" => 1,	
			"label" => "Frame:|Ramme:|Rahmen:|Ramme:|Frame:|Frame:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("", "0"),
					Array("Invisible|Usynlig|Unsichtbar|Usynlig|Invisibile|Invisible", "1"),
					Array("Ruler before|Streg før|Linie davor|Strek før|Separatore prima|Règle avant", "5"),
					Array("Ruler after|Streg efter|Line danach|Strek etter|Separatore dopo|Règle après", "6"),
					Array("Indent|Indrykket|Einrücken|Innrykk|Indent|Indenter", "10"),
					Array("Frame 1|Ramme 1|Rahmen 1|Ramme 1|Frame 1|Frame 1", "20"),
					Array("Frame 2|Ramme 2|Rahmen 2|Ramme 2|Frame 2|Frame 2", "21")
				),
				"default" => "0"
			)
		),
		"splash_layout" => Array (
			"exclude" => 1,	
			"label" => "Type:|Type:|Typ:|Type:|Tipo:|Type:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("Default|Standard|Standard|Standard|Predefnito|Valeur par défaut", "0"),
					Array("Image Shadow|Billedskygge|Schatten|Bildeskygge|Immagine nascosta|Image ombrée", "1"),
					Array("Image Frame 1|Billedramme 1|Bildrahmen 1|Bilderamme 1|Immagine frame 1|Image frame 1", "2"),
					Array("Image Frame 2|Billedramme 2|Bildrahmen 2|Bilderamme 2|Immagine frame 2|Image frame 2", "3"),
/*					Array("__Textboxes:__|__Tekstbokse:__|__Textboxen:__|__Tekstboks:__||___Texte encadré:___", "--div--"),
					Array("News|Nyhed|News|Nyhet|Novita'|Nouveautés", "11"),
					Array("Note|Note|Notiz|Notis|Note|Note", "12"),
					Array("Important|Vigtigt|Wichtig|Viktig|Inportante|Important", "13"),
					Array("Remember|Husk|Erinnern|Husk|Ricordare|Ne pas oublier", "14"),
					Array("Warning|Advarsel|Warnung|Advarsel|Attenzione|Warning", "15"),
	*/				Array("__Graphical:__|__Grafiske:__|__Grafisch:__|__Grafiske:__|__Grafica:__|___Graphique :___", "--div--"),
					Array("Postit 1|Postit 1|Postit 1|Postit 1|Postit 1|Post-it 1", "20")
//					Array("Postit 2|Postit 2|Postit 2|Postit 2|Postit 2|Post-it 2", "21"),
//					Array("Paper 1|Papir 1|Papier 1|Papir 1|Carta 1|Feuille 1", "22"),
//					Array("Paper 2|Papir 2|Papier 2|Papir 2|Carta 2|Feuille 2", "23")
				),
				"default" => "0"
			)
		),
		"sectionIndex" => Array (
			"exclude" => 1,	
			"label" => "Index:|Index:|Index:|Index:|Indice:|Index:",
			"config" => Array (
				"type" => "check"
			)
		),
		"linkToTop" => Array (
			"exclude" => 1,	
			"label" => "To top:|Til top:|Nach Oben:|Til toppen:|Vai su:|Vers la haut:",
			"config" => Array (
				"type" => "check"
			)
		)
	),
	"types" => Array (	
		"1" => 	Array("showitem" => "CType"),
		"header" => 	Array("showitem" => "CType;;4;button, header;;3, subheader;;8"),
		"text" => 		Array("showitem" => "CType;;4;button, header;;3, bodytext;;9, text_properties"),
		"textpic" => 	Array("showitem" => "CType;;4;button, header;;3, bodytext;;9, text_properties, --div--, image;;2, imagewidth;;13, --palette--;Image Link|Billedlink|Bild-Link|Bildehyperkobling|Link immagine;7, --palette--;Image Options|Billedvalg|Bild Optionen|Bildevalg|Opzioni immagine;11, imagecaption;;5"),
		"image" => 		Array("showitem" => "CType;;4;button, header;;3, image;;2, imagewidth;;13, --palette--;Image Link|Billedlink|Bild-Link|Bildehyperkobling|Link immagine;7, --palette--;Image Options|Billedvalg|Bild Optionen|Bildevalg|Opzioni immagine;11, imagecaption;;5"),
		"bullets" => 	Array("showitem" => "CType;;4;button, layout, header;;3, bodytext;;9;nowrap, text_properties"),
		"table" => 		Array("showitem" => "CType;;4;button, layout;;10;button, header;;3, cols, bodytext;;9;nowrap, text_properties"),
		"splash" => 	Array("showitem" => "CType;;4;button, splash_layout, header;Name:|Navn:|Name:|Navn:|Nome:, bodytext, image;;6"),
		"uploads" => 	Array("showitem" => "CType;;4;button, header;;3, media, select_key;Read from path:|Læs fra sti:|Dateipfad:|Les fra sti:|Leggi dal percorso:, layout;;10;button, filelink_size"),
		"multimedia" =>	Array("showitem" => "CType;;4;button, header;;3, multimedia, bodytext;Parameters:|Parametre:|Parameter:|Parameter:|Parametri:;;nowrap"),
		"script" =>		Array("showitem" => "CType;;4;button, header;Name:|Navn:|Name:|Navn:|Nome:, select_key, bodytext;Parameters:|Parametre:|Parameter:|Parameter:|Parametri:;;nowrap, imagecaption;Comments:|Kommentarer:|Kommentar:|Kommentarer:|Commenti:"),
		"menu" => 		Array("showitem" => "CType;;4;button, header;;3, menu_type, pages"),
		"mailform" => 	Array("showitem" => "CType;;4;button, header;;3, bodytext;Configuration:|Opsætning:|Konfiguration:|Oppsett:|Configurazione:;;nowrap, pages;Jump to page:|Spring til side:|Zielseite:|Gå til side:|Salta alla pagina:, subheader;Recipient-email:|Modtager-email:|Empf.-EMailadr.:|Mottakers-epost:|Contenitore E-mail:"),
		"search" => 	Array("showitem" => "CType;;4;button, header;;3, pages;Send to page:|Send til side:|Zielseite:|Send til side:|Manda alla pagina:"),
		"login" => 		Array("showitem" => "CType;;4;button, header;;3, pages;Send to page:|Send til side:|Zielseite:|Send til side:|Manda alla pagina:"),
		"shortcut" => 	Array("showitem" => "CType;;4;button, header;Name:|Navn:|Name:|Navn:|Nome:, records, layout"),
		"list" => 		Array("showitem" => "CType;;4;button, header;;3, --div--, list_type, layout, select_key, pages;;12"),
		"html" => 		Array("showitem" => "CType;;4;button, header;Name:|Navn:|Name:|Navn:|Nome:, bodytext;HTML:|HTML-kode:|HTML:|HTML-kode:|HTML:|HTML:;;nowrap")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "hidden, starttime, endtime, fe_group"),
		"2" => Array("showitem" => "imageorient, imagecols, image_noRows, imageborder"),
		"3" => Array("showitem" => "header_position, header_layout, header_link, date"),
		"4" => Array("showitem" => "colPos, spaceBefore, spaceAfter, section_frame, sectionIndex, linkToTop"),
		"5" => Array("showitem" => "imagecaption_position"),
		"6" => Array("showitem" => "imagewidth"),
		"7" => Array("showitem" => "image_link, image_zoom"),
		"8" => Array("showitem" => "layout"),
		"9" => Array("showitem" => "text_align,text_face,text_size,text_color"),
		"10" => Array("showitem" => "table_bgColor, table_border, table_cellspacing"),
		"11" => Array("showitem" => "image_compression, image_effects, image_frames"),
		"12" => Array("showitem" => "recursive"),
		"13" => Array("showitem" => "imageheight")
	)
);




// ******************************************************************
// This is the standard TypoScript address table, tt_address
// ******************************************************************

$tc[tt_address] = Array (
	"ctrl" => Array (
		"label" => "name",
		"default_sortby" => "ORDER BY name",
		"tstamp" => "tstamp",
		"delete" => "deleted",
		"title" => "Address|Adresse|Adresse|Adresse|Indirizzo:",
		"enablecolumns" => Array (
			"disabled" => "hidden"
		),
		"thumbnail" => "image"
	),
	"interface" => Array (
		"showRecordFieldList" => "name,address,city,zip,country,phone,fax,email,www,title,company,image"
	),
	"columns" => Array (	
		"hidden" => Array (
			"exclude" => 1,	
			"label" => "Hide:|Skjul:|Verstecken:|Skjul:|Nascondi:|Cacher:",
			"config" => Array (
				"type" => "check"
			)
		),
		"name" => Array (
			"label" => "Name:|Navn:|Name:|Navn:|Nome:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		),
		"title" => Array (
			"exclude" => 1,	
			"label" => "Title:|Titel:|Titel:|Tittel:|Titolo:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "40"
			)
		),
		"address" => Array (
			"label" => "Address:|Adresse:|Adresse:|Adresse:|Indirizzo:",
			"config" => Array (
				"type" => "text",
				"cols" => "20",	
				"rows" => "3"
			)
		),
		"phone" => Array (
			"label" => "Phone:|Telefon:|Telefon:|Telefon:|Telefono:",
			"config" => Array (
				"type" => "input",
				"eval" => "trim",
				"size" => "20",
				"max" => "30"
			)
		),
		"fax" => Array (
			"exclude" => 1,	
			"label" => "Fax:|Fax:|Fax:|Fax:|Fax:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "30"
			)
		),
		"mobile" => Array (
			"exclude" => 1,	
			"label" => "Mobile:|Mobil:|Mobil:|Mobil:|Cellulare:",
			"config" => Array (
				"type" => "input",
				"eval" => "trim",
				"size" => "20",
				"max" => "30"
			)
		),
		"www" => Array (
			"exclude" => 1,	
			"label" => "www:|www:|www:|www:|sito web:",
			"config" => Array (
				"type" => "input",
				"eval" => "trim",
				"size" => "20",
				"max" => "80"
			)
		),
		"email" => Array (
			"label" => "Email:|Email:|Email:|Epost:|Email:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"company" => Array (
			"exclude" => 1,	
			"label" => "Company:|Firma:|Firma:|Firma:|Societa\':",
			"config" => Array (
				"type" => "input",
				"eval" => "trim",
				"size" => "20",
				"max" => "80"
			)
		),
		"city" => Array (
			"label" => "City:|By:|Stadt:|By:|Citta\':",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"zip" => Array (
			"label" => "Zipcode:|Postnr:|PLZ:|Postnr:|CAP:",
			"config" => Array (
				"type" => "input",
				"eval" => "trim",
				"size" => "10",
				"max" => "20"
			)
		),
		"country" => Array (
			"exclude" => 1,	
			"label" => "Country:|Land:|Land:|Land:|Paese:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "30"
			)
		),
		"image" => Array (
			"exclude" => 1,	
			"label" => "Image:|Billede:|Bild:|Bilde:|Immagine:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "gif,jpg,jpeg,tif,bmp,pcx,pdf,ai,tga,png",
				"max_size" => "1000",
				"uploadfolder" => "uploads/pics",
				"show_thumbs" => "1",
				"size" => "3",
				"maxitems" => "6",
				"minitems" => "0"
			)
		)
	),
	"types" => Array (	
		"1" => Array("showitem" => "hidden, name;;2, email;;5, address;;3, zip;;3, city;;3, phone;;4, image")
	),
	"palettes" => Array (
		"2" => Array("showitem" => "title, company"),
		"3" => Array("showitem" => "country"),
		"4" => Array("showitem" => "mobile, fax"),
		"5" => Array("showitem" => "www")
	)
);



// ******************************************************************
// This is the standard TypoScript links table, tt_links
// ******************************************************************

$tc[tt_links] = Array (
	"ctrl" => Array (
		"label" => "title",
		"default_sortby" => "ORDER BY title",
		"tstamp" => "tstamp",
		"delete" => "deleted",
		"crdate" => "crdate",
		"enablecolumns" => Array (
			"disabled" => "hidden"
		),
		"title" => "Links|Links|Links|Linker|Links"
	),
	"interface" => Array (
		"showRecordFieldList" => "title,url,hidden,note,note2"
	),
	"columns" => Array (	
		"title" => Array (
			"label" => "Title:|Titel:|Titel:|Tittel:|Titolo:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		),
		"note" => Array (
			"label" => "Note:|Note:|Bemerkung:|Merk deg:|Note:",
			"config" => Array (
				"type" => "text",
				"cols" => "40",	
				"rows" => "5"
			)
		),
		"note2" => Array (
			"exclude" => 1,	
			"label" => "Note (alt):|Note (alt):|Bemerkung (alt):|Merk deg (alt):|Note(alt.):",
			"config" => Array (
				"type" => "text",
				"cols" => "40",	
				"rows" => "5"
			)
		),
		"url" => Array (
			"label" => "URL:|URL:|URL:|URL:|URL:|URL:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"eval" => "trim",
				"max" => "120"
			)
		),
		"hidden" => Array (
			"exclude" => 1,	
			"label" => "Hide:|Skjul:|Verstecken:|Skjul:|Nascondi:|Cacher:",
			"config" => Array (
				"type" => "check",
				"default" => "1"
			)
		)
	),
	"types" => Array (	
		"1" => Array("showitem" => "hidden, title, note, note2, url")
	)
);



// ******************************************************************
// This is the standard TypoScript guestbook
// ******************************************************************

$tc[tt_guest] = Array (
	"ctrl" => Array (
		"label" => "title",
		"default_sortby" => "ORDER BY crdate DESC",
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"delete" => "deleted",
		"type" => "type",
		"enablecolumns" => Array (
			"disabled" => "hidden"
		),
		"title" => "Guestbook|Gæstebog|Gästebuch|Gjestebok|Libro visitatori",
		"iconfile" => "tt_faq_no.gif"
	),
	"interface" => Array (
		"showRecordFieldList" => "title,cr_name,cr_email,note,hidden"
	),
	"columns" => Array (	
		"title" => Array (
			"label" => "Title:|Titel:|Titel:|Tittel:|Titolo:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		),
		"note" => Array (
			"label" => "Note:|Note:|Bemerkung:|Merk deg:|Note:",
			"config" => Array (
				"type" => "text",
				"cols" => "40",	
				"rows" => "5"
			)
		),
		"cr_name" => Array (
			"label" => "Sender name:|Afsender navn:|Absender Name:|Avsenderens navn:|Mittente:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"cr_email" => Array (
			"label" => "Sender email:|Afsender email:|Absender email:|Avsenderens Epost:|E-mail mittente:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"hidden" => Array (
			"exclude" => 1,	
			"label" => "Hide:|Skjul:|Verstecken:|Skjul:|Nascondi:|Cacher:",
			"config" => Array (
				"type" => "check",
				"default" => "1"
			)
		)
	),
	"types" => Array (	
		"0" => Array("showitem" => "type, hidden, title, note, cr_name, cr_email")
	)
);








// ******************************************************************
// This is the standard TypoScript Board table, tt_board
// ******************************************************************

$tc[tt_board] = Array (
	"ctrl" => Array (
		"label" => "subject",
		"default_sortby" => "ORDER BY parent,crdate DESC",
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"delete" => "deleted",
		"enablecolumns" => Array (
			"disabled" => "hidden"
		),
		"title" => "Board|Forum|Forum|Forum|Board",
		"typeicon_column" => "parent",
		"typeicons" => Array (
			"0" => "tt_faq_board_root.gif"
		),
		"useColumnsForDefaultValues" => "parent",
		"iconfile" => "tt_faq_board.gif"
	),
	"interface" => Array (
		"showRecordFieldList" => "subject,author,email,message"
	),
	"columns" => Array (	
		"subject" => Array (
			"label" => "Subject:|Emne:|Titel:|Emne:|Soggetto:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		),
		"message" => Array (
			"label" => "Message:|Indhold:|Mitteilung:|Innhold:|Messaggio:",
			"config" => Array (
				"type" => "text",
				"cols" => "40",	
				"rows" => "5"
			)
		),
		"author" => Array (
			"label" => "Author:|Forfatter:|Autor:|Forfatter:|Autore:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"email" => Array (
			"label" => "Email:|Email:|Email:|Email:|Email:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"hidden" => Array (
			"label" => "Hide:|Skjul:|Verstecken:|Skjul:|Nascondi:|Cacher:",
			"config" => Array (
				"type" => "check"
			)
		),
		"parent" => Array (
			"label" => "Parent:|Forrige:|Vorherige|Forrige:|Parent:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "db",
					"allowed" => "tt_board",
				"size" => "3",
				"maxitems" => "1",
				"minitems" => "0",
				"show_thumbs" => "1"
			)
		),
		"notify_me" => Array (
			"label" => "Notify by email:|Orientering pr. email:|Benachrichtigung per email:|Orientering per Epost:|Notifica via email:",
			"config" => Array (
				"type" => "check"
			)
		)
	),
	"types" => Array (	
		"0" => Array("showitem" => "hidden, subject, message, author, email, parent, notify_me")
	)
);










// ******************************************************************
// This is the standard TypoScript calendar table, tt_calender
// ******************************************************************

$tc[tt_calender] = Array (
	"ctrl" => Array (
		"label" => "title",
		"default_sortby" => "ORDER BY date",
		"tstamp" => "tstamp",
		"delete" => "deleted",
		"type" => "type",
		"enablecolumns" => Array (
			"disabled" => "hidden",
			"starttime" => "starttime",
			"endtime" => "endtime"
		),
		"mainpalette" => 1,
		"typeicon_column" => "type",
		"typeicons" => Array (
			"0" => "tt_calender.gif",
			"1" => "tt_calender_todo.gif"
		),
		"title" => "Calendar|Kalender|Kalender|Kalender|Calendario",
		"useColumnsForDefaultValues" => "type",
		"mainpalette" => 1
	),
	"interface" => Array (
		"showRecordFieldList" => "type,date,title,note,category,responsible,workgroup,time,week,hidden,starttime,endtime"
	),
	"columns" => Array (	
		"starttime" => Array (
			"exclude" => 1,	
			"label" => "Start:|Start:|Start:|Start:|Inizio:|Lancement:",
			"config" => Array (
				"type" => "input",
				"size" => "7",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0"
			)
		),
		"endtime" => Array (
			"exclude" => 1,	
			"label" => "Stop:|Stop:|Stop:|Stopp:|Fine:|Arrêt:",
			"config" => Array (
				"type" => "input",
				"size" => "7",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0",
				"range" => Array (
					"upper" => mktime(0,0,0,12,31,2020),
					"lower" => mktime(0,0,0,date("m")-1,date("d"),date("Y"))
				)
			)
		),
		"hidden" => Array (
			"exclude" => 1,	
			"label" => "Hide:|Skjul:|Verstecken:|Skjul:|Nascondi:|Cacher:",
			"config" => Array (
				"type" => "check",
				"default" => "1"
			)
		),
		"title" => Array (
			"label" => "Title:|Titel:|Titel:|Tittel:|Titolo:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		),
		"note" => Array (
			"label" => "Note:|Note:|Bemerkung:|Merk deg:|Note:",
			"config" => Array (
				"type" => "text",
				"cols" => "40",	
				"rows" => "5"
			)
		),
		"type" => Array (
			"exclude" => 1,	
			"label" => "Type:|Type:|Typ:|Type:|Tipo:|Type:",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("Calendar|Kalender|Kalender|Kalender|Calendario", 0),
					Array("Todo|Todo|Todo|Todo|da fare", 1)
				),
				"default" => 0
			)
		),
		"date" => Array (
			"exclude" => 1,	
			"label" => "Date:|Dato:|Datum:|Dato:|Data:|Date:",
			"config" => Array (
				"type" => "input",
				"size" => "10",
				"max" => "20",
				"eval" => "date",
				"default" => "0"
			)
		),
		"time" => Array (
			"exclude" => 1,	
			"label" => "Time:|Tidspunkt:|Zeit:|Tidspunkt:|Ora:",
			"config" => Array (
				"type" => "input",
				"size" => "10",
				"max" => "20",
				"eval" => "time",
				"default" => "0"
			)
		),
		"week" => Array (
			"exclude" => 1,	
			"label" => "Week:|Uge:|Woche:|Uke:|Settimana:",
			"config" => Array (
				"type" => "input",
				"size" => "6",
				"max" => "6",
				"eval" => "int",
				"range" => Array (
					"upper" => 52,
					"lower" => 1
				),
				"default" => "0"
			)
		),
		"datetext" => Array (
			"exclude" => 1,	
			"label" => "Datetext:|Datotekst:|Datumstext:|Datotekst:|Data:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"complete" => Array (
			"exclude" => 1,	
			"label" => "Finished:|Afsluttet:|Ende:|Avslutt:|Finire:",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"workgroup" => Array (
			"exclude" => 1,	
			"label" => "Workgroup:|Arbejdsgruppe:|Arbeitsgruppe:|Arbeidsgruppe:|Gruppo di lavoro:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"priority" => Array (
			"label" => "Priority:|Prioritet:|Priorität:|Prioritet:|priorita\':",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("High|Høj|Hoch|Høy|Alta", 1),
					Array("Medium|Mellem|Mittel|Medium|Media", 3),
					Array("Low|Lav|Niedrig|Lav|Bassa", 5)
				),
				"default" => 3
			)
		),
		"responsible" => Array (
			"exclude" => 1,	
			"label" => "Responsible:|Ansvarlige:|Verantwortlich:|Ansvarlige:|Responsabile:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "db",
					"allowed" => "tt_address",
				"size" => "3",
				"eval" => "trim",
				"maxitems" => "6",
				"minitems" => "0",
				"show_thumbs" => "1"
			)
		),
		"category" => Array (
			"exclude" => 1,	
			"label" => "Category:|Kategori:|Kategorie:|Kategori:|Categoria:",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("", 0)
				),
				"foreign_table" => "tt_calender_cat"
			)
		),
		"link" => Array (
			"exclude" => 1,	
			"label" => "Link to page:|Link til side:|Link zur Seite:|Link til side:|Lonk alla pagina:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "db",
					"allowed" => "pages",
				"size" => "3",
				"maxitems" => "5",
				"minitems" => "0",
				"show_thumbs" => "1"
			)
		)
	),
	"types" => Array (	
		"0" => Array("showitem" => "type, hidden, date;;2, title, note, category, --div--, datetext, link"),
		"1" => Array("showitem" => "type, hidden, date;;2, title, note, category, --div--, complete, priority, workgroup, responsible, link")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "starttime, endtime"),
		"2" => Array("showitem" => "week,time")	//, month
	)
);





// ******************************************************************
// This is the standard TypoScript calendar category table, tt_calender_cat
// ******************************************************************

$tc[tt_calender_cat] = Array (
	"ctrl" => Array (
		"label" => "title",
		"tstamp" => "tstamp",
		"delete" => "deleted",
		"crdate" => "crdate",
		"title" => "Calendar category|Kalender kategori|Kalender Kategorie|Kalender kategori|Categoria calendario"
	),
	"interface" => Array (
		"showRecordFieldList" => "title"
	),
	"columns" => Array (	
		"title" => Array (
			"label" => "Title:|Titel:|Titel:|Tittel:|Titolo:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		)
	),
	"types" => Array (	
		"0" => Array("showitem" => "title")
	)
);





// ******************************************************************
// This is the standard TypoScript address table, tt_address
// ******************************************************************

$tc[tt_products] = Array (
	"ctrl" => Array (
		"label" => "title",
		"default_sortby" => "ORDER BY title",
		"tstamp" => "tstamp",
		"delete" => "deleted",
		"title" => "Products|Produkter|Produkte|Produkter|Prodotti",
		"enablecolumns" => Array (
			"disabled" => "hidden",
			"starttime" => "starttime",
			"endtime" => "endtime"
		),
		"thumbnail" => "image",
		"useColumnsForDefaultValues" => "category",
		"mainpalette" => 1
	),
	"interface" => Array (
		"showRecordFieldList" => "title,itemnumber,price,price2,note,category,inStock,image,hidden,starttime,endtime"
	),
	"columns" => Array (	
		"starttime" => Array (
			"exclude" => 1,	
			"label" => "Start:|Start:|Start:|Start:|Inizio:|Lancement:",
			"config" => Array (
				"type" => "input",
				"size" => "7",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0"
			)
		),
		"endtime" => Array (
			"exclude" => 1,	
			"label" => "Stop:|Stop:|Stop:|Stopp:|Fine:|Arrêt:",
			"config" => Array (
				"type" => "input",
				"size" => "7",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0",
				"range" => Array (
					"upper" => mktime(0,0,0,12,31,2020),
					"lower" => mktime(0,0,0,date("m")-1,date("d"),date("Y"))
				)
			)
		),
		"hidden" => Array (
			"exclude" => 1,	
			"label" => "Hide:|Skjul:|Verstecken:|Skjul:|Nascondi:|Cacher:",
			"config" => Array (
				"type" => "check"
			)
		),
		"title" => Array (
			"label" => "Title:|Titel:|Titel:|Tittel:|Titolo:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		),
		"note" => Array (
			"label" => "Note:|Note:|Bemerkung:|Merk deg:|Note:",
			"config" => Array (
				"type" => "text",
				"cols" => "48",	
				"rows" => "5"
			)
		),
		"price" => Array (
			"label" => "Price:|Pris:|Preis:|Pris:|Prezzo:",
			"config" => Array (
				"type" => "input",
				"size" => "12",
				"eval" => "trim,double2",
				"max" => "20"
			)
		),
		"price2" => Array (
			"exclude" => 1,	
			"label" => "Price (2):|Pris (2):|Preis (2):|Pris (2):|Prezzo(2):",
			"config" => Array (
				"type" => "input",
				"size" => "12",
				"eval" => "trim,double2",
				"max" => "20"
			)
		),
		"www" => Array (
			"exclude" => 1,	
			"label" => "www:|www:|www:|www:|www:",
			"config" => Array (
				"type" => "input",
				"eval" => "trim",
				"size" => "20",
				"max" => "80"
			)
		),
		"itemnumber" => Array (
			"label" => "Item number:|Emne nummer:|Artikel Nr.:|Emne nummer:|Numero item:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "40"
			)
		),
		"category" => Array (
			"exclude" => 1,	
			"label" => "Category:|Kategori:|Kategorie:|Kategori:|Categoria:",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("", 0)
				),
				"foreign_table" => "tt_products_cat"
			)
		),
		"inStock" => Array (
			"exclude" => 1,	
			"label" => "In Stock (pcs):|På lager (stk):|Am Lager (St.):|På lager (stk.):|In giacenza (p.zi):",
			"config" => Array (
				"type" => "input",
				"size" => "6",
				"max" => "6",
				"eval" => "int",
				"range" => Array (
					"lower" => 0
				)
			)
		),
		"image" => Array (
			"exclude" => 1,	
			"label" => "Image:|Billede:|Bild:|Bilde:|Immagine:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "gif,jpg,jpeg,tif,bmp,pcx,pdf,ai,tga,png",
				"max_size" => "1000",
				"uploadfolder" => "uploads/pics",
				"show_thumbs" => "1",
				"size" => "3",
				"maxitems" => "6",
				"minitems" => "0"
			)
		)
	),
	"types" => Array (	
		"1" => Array("showitem" => "hidden, title;;3, itemnumber, category, price;;2, note, image")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "starttime, endtime"),
		"2" => Array("showitem" => "price2, inStock"),
		"3" => Array("showitem" => "www")
	)
);







// ******************************************************************
// This is the standard TypoScript calendar category table, tt_calender_cat
// ******************************************************************

$tc[tt_products_cat] = Array (
	"ctrl" => Array (
		"label" => "title",
		"tstamp" => "tstamp",
		"delete" => "deleted",
		"crdate" => "crdate",
		"title" => "Product category|Produkt kategori|Produkt Kategorie|Produkt kategori|Categoria prodotto"
	),
	"columns" => Array (	
		"title" => Array (
			"label" => "Title:|Titel:|Titel:|Tittel:|Titolo:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		)
	),
	"types" => Array (	
		"0" => Array("showitem" => "title")
	)
);




// ******************************************************************
// sys_dmail
// ******************************************************************

$tc[sys_dmail] = Array (
	"ctrl" => Array (
		"label" => "subject",
		"default_sortby" => "ORDER BY tstamp DESC",
		"tstamp" => "tstamp",
		"title" => "Direct mails|Direct mail",
		"iconfile" => "mail.gif",
		"type" => "type",
		"useColumnsForDefaultValues" => "from_email,from_name,replyto_email,replyto_name,organisation,priority,sendOptions,type"
	),
	"interface" => Array (
		"showRecordFieldList" => "type,plainParams,HTMLParams,subject,from_name,from_email,replyto_name,replyto_email,organisation,attachment,priority,sendOptions,issent,renderedsize"
	),
	"columns" => Array (	
		"subject" => Array (
			"label" => "Subject:|Emne:",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"max" => "120",
				"eval" => "trim,required"
			)
		),
		"page" => Array (	
			"label" => "Mail page:|Side med mail:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "db",
				"allowed" => "pages",
				"size" => "3",
				"maxitems" => 1,
				"minitems" => 0
			)
		),
/*		"url" => Array (
			"label" => "External URL:|Extern URL:",
			"config" => Array (
				"type" => "input",	
				"size" => "30",
				"eval" => "trim",
				"max" => "120"
			)
		),*/
		"from_email" => Array (
			"label" => "Sender email:|Afsender email:",
			"config" => Array (
				"type" => "input",	
				"size" => "30",
				"max" => "80",
				"eval" => "trim,required"
			)
		),
		"from_name" => Array (
			"label" => "Sender name:|Afsender navn:",
			"config" => Array (
				"type" => "input",	
				"size" => "30",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"replyto_email" => Array (
			"label" => "Reply email:|Svar email:",
			"config" => Array (
				"type" => "input",	
				"size" => "30",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"replyto_name" => Array (
			"label" => "Reply name:|Svar navn:",
			"config" => Array (
				"type" => "input",	
				"size" => "30",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"organisation" => Array (
			"label" => "Organization:|Organisation:",
			"config" => Array (
				"type" => "input",	
				"size" => "30",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"priority" => Array (
			"label" => "Priority:|Prioritet:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (
					Array("Low|Lav", "5"),
					Array("Normal", "3"),
					Array("High|Høj", "1")
				),
				"default" => "3"
			)
		),
		"sendOptions" => Array (
			"label" => "Include|Medtag",
			"config" => Array (
				"type" => "check",
				"items" => Array (
					Array("Plain text|Ren tekst", ""),
					Array("HTML", "")
				),
				"default" => "3"
			)
		),
		"HTMLParams" => Array (
			"label" => "Parameters, HTML:|Parametre, HTML:",
			"config" => Array (
				"type" => "input",	
				"size" => "15",
				"max" => "80",
				"eval" => "trim",
				"default" => "&type=1"
			)
		),
		"plainParams" => Array (
			"label" => "Parameters, Plain text:|Parametre, Ren tekst:",
			"config" => Array (
				"type" => "input",	
				"size" => "15",
				"max" => "80",
				"eval" => "trim",
				"default" => "&type=1&no_cache=1&plaintext=1"
			)
		),
		"issent" => Array (	
			"label" => "Is sent:|Sendt:",
			"config" => Array (
				"type" => "none"
			)
		),
		"renderedsize" => Array (	
			"label" => "Compiled size:|Kompileret størrelse:",
			"config" => Array (
				"type" => "none"
			)
		),
		"attachment" => Array (
			"label" => "Attachments:|Vedhæng:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "gif,jpg,jpeg,tif,bmp,psd,pcx,html,htm,ttf,txt,css,pdf,doc,ppt,ai",
				"max_size" => "500",
				"uploadfolder" => "uploads/dmail_att",
				"show_thumbs" => "0",
				"size" => "3",
				"maxitems" => "5",
				"minitems" => "0"
			)
		),
/*		"recip_table" => Array (
			"label" => "Recipients:|Modtagere:",
			"config" => Array (
				"type" => "select",	
				"items" => Array (
					Array("", ""),
					Array("Website-brugere", "fe_users")
				),
				"default" => ""
			)
		),*/
		"type" => Array (
			"label" => "Type:",
			"config" => Array (
				"type" => "select",	
				"items" => Array (	
					Array("Typo3 page", "0"),
					Array("External URL", "1")
				),
				"default" => "0"
			)
		)
	),
	"types" => Array (	
		"0" => Array("showitem" => "type, page, plainParams, HTMLParams, --div--, subject, from_email;;1, replyto_email;;2, organisation, attachment, priority, sendOptions, issent, renderedsize"),
		"1" => Array("showitem" => "type, plainParams;URL for plaintext:, HTMLParams;URL for HTML-content:, --div--, subject, from_email;;1, replyto_email;;2, organisation, attachment, priority, sendOptions, issent, renderedsize")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "from_name"),
		"2" => Array("showitem" => "replyto_name")
	)
);


// ******************************************************************
// fe_users
// ******************************************************************

$tc[fe_users] = Array (
	"ctrl" => Array (
		"label" => "username",
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"title" => "Website user|Website bruger|Website Benutzer|Webside bruker|Utente Web",
		"delete" => "deleted",
		"mainpalette" => "1",
		"enablecolumns" => Array (
			"disabled" => "disable",
			"starttime" => "starttime",
			"endtime" => "endtime"
		),
		"useColumnsForDefaultValues" => "usergroup,lockToDomain,disable,starttime,endtime"
	),
	"interface" => Array (
		"showRecordFieldList" => "username,password,usergroup,lockToDomain,name,address,email,telephone,fax,disable,starttime,endtime"
	),
	"columns" => Array (	
		"username" => Array (
			"label" => "Username:|Brugernavn:|Benutzername:|Brukernavn:|Nome Utente:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"max" => "20",
				"eval" => "nospace,lower,unique,required"
			)
		),
		"password" => Array (
			"label" => "Password:|Password:|Password:|Password:|Password:",
			"config" => Array (
				"type" => "input",
				"size" => "10",
				"max" => "40",
				"eval" => "nospace,lower,required"
			)
		),
		"usergroup" => Array (
			"label" => "Groups:|Grupper:|Benutzergruppe:|Grupper:|Gruppi:",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "fe_groups",
				"size" => "3",
				"minitems" => "1",
				"maxitems" => "5"
			)
		),
		"lockToDomain" => Array (
			"exclude" => 1,	
			"label" => "Lock to domain:|Lås til domæne:|An Domain binden:|Lås til domene:|Blocca al dominio:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "50",
				"checkbox" => ""
			)
		),
		"name" => Array (
			"exclude" => 1,	
			"label" => "Name:|Navn:|Name:|Navn:|Nome:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"address" => Array (
			"exclude" => 1,	
			"label" => "Address:|Adresse:|Adresse:| Adresse:|Indirizzo:",
			"config" => Array (
				"type" => "text",
				"cols" => "20",	
				"rows" => "3"
			)
		),
		"telephone" => Array (
			"exclude" => 1,	
			"label" => "Phone:|Telefon:|Telefon:|Telefon:|Telefono:",
			"config" => Array (
				"type" => "input",
				"eval" => "trim",
				"size" => "20",
				"max" => "20"
			)
		),
		"fax" => Array (
			"exclude" => 1,	
			"label" => "Fax:|Fax:|Fax:|Fax:|Fax:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "20"
			)
		),
		"email" => Array (
			"exclude" => 1,	
			"label" => "Email:|Email:|Email:|Epost:|Email:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"disable" => Array (
			"exclude" => 1,	
			"label" => "Disable:|Inaktiv:|Inaktiv:|Deaktiv:|Disabilitato:",
			"config" => Array (
				"type" => "check"
			)
		),
		"starttime" => Array (
			"exclude" => 1,	
			"label" => "Start:|Start:|Start:|Start:|Inizio:|Lancement:",
			"config" => Array (
				"type" => "input",
				"size" => "7",
				"max" => "20",
				"eval" => "date",
				"default" => "0",
				"checkbox" => "0"
			)
		),
		"endtime" => Array (
			"exclude" => 1,	
			"label" => "Stop:|Stop:|Stop:|Stopp:|Fine:|Arrêt:",
			"config" => Array (
				"type" => "input",
				"size" => "7",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0",
				"range" => Array (
					"upper" => mktime(0,0,0,12,31,2020),
					"lower" => mktime(0,0,0,date("m")-1,date("d"),date("Y"))
				)
			)
		)
	),
	"types" => Array (
		"0" => Array("showitem" => "username, password, usergroup, lockToDomain, --div--, name, address, telephone, fax, email")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "disable, starttime, endtime")
	)
);



// ******************************************************************
// fe_groups
// ******************************************************************

$tc[fe_groups] = Array (
	"ctrl" => Array (
		"label" => "title",
		"tstamp" => "tstamp",
		"delete" => "deleted",
		"enablecolumns" => Array (
			"disabled" => "hidden"
		),
		"title" => "Website usergroup|Website brugergruppe|Website Benutzergruppe|Webside brukergruppe|Gruppo Utenti web",
		"useColumnsForDefaultValues" => "lockToDomain"
	),
	"interface" => Array (
		"showRecordFieldList" => "title,hidden,lockToDomain,description"
	),
	"columns" => Array (	
		"hidden" => Array (
			"label" => "Disable:|Inaktiv:|Inaktiv:|Deaktiv:|Disabilitato:",
			"exclude" => 1,	
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"title" => Array (
			"label" => "Grouptitle:|Gruppetitel:|Gruppenname:|Gruppetittel:|Titolo del Gruppo:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"max" => "20",
				"eval" => "trim,required"
			)
		),
		"lockToDomain" => Array (
			"exclude" => 1,	
			"label" => "Lock to domain:|Lås til domæne:|An Domain binden:|Lås til domene:|Blocca al dominio:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "50",
				"checkbox" => ""
			)
		),
		"description" => Array (
			"label" => "Description:|Beskrivelse:||Beskrivelse:|Descrizione:",
			"config" => Array (
				"type" => "text",
				"rows" => 5,
				"cols" => 48
			)
		)
	),
	"types" => Array (	
		"0" => Array(
			"showitem" => "hidden,title,lockToDomain,description"
		)
	)
);



// ******************************************************************
// be_groups
// ******************************************************************

$tc[be_users] = Array (
	"ctrl" => Array (
		"label" => "username",
		"tstamp" => "tstamp",
		"title" => "Backend user|Opdaterings bruger||Oppdater bruker|Utente backend",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"delete" => "deleted",
		"rootLevel" => 1,
		"default_sortby" => "ORDER BY admin, username",
		"enablecolumns" => Array (
			"disabled" => "disable",
			"starttime" => "starttime",
			"endtime" => "endtime"
		),
		"type" => "admin",
		"typeicon_column" => "admin",
		"typeicons" => Array (
			"0" => "be_users.gif",
			"1" => "be_users_admin.gif"
		),
		"mainpalette" => "1",
		"useColumnsForDefaultValues" => "usergroup,lockToDomain,options,db_mountpoints,file_mountpoints,fileoper_perms,userMods"
	),
	"interface" => Array (
		"showRecordFieldList" => "username,usergroup,db_mountpoints,file_mountpoints,admin,options,fileoper_perms,userMods,lockToDomain,realName,email,disable,starttime,endtime"
	),
	"columns" => Array (	
		"username" => Array (
			"label" => "Username:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"max" => "20",
				"eval" => "nospace,lower,unique,required"
			)
		),
		"password" => Array (
			"label" => "Password:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"max" => "40",
				"eval" => "required,md5,password"
			)
		),
		"usergroup" => Array (
			"label" => "Group:",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "be_groups",
				"foreign_table_where" => "ORDER BY be_groups.title",
				"size" => "5",
				"maxitems" => "20"
			)
		),
		"lockToDomain" => Array (
			"label" => "Lock to domain:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "50",
				"checkbox" => ""
			)
		),
		"db_mountpoints" => Array (
			"label" => "DB Mounts:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "db",
					"allowed" => "pages",
				"size" => "3",
				"maxitems" => "10",
				"show_thumbs" => "1"
			)
		),
		"file_mountpoints" => Array (
			"label" => "File Mounts:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "db",
					"allowed" => "sys_filemounts",
				"size" => "3",
				"maxitems" => "10",
				"show_thumbs" => "1"
			)
		),
		"email" => Array (
			"label" => "Email:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"realName" => Array (
			"label" => "Name:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"disable" => Array (
			"label" => "Disable:",
			"config" => Array (
				"type" => "check"
			)
		),
		"admin" => Array (
			"label" => "Admin(!):",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"options" => Array (
			"label" => "Mount from groups:",
			"config" => Array (
				"type" => "check",
				"items" => Array (
					Array("DB Mounts", 0),
					Array("File Mounts", 0)
				),
				"default" => "3"
			)
		),
		"fileoper_perms" => Array (
			"label" => "Fileoperation permissions:",
			"config" => Array (
				"type" => "check",
				"items" => Array (
					Array("Files: Upload,Copy,Move,Delete,Rename", 0),
					Array("Files: Unzip", 0),
					Array("Directory: Move,Delete,Rename,New", 0),
					Array("Directory: Copy", 0),
					Array("Directory: Delete recursively (rm -Rf)", 0)
				),
				"default" => "7"
			)
		),
		"starttime" => Array (
			"label" => "Start:",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"default" => "0",
				"checkbox" => "0"
			)
		),
		"endtime" => Array (
			"label" => "Stop:",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0",
				"range" => Array (
					"upper" => mktime(0,0,0,12,31,2020),
					"lower" => mktime(0,0,0,date("m")-1,date("d"),date("Y"))
				)
			)
		),
		"lang" => Array (
			"label" => "Default Language:",
			"config" => Array (
				"type" => "radio",
				"items" => Array (
					Array("English", ""),
					Array("Danish", "dk"),
					Array("German", "de"),
					Array("Norwegian", "no"),
					Array("Italian", "it"),
					Array("French", "fr")
				)
			)
		),
		"userMods" => Array (	
			"label" => "Modules:",
			"config" => Array (
				"type" => "select",
				"special" => "modListUser",
				"size" => "5",
				"maxitems" => "15"
			)
		)
	),
	"types" => Array (
		"0" => Array("showitem" => "username, password, lang, realName, email, --div--, admin, lockToDomain, usergroup, userMods, --div--, options, db_mountpoints, file_mountpoints,fileoper_perms"),
		"1" => Array("showitem" => "username, password, lang, realName, email, --div--, admin, usergroup, --div--, options, db_mountpoints, file_mountpoints, fileoper_perms")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "disable, starttime, endtime")
	)
);



// ******************************************************************
// be_groups
// ******************************************************************

$tc[be_groups] = Array (
	"ctrl" => Array (
		"label" => "title",
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"delete" => "deleted",
		"rootLevel" => 1,
		"type" => "inc_access_lists",
		"typeicon_column" => "inc_access_lists",
		"typeicons" => Array (
			"1" => "be_groups_lists.gif"
		),
		"enablecolumns" => Array (
			"disabled" => "hidden"
		),
		"title" => "Backend usergroup|Opdaterings brugergruppe||Oppdater brukergruppe|Gruppo backend",
		"useColumnsForDefaultValues" => "lockToDomain"
	),
	"interface" => Array (
		"showRecordFieldList" => "title,db_mountpoints,file_mountpoints,inc_access_lists,tables_select,tables_modify,pagetypes_select,non_exclude_fields,groupMods,lockToDomain,description"
	),
	"columns" => Array (	
		"title" => Array (
			"label" => "Grouptitle:",
			"config" => Array (
				"type" => "input",
				"size" => "25",
				"max" => "20",
				"eval" => "trim,required"
			)
		),	
		"db_mountpoints" => Array (
			"label" => "DB Mounts:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "db",
					"allowed" => "pages",
				"size" => "3",
				"maxitems" => "10",
				"show_thumbs" => "1"
			)
		),
		"file_mountpoints" => Array (
			"label" => "File Mounts:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "db",
					"allowed" => "sys_filemounts",
				"size" => "3",
				"maxitems" => "10",
				"show_thumbs" => "1"
			)
		),
		"pagetypes_select" => Array (
			"label" => "Page types:",
			"config" => Array (
				"type" => "select",
				"special" => "pagetypes",
				"size" => "5",
				"maxitems" => "20"
			)
		),
		"tables_modify" => Array (
			"label" => "Tables (modify):",
			"config" => Array (
				"type" => "select",
				"special" => "tables",
				"size" => "5",
				"maxitems" => "20"
			)
		),
		"tables_select" => Array (	
			"label" => "Tables (listing):",
			"config" => Array (
				"type" => "select",
				"special" => "tables",
				"size" => "5",
				"maxitems" => "20"
			)
		),
		"non_exclude_fields" => Array (	
			"label" => "Allowed excludefields:",
			"config" => Array (
				"type" => "select",
				"special" => "exclude",
				"size" => "10",
				"maxitems" => "300"
			)
		),
		"hidden" => Array (
			"label" => "Disable:",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"lockToDomain" => Array (
			"label" => "Lock to domain:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "50",
				"checkbox" => ""
			)
		),
		"groupMods" => Array (	
			"label" => "Modules:",
			"config" => Array (
				"type" => "select",
				"special" => "modListGroup",
				"size" => "5",
				"maxitems" => "15"
			)
		),
		"inc_access_lists" => Array (
			"label" => "Include Access Lists:",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"description" => Array (
			"label" => "Description:",
			"config" => Array (
				"type" => "text",
				"rows" => 5,
				"cols" => 30
			)
		)
	),
	"types" => Array (
		"0" => Array("showitem" => "hidden,title,inc_access_lists, --div--, lockToDomain, db_mountpoints,file_mountpoints,description"),
		"1" => Array("showitem" => "hidden,title,inc_access_lists, --div--, groupMods, tables_select, tables_modify, pagetypes_select, non_exclude_fields, --div--, lockToDomain, db_mountpoints,file_mountpoints,description")
	)
);



// ******************************************************************
// sys_filemounts
// ******************************************************************

$tc[sys_filemounts] = Array (
	"ctrl" => Array (
		"label" => "title",
		"tstamp" => "tstamp",
		"title" => "Filemount|Filmount||Filmount|Filemount",
		"rootLevel" => 1,
		"delete" => "deleted",
		"enablecolumns" => Array (
			"disabled" => "hidden"
		),
		"iconfile" => "_icon_ftp.gif",
		"useColumnsForDefaultValues" => "path,base"
	),
	"interface" => Array (
		"showRecordFieldList" => "title,hidden,path,base"
	),
	"columns" => Array (	
		"title" => Array (
			"label" => "LABEL:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"max" => "30",
				"eval" => "trim"
			)
		),
		"path" => Array (
			"label" => "PATH:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "120",
				"eval" => "trim"
			)
		),
		"hidden" => Array (
			"label" => "Disable:",
			"config" => Array (
				"type" => "check"
			)
		),
		"base" => Array (
			"label" => "BASE",
			"config" => Array (
				"type" => "radio",
				"items" => Array (
					Array("absolute (root) / ", 0),
					Array("relative ../fileadmin/", 1)
				),
				"default" => 0
			)
		)
	),
	"types" => Array (	
		"0" => Array("showitem" => "hidden,title,path,base")
	)
);



// ******************************************************************
// sys_domain
// ******************************************************************

$tc[sys_domain] = Array (
	"ctrl" => Array (
		"label" => "domainName",
		"tstamp" => "tstamp",
		"title" => "Domain|Domæne|Domain|Domene|Dominio",
		"iconfile" => "domain.gif",
		"enablecolumns" => Array (
			"disabled" => "hidden"
		)
	),
	"interface" => Array (
		"showRecordFieldList" => "hidden,domainName,redirectTo"
	),
	"columns" => Array (	
		"domainName" => Array (
			"label" => "Domain:|Domæne:|Domain:|Domene:|Dominio:",
			"config" => Array (
				"type" => "input",	
				"size" => "35",
				"max" => "80",
				"eval" => "required,unique,lower,trim"
			)
		),
		"redirectTo" => Array (
			"label" => "Redirect to:|Henvisning til:||Henvisning til:|Redirigi a:",
			"config" => Array (
				"type" => "input",	
				"size" => "35",
				"max" => "120",
				"checkbox" => "",
				"default" => "",
				"eval" => "trim"
			)
		),
		"hidden" => Array (
			"label" => "Disable:|Inaktiv:|Inaktiv:|Deaktiv:|Disabilitato:",
			"exclude" => 1,	
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		)
	),
	"types" => Array (					
		"1" => Array("showitem" => "hidden,domainName;;1")
	),
	"palettes" => Array (					
		"1" => Array("showitem" => "redirectTo")
	)
);










// ******************************************************************
// This is the standard TypoScript Note table , sys_note
// ******************************************************************

$tc[sys_note] = Array (
	"ctrl" => Array (
		"label" => "subject",
		"default_sortby" => "ORDER BY crdate",
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser",
		"delete" => "deleted",
		"enablecolumns" => Array (
			"disabled" => "hidden"
		),
		"title" => "Internal note|Intern note|Interne Notiz|Internt notis|Note interne",
		"iconfile" => "sys_note.gif"
	),
	"interface" => Array (
		"showRecordFieldList" => "category,subject,message,author,email,personal"
	),
	"columns" => Array (	
		"category" => Array (
			"label" => "Category:|Kategori:|Kategorie:|Kategori:|Categoria:",
			"config" => Array (
				"type" => "select",		
				"items" => Array (	
					Array("", "0"),
					Array("Instructions|Vejledning|Anweisung|Veiledning|Istruzioni", "1"),
					Array("Notes|Noter|Notiz|Notis|Note", "3"),
					Array("To-do|To-do|To-do|To-do|da fare", "4"),
					Array("Template|Skabelon|Template|Skabelon|Template", "2")
				),
				"default" => "0"
			)
		),
		"subject" => Array (
			"label" => "Subject:|Emne:|Titel:|Emne:|Soggetto:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		),
		"message" => Array (
			"label" => "Message:|Indhold:|Inhalt:|Innhold:|Messaggio:",
			"config" => Array (
				"type" => "text",
				"cols" => "40",	
				"rows" => "15"
			)
		),
		"author" => Array (
			"label" => "Author:|Forfatter:|Autor:|Forfatter:|Autore:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"email" => Array (
			"label" => "Email:|Email:|Email:|Epost:|Email:",
			"config" => Array (
				"type" => "input",
				"size" => "20",
				"eval" => "trim",
				"max" => "80"
			)
		),
		"personal" => Array (
			"label" => "Personal:|Personlig:|Persönlich:|Personlig:|Personale:",
			"config" => Array (
				"type" => "check"
			)
		)
	),
	"types" => Array (	
		"0" => Array("showitem" => "category, subject;;1, message;;1")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "author, email, personal")
	)
);




// ******************************************************************
// sys_template
// ******************************************************************

$tc[sys_template] = Array (
	"ctrl" => Array (
		"label" => "title",
		"tstamp" => "tstamp",
		"sortby" => "sorting",
		"title" => "Template|Skabelon||Skabelon|Template",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"delete" => "deleted",
		"iconfile" => "template.gif",
		"thumbnail" => "resources",
		"enablecolumns" => Array (
			"disabled" => "hidden",
			"starttime" => "starttime",
			"endtime" => "endtime"
		),
		"typeicon_column" => "root",
		"typeicons" => Array (
			"0" => "template_add.gif"
		),
		"mainpalette" => "1"
	),
	"interface" => Array (
		"showRecordFieldList" => "title,clear,root,include_static,basedOn,nextLevel,resources,sitetitle,description,hidden,starttime,endtime"
	),
	"columns" => Array (	
		"title" => Array (
			"label" => "Template title:",
			"config" => Array (
				"type" => "input",	
				"size" => "25",
				"max" => "256",
				"eval" => "required"
			)
		),
		"hidden" => Array (
			"label" => "Deactivated:",
			"exclude" => 1,	
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"starttime" => Array (
			"label" => "Start:",
			"exclude" => 1,	
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0"
			)
		),
		"endtime" => Array (
			"label" => "Stop:",
			"exclude" => 1,	
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0",
				"range" => Array (
					"upper" => mktime(0,0,0,12,31,2020),
					"lower" => mktime(0,0,0,date("m")-1,date("d"),date("Y"))
				)
			)
		),
		"root" => Array (
			"label" => "Rootlevel:",
			"config" => Array (
				"type" => "check"
			)
		),
		"clear" => Array (
			"label" => "Clear:",
			"config" => Array (
				"type" => "check",
				"items" => Array (	
					Array("Constants", ""),
					Array("Setup", "")
				),
				"cols" => 2
			)
		),
		"sitetitle" => Array (
			"label" => "Website title:",
			"config" => Array (
				"type" => "input",	
				"size" => "25",
				"max" => "256"
			)
		),
		"constants" => Array (
			"label" => "Constants:",
			"config" => Array (
				"type" => "text",
				"cols" => "48",
				"rows" => "10",
				"wrap" => "OFF"
			)
		),
		"resources" => Array (
			"label" => "Resources:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "gif,jpg,jpeg,tif,bmp,pcx,png,html,htm,ttf,txt,css,tmpl,inc,ico",
				"max_size" => "1000",
				"uploadfolder" => "uploads/tf",
				"show_thumbs" => "1",
				"size" => "7",
				"maxitems" => "15",
				"minitems" => "0"
			)
		),
		"nextLevel" => Array (
			"label" => "Template on next level:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "db",
				"allowed" => "sys_template",
				"show_thumbs" => "1",
				"size" => "1",
				"maxitems" => "1",
				"minitems" => "0",
				"default" => ""
			)
		),
		"include_static" => Array (
			"label" => "Include static:",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "static_template",
				"foreign_table_where" => "ORDER BY static_template.title DESC",
				"size" => 5,
				"maxitems" => 10,
				"default" => ""
			)
		),
		"basedOn" => Array (
			"label" => "Include basis template:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "db",
				"allowed" => "sys_template",
				"show_thumbs" => "1",
				"size" => "1",
				"maxitems" => "5",
				"minitems" => "0",
				"default" => ""
			)
		),
		"config" => Array (
			"label" => "Setup:",
			"config" => Array (
				"type" => "text",
				"rows" => 10,
				"cols" => 48,
				"wrap" => "OFF"
			)
		),
		"description" => Array (
			"label" => "Description:",
			"config" => Array (
				"type" => "text",
				"rows" => 10,
				"cols" => 48
			)
		)
	),
	"types" => Array (					
		"1" => Array("showitem" => "title;;1, sitetitle, constants, config, resources, clear, root, --div--, include_static, basedOn, nextLevel, --div--, description")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "hidden,starttime,endtime")
	)
);



// ******************************************************************
// static_template
// ******************************************************************

$tc[static_template] = Array (
	"ctrl" => Array (
		"label" => "title",
		"tstamp" => "tstamp",
		"title" => "Static templates|Faste skabeloner||Faste skabeloner|Template statici",
		"readOnly" => 1,	// This should always be true, as it prevents the static templates from being altered
		"rootLevel" => 1,
		"default_sortby" => "ORDER BY title",
		"crdate" => "crdate",
		"iconfile" => "template_standard.gif"
	),
	"interface" => Array (
		"showRecordFieldList" => "title,include_static,description"
	),
	"columns" => Array (	
		"title" => Array (
			"label" => "Template title:",
			"config" => Array (
				"type" => "input",	
				"size" => "25",
				"max" => "256",
				"eval" => "required"
			)
		),
		"constants" => Array (
			"label" => "Constants:",
			"config" => Array (
				"type" => "text",
				"cols" => "48",
				"rows" => "10",
				"wrap" => "OFF"
			)
		),
		"include_static" => Array (
			"label" => "Include static:",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "static_template",
				"foreign_table_where" => "ORDER BY static_template.title",
				"size" => 5,
				"maxitems" => 10,
				"default" => ""
			)
		),
		"config" => Array (
			"label" => "Setup:",
			"config" => Array (
				"type" => "text",
				"rows" => 10,
				"cols" => 48,
				"wrap" => "OFF"
			)
		),
		"description" => Array (
			"label" => "Description:",
			"config" => Array (
				"type" => "text",
				"rows" => 10,
				"cols" => 48
			)
		)
	),
	"types" => Array (					
		"1" => Array("showitem" => "title, constants, config, include_static, description")
	)
);






/*

Modules configuraion. 


Every entry in this array represents a tab in the upper left corner pane in the Typo3 interface


Add Modules: 
If the key (associative key) of the entry exists as a folder by that same name in 
the folder "mod/"-folder AND if that folder holds a $conf-file (with valid 
PHP-configuration inside!) then a tab is added to the pane. 
If this is not the case the entry is regarded as a "spacer" in the pane


Add Submodules: 
As long as the value of a valid (see above) module is empty ("") 
then no submodules are added. If not the value is regarded as a comma-sep. 
list of submodules. These must exist as subfolders to the modules folder 
and if the do not they are just regarded as spacers instead.

*/


$modules = Array (
	"web" => "layout,list,perm,ts,func,log,dmail",	// 
	"file" => "list,images",
	"doc" => "",	// This should always be empty!
	"spacer1"=>"",
	"user" => "",
	"tools" => "dbint,log,config",
	"help" => "about,quick"
);


?>