function toggleElement(elementName)	{
	toggleEl = $(elementName);
	if (toggleEl.style.display == 'none')	{
		openLeaf(elementName);
	} else {
		closeLeaf(elementName);
	}
}

function openLeaf(elementName)	{
	if ($(elementName).style.display == 'none')	{
		img = $('img_'+elementName);
		if (img)	img.src = minusSrc;
		new Effect.SlideDown(elementName, {duration: 0.1});
	}
}

function closeLeaf(elementName)	{
	if ($(elementName).style.display != 'none')	{
		img = $('img_'+elementName);
		if (img)	img.src = plusSrc;
		new Effect.SlideUp(elementName, {duration: 0.1});
	}
}

function toggleHelp(elementName)	{
	toggleEl = $(elementName);
	if (toggleEl.style.display == 'none')	{
		new Effect.BlindDown(elementName, {duration: 0.1});
	} else {
		new Effect.BlindUp(elementName, {duration: 0.1});
	}
}


function toggleAllLeafs()	{
	var iconUrl = expandSrc;
	var label = labelExpand;
	
	if (!allOpen)	{
		iconUrl = collapseSrc;
		label = labelCollapse;
	}
	
	Element.setStyle('collapseExpandToggle', {backgroundImage: 'url('+iconUrl+')'});
	$('collapseExpandToggle').innerHTML = label;
	
	$$('.subLeaf').each(function(item, index)	{
		if (allOpen)	{
			closeLeaf(item.id);		
		} else {
			openLeaf(item.id);
		}
	});
	
	allOpen = !allOpen;
}

function sendForm(formId)	{
	var elements = $(formId).elements;
	var ajaxParameters = {saveData: true};
	for (var i = 0; i < elements.length; i++)	{
		var el = elements.item(i);
		switch (el.type)	{
			case 'text':
				ajaxParameters[el.name] = el.value;
				break;
			case 'checkbox':
				ajaxParameters[el.name] = (el.checked == true) ? 1 : 0;
				break;
		}
	};
	
	loadModuleContent(elements.categoryMain.value, elements.categorySub.value, ajaxParameters);
}

function sendMethodForm(formId, module, method, callBack)	{
	var elements = $(formId).elements;
	var ajaxParameters = {};
	for (var i = 0; i < elements.length; i++)	{
		var el = elements.item(i);
		switch (el.type)	{
			case 'checkbox':
				ajaxParameters[el.name] = (el.checked == true) ? 1 : 0;
				break;
			case 'text':
			default:
				ajaxParameters[el.name] = el.value;
				break;
		}
	};
	
	executeMethod(module, method, ajaxParameters, callBack);
	return false;
}

function doSearch()	{
	searchString = $('treeFilter').value;
	$$('.tree_item').each(function(item, index)	{ item.setStyle({'fontWeight': 'normal', backgroundColor: '#fff'}) });
	$$('.deliverable-box').each(function(item, index)	{ item.setStyle({backgroundColor: ''}) });
	
	if (searchString.length >= 2)	{
		$('filterStatus').innerHTML = '';
		executeMethod('setup', 'searchCategories', {'searchString': searchString}, processSearch);
	} else {
		$('filterStatus').innerHTML = '';
	}
}

function processSearch(transport)	{
	var results = eval('('+transport.responseText+')');
	
		// open the main cat
	if (results.resultCount > 0)	{
		for (property in results.catMain)	{
			openLeaf(property);
		}
		
			// highlight the subcat where something was found
		for (property in results.catSub)	{
			$('item_'+property).setStyle({fontWeight: 'bold', backgroundColor: '#99ff99'});
			

			results.catSub[property]._each(function(item, index)	{
				console.debug($('container_'+item['deliverable']));
				console.debug('container_'+item['deliverable']);
				$('container_'+item['deliverable']).setStyle({backgroundColor: '#99ff99'});
			});
		}
	}
	
	$('filterStatus').innerHTML = results.resultMessage;
}

/**
 * Sends an AJAX request and posts needed parameters to the server.
 * The result replaces the exting content with a fade effect.
 */
function loadModuleContent(catMain, catSub, extraParameters)	{
	var ajaxParameters = {
		ajax: 1,
		categoryMain: catMain,
		categorySub: catSub
	};

	if (extraParameters)	{
		ajaxParameters = $H(ajaxParameters).merge(extraParameters).toObject();
	}

	new Ajax.Request('../../typo3/install/index.php', {
		method: 'post',
		parameters: ajaxParameters,
		onSuccess: function(transport)	{
			new Effect.Fade('moduleContent', {duration: 0.2, afterFinish: function()	{
				$('moduleContent').innerHTML = transport.responseText;
				new Effect.Appear('moduleContent', {duration: 0.2});
			}});
		}
	});
}

function executeMethod(module, method, extraParameters, callBack)	{
	var ajaxParameters = {
		ajax: 1,
		module: module,
		method: method
	};

	if (extraParameters)	{
		ajaxParameters = $H(ajaxParameters).merge(extraParameters).toObject();
	}

	new Ajax.Request('../../typo3/install/index.php', {
		method: 'get',
		parameters: ajaxParameters,
		onSuccess: function(transport)	{
			if (callBack)	{
				callBack(transport);
			}
		}
	});
}

function displayMethodResult(data)	{
	console.debug(data);
	if (data.request.parameters.target)	{
		$(data.request.parameters.target).innerHTML = data.responseText;
	} else {
		// console.debug(data.responseText);
	}
}

/**
 * This function takes a list of checkboxes (identified by their ID)
 * and toggles them on or off depeding on the value of the flag
 */
function toggleCheckboxes(checkboxList, flag) {
	for (i = 0; i < checkboxList.length; i++) {
		$(checkboxList[i]).checked = flag;
	}
}

allOpen = false;

	// add event observers
Event.observe(window, 'load', function() {
	new Form.Element.DelayedObserver('treeFilter', 0.2, doSearch);
});
