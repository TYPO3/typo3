/*---------------------------------------*\
 Find and Replace Plugin for HTMLArea-3.0
 -----------------------------------------
 author: Cau guanabara 
 e-mail: caugb@ibest.com.br
\*---------------------------------------*/

	var tosearch = '';
	var pater = null;
	var buffer = null;
	var matches = 0;
	var replaces = 0;
	var fr_spans = new Array();
		
	function execSearch (params) {
		var ihtml = editor._doc.body.innerHTML;
		if (buffer == null) {
			buffer = ihtml;
		}
		
		if (params.fr_pattern != tosearch) {
			if (tosearch != "") {
				clearDoc();
			}
			tosearch = params.fr_pattern;
		}
		
		if (matches == 0) {
			er = params.fr_words ? "/(?!<[^>]*)(\\b"+params.fr_pattern+"\\b)(?![^<]*>)/g" : "/(?!<[^>]*)("+params.fr_pattern+")(?![^<]*>)/g";
			if (!params.fr_matchcase) {
				er += "i";
			}
			
			pater = eval(er);
			
			var tago = '<span id=frmark>';
			var tagc = '</span>';
			var newHtml = ihtml.replace(pater,tago+"$1"+tagc);
			editor.setHTML(newHtml);
			
			var getallspans = editor._doc.body.getElementsByTagName("span"); 
			for (var i = 0; i < getallspans.length; i++) {
				if (/^frmark/.test(getallspans[i].id)) {
					fr_spans.push(getallspans[i]);
				}
			}
		}
		spanWalker(params['fr_pattern'],params['fr_replacement'],params['fr_replaceall']);
	};
	
	function spanWalker (pattern,replacement,replaceall) {
		var foundtrue = false;
		clearMarks();
		
		for (var i = matches; i < fr_spans.length; i++) {
			var elm = fr_spans[i];
			foundtrue = true;
			if (!(/[0-9]$/.test(elm.id))) { 
				matches++;
				disable('fr_clear', false);
				elm.id = 'frmark_'+ matches;
				elm.style.color = 'white';
				elm.style.backgroundColor = 'highlight';
				elm.style.fontWeight = 'bold';
				elm.scrollIntoView(false);
				if (replaceall || confirm(dialog.plugin.localize("Substitute this occurrence?"))) {
					elm.firstChild.replaceData(0,elm.firstChild.data.length,replacement);
					replaces++;
					disable('fr_undo', false);
				}
				if (replaceall) {
					clearMarks();
					continue;
				}
				break;
			}
		}
		var last = (i >= fr_spans.length - 1);
		if (last || !foundtrue) { // EOF
			var message = dialog.plugin.localize("Done")+":\n\n";
			if (matches > 0) {
				if (matches == 1) {
					message += matches+' ' + dialog.plugin.localize("found item");
				} else {
					message += matches+' ' + dialog.plugin.localize("found items");
				}
				if (replaces > 0) {
					if (replaces == 1) {
						message += ',\n'+replaces+' ' + dialog.plugin.localize("replaced item");
					} else {
						message += ',\n'+replaces+' ' + dialog.plugin.localize("replaced items");
					}
				}
				hiliteAll();
				disable('fr_hiliteall', false);
			} else {
				message += '"' + pattern + '" ' + dialog.plugin.localize("not found");
			}
			alert(message+'.');
		}
	};
	
	function clearDoc () {
		var doc = editor._doc.body.innerHTML; 
		var er = /(<span\s+[^>]*id=.?frmark[^>]*>)([^<>]*)(<\/span>)/gi;
		editor._doc.body.innerHTML = doc.replace(er,"$2");
		pater = null;
		tosearch = '';
		fr_spans = new Array();
		matches = 0;
		replaces = 0;
		disable("fr_hiliteall,fr_clear", true);
	};
	
	function clearMarks () {
		var getall = editor._doc.body.getElementsByTagName("span"); 
		for (var i = 0; i < getall.length; i++) {
			var elm = getall[i];
			if (/^frmark/.test(elm.id)) {
				var objStyle = editor._doc.getElementById(elm.id).style;
				objStyle.backgroundColor = "";
				objStyle.color = "";
				objStyle.fontWeight = "";
			}
		}
	};
	
	function hiliteAll () {
		var getall = editor._doc.body.getElementsByTagName("span"); 
		for (var i = 0; i < getall.length; i++) {
			var elm = getall[i];
			if (/^frmark/.test(elm.id)) {
				var objStyle = editor._doc.getElementById(elm.id).style;
				objStyle.backgroundColor = "highlight";
				objStyle.color = "white";
				objStyle.fontWeight = "bold";
			}
		}
	};
	
	function resetContents () {
		if (buffer == null) return;
		var transp = editor._doc.body.innerHTML;
		editor._doc.body.innerHTML = buffer;
		buffer = transp;
	};
	
	function disable (elms, toset) {
		var names = elms.split(/[,; ]+/);
		for (var i = 0; i < names.length; i++) {
			document.getElementById(names[i]).disabled = toset;
		}
	};
	
