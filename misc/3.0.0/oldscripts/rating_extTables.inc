<?php
// ******************************************************************
// This is the TypoScript ratings table, tt_rating
// ******************************************************************

t3lib_div::loadTCA("tt_content");
$TCA["tt_content"]["columns"]["records"]["config"]["allowed"] .= ",tt_rating";

$PAGES_TYPES["default"]["allowedTables"] .= ",tt_rating";

/* this may be already in tables.php
$TCA[tt_content]["columns"]["list_type"]["config"]["items"] = array_merge ( $TCA[tt_content]["columns"]["list_type"]["config"]["items"],
	Array(
		Array("Rating||Bewertung", "8")
	)
);
*/

$TCA[tt_rating] = Array (
	"ctrl" => Array (
		"label" => "title",
		"default_sortby" => "ORDER BY title",
		"tstamp" => "tstamp",
		"delete" => "deleted",
		"crdate" => "crdate",
		"enablecolumns" => Array (
			"disabled" => "hidden"
		),
		"title" => "Rating|Karaktergivning|Bewertung||Rating|Votation|Calificación|Beoordeling|||Glasovanje||Rating"
	),
	"interface" => Array (
		"showRecordFieldList" => "title,description,hidden,rating,votes"
	),
	"columns" => Array (
		"title" => Array (
			"label" => $LANG_GENERAL_LABELS["title"],
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		),
		"description" => Array (
			"label" => $LANG_GENERAL_LABELS["description"],
			"config" => Array (
				"type" => "text",
				"cols" => "40",
				"rows" => "5"
			)
		),
		"rating" => Array (
			"label" => "Rating:|Karakter:|Note:||Rating:|Votation:|Calificación:|Beoordeling:|||Glasovanje:||Rating:",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "trim,double2",
				"default" => "0"
			)
		),
		"votes" => Array (
			"label" => "Votes:|Stemmer:|Anzahl Stimmen:||Voti:|Votes:|Votos:|Stemmen:|||Glasov:||Oylar:",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "12",
				"eval" => "int",
				"default" => 0
			)
		),
		"ratingstat" => Array (
			"label" => "Statistics:|Statestik:|Statistik:||Statistiche:|Statistiques:|Estadísticas:|Statistieken:|||Statistika:||Ýstatistikler:",
			"config" => Array (
				"type" => "input",
				"size" => "40",
				"max" => "256"
			)
		),
		"recordlink" => Array (
			"label" => "Link to Record:|Reference til element:|Verknüpfung:||Link to record:|Liens vers un enregistrement:|Ligar al registro:|Link naar Record:|||Povezava do zapisa:||Kayýta link:",
			"config" => Array (
				"type" => "group",
				"internal_type" => "db",
				"allowed" => $TCA[tt_content]["columns"]["records"]["config"]["allowed"],
							/* "tt_content,tt_address,tt_links,tt_board,tt_guest,tt_calender,tt_products",   */
				"size" => "1",
				"maxitems" => "1",
				"minitems" => "0",
				"show_thumbs" => "1"
			)
		),
		"hidden" => Array (
			"exclude" => 1,
			"label" => $LANG_GENERAL_LABELS["hidden"],
			"config" => Array (
				"type" => "check",
				"default" => "1"
			)
		)
	),
	"types" => Array (
		"1" => Array("showitem" => "hidden, title, description, rating, votes, recordlink")
	)
);
?>