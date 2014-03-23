Event.observe(document, "dom:loaded", function(){
	var changeEffect;
	Sortable.create("task-list", { handles:$$("#task-list .drag"), tag: "li", ghosting:false, overlap:"vertical", constraint:false,
	 onChange: function(item) {
		 var list = Sortable.options(item).element;
		 // deactivate link
		$$("#task-list a").each(function(link) {
			link.writeAttribute("onclick","return false;");
		});

	 },

	 onUpdate: function(list) {
		 new Ajax.Request(TYPO3.settings.ajaxUrls['Taskcenter::saveSortingState'], {
			 method: "post",
			 parameters: { data: Sortable.serialize(list)}
		 });
			// activate link
		 Event.observe(window,"mouseup",function(){
			$$("#task-list a").each(function(link) {
				link.writeAttribute("onclick","");
			});
		});

	 }
	});

	$$("#taskcenter-menu .down").invoke("observe", "click", function(event){
		var item = Event.element(event);
		var itemParent = item.up();
		item = item.next("div").next("div").next("div").next("div");

		if (itemParent.hasClassName("expanded")) {
			itemParent.removeClassName("expanded").addClassName("collapsed");
			Effect.BlindUp(item, {duration : 0.5});
			state = 1;
		} else {
			itemParent.removeClassName("collapsed").addClassName("expanded");
			Effect.BlindDown(item, {duration : 0.5});
			state = 0;
		}
		new Ajax.Request(TYPO3.settings.ajaxUrls['Taskcenter::saveCollapseState'], {
			parameters : "item=" + itemParent.id + "&state=" + state
		});
	});
});
