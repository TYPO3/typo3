<?php
// ******************************************************************
// This is the TypoScript polls table, tt_poll
// ******************************************************************

t3lib_div::loadTCA("tt_content");
$TCA["tt_content"]["columns"]["records"]["config"]["allowed"] .= ",tt_poll";

$PAGES_TYPES["default"]["allowedTables"] .= ",tt_poll";


$TCA[tt_poll] = Array (
	"ctrl" => Array (
		"label" => "title",
		"default_sortby" => "ORDER BY title",
		"tstamp" => "tstamp",
		"delete" => "deleted",
		"crdate" => "crdate",
		"enablecolumns" => Array (
			"disabled" => "hidden",
			"starttime" => "starttime"
//			"endtime" => "endtime"
// this is handled through the script - voteform with endtime, result without endtime
		),
		"title" => "Poll|Afstemning|Abstimmung||Sondaggio|Qestionnaire|Encuesta|Poll|||Anketa||Anket"
	),
	"interface" => Array (
		"showRecordFieldList" => "title,question,hidden,votes"
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
		"question" => Array (
			"label" => "Question:|Spørgsmål:|Frage:||Domanda:|Question:|Pregunta:|Vraag:|||Vprašanje:||Soru:",
			"config" => Array (
				"type" => "text",
				"cols" => "40",
				"rows" => "2"
			)
		),
		"answers" => Array (
			"label" => "Answers:|Svar:|Antworten:||Risposte:|Réponses:|Respuestas:|Antwoorden:|||Odgovori:||Cevaplar:",
			"config" => Array (
				"type" => "text",
				"cols" => "40",
				"rows" => "5"
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
		"recordlink" => Array (
			"label" => "Link to Record:|Reference til element:|Verknüpfung:||Link to Record:|Liens vers un enregistrement:|Ligar al registro:|Link naar Record:|||Povezava do zapisa:||Kayýta link:",
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
		"starttime" => Array (
			"exclude" => 1,
			"label" => $LANG_GENERAL_LABELS["starttime"],
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
			"label" => $LANG_GENERAL_LABELS["endtime"],
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
			"label" => $LANG_GENERAL_LABELS["hidden"],
			"config" => Array (
				"type" => "check",
				"default" => "1"
			)
		)
	),
	"types" => Array (
		"1" => Array("showitem" => "hidden, title, question, answers, votes, starttime, endtime, recordlink")
	)
);
?>
