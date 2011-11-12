/**
 * flowplayer.playlist.js 3.0.0. Flowplayer JavaScript plugin.
 * 
 * This file is part of Flowplayer, http://flowplayer.org
 *
 * Author: Tero Piirainen, <support@flowplayer.org>
 * Copyright (c) 2009 Flowplayer Ltd
 *
 * Dual licensed under MIT and GPL 2+ licenses
 * SEE: http://www.opensource.org/licenses
 * 
 * Version: 3.0.0 - Tue Nov 25 2008 16:30:11 GMT-0000 (GMT+00:00)
 */
(function($) {
	
	$f.addPlugin("captions", function(container, options) {
	
		// self points to current Player instance
		var self = this;	
		var api = null;
		var opts = {
			activeClass: 'active',
			template: '<img src="images/${time}.jpg"/>',
			padTime: true,
			fadeTime: 500
		};		
		
		$.extend(opts, options);
		var wrap = container;
		var template = null;
		
		//wrap = $(wrap);		
		//alert(wrap.html());
		//var template = wrap.is(":empty") ? opts.template : wrap.html(); 
		var el = "";
		//wrap.empty();		
			
	
		function seek()
		{
			var status = api.getStatus(); 	
			alert(status.index);
		}
		
		function parseTemplate(values)
		{
			$.each(values, function(key, val) {
	
				if (typeof val == 'object')
				{	
					parseTemplate(key);
				} else {
					if (key == "time") {
						val = Math.round(val / 1000);
						if (opts.padTime && val < 10) val = "0" + val;
					}
					
					el = el.replace("$\{" +key+ "\}", val).replace("$%7B" +key+ "%7D", val);

				}
			});
		}
		
		// onStart
		self.onStart(function(clip) {
		
			var index = 1;
			
			wrap = $(wrap);		
			template = wrap.is(":empty") ? opts.template : wrap.html(); 
			wrap.fadeOut(opts.fadeTime).empty();
			
		   // wrap.empty();
		
			$.each(clip.cuepoints, function(key, val) {	
				
				el = template;
				if (val !== null) {
					var time = Math.round(val[0].time / 1000);
					parseTemplate(val[0]);
	
					el = $(el);	
					el.attr("index",index);
					index++;
					el.click(function() {	
						self.seek(time);
						api.seekTo($(this).attr("index"));
						//api.next();
					});
			
					wrap.append(el);
				}
			});
			
			if (wrap.parent().css('display') == "none") {
				wrap.show();
				wrap.parent('div').fadeIn(opts.fadeTime);
			} else {
				wrap.fadeIn(opts.fadeTime);
			}
			
			
			$(wrap.parent()).scrollable({items:wrap,size:4, clickable:true, activeClass: opts.activeClass});
		    api = $(wrap.parent()).scrollable();
		    
	
			$("a.prevPage").click(function() {
				api.prevPage(500);			
			});
			
			$("a.prevPage").mouseover(function() {
				api.prevPage(500);			
			});
			
			$("a.nextPage").click(function() {
				api.nextPage(500);			
			});	 
			
			$("a.nextPage").mouseover(function() {
				api.nextPage(500);			
			});	
	
			els = wrap.children();

			
		});	
		
		
		self.onCuepoint(function(clip, cuepoint) { 
			
			//var cue = els.filter("[@time=" + cuepoint.time + "]");
			//api.move();
			api.next();
		//	alert(api.getIndex());
			//alert(wrap.html());
			//self.getPlugin("scrollable").next();
			//console.log(cue.text());
			//alert(cuepoint.time);
		       //alert("embedded cuepoint entered, time: " + cuepoint.time); 
		});
		/*
		// onPause	
		self.onPause(function(clip) {
			getEl(clip).removeClass(opts.playingClass).addClass(opts.pausedClass);		
		});	
		
		// onResume
		self.onResume(function(clip) {
			getEl(clip).removeClass(opts.pausedClass).addClass(opts.playingClass);		
		});		
		
		// what happens when clip ends ?
		if (!opts.loop && !manual) {
			
			// stop the playback exept on the last clip, which is stopped by default
			self.onBeforeFinish(function(clip) {
				if (clip.index < els.length -1) {
					return false;
				}
			}); 
		}*/
		
		// on manual setups perform looping here
		/*if (manual && opts.loop) {
			self.onBeforeFinish(function(clip) {
				var el = getEl(clip);
				if (el.next().length) {
					el.next().click();	 		
				} else {
					els.eq(0).click();	
				} 
				return false;				
			}); 
		}*/   
		
		// onUnload
		self.onUnload(function() {
			clearCSS();		
		});
		
		
		return self;
		
	});
		
})(jQuery);		
