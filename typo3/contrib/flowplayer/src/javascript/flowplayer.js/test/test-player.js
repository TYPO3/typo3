

function TestPlayer(player) {

	var self = this;
	var playerId = player.id();

	// clip information
	var index = 0;	
	var duration = 25;
	var bytesTotal = 3345;
	
	// status information
	var bytesLoaded = 0;
	var state = 1;	
	var volume = 50;	
	var time = 0;
	
	
	// fire onLoad
	setTimeout(function() { 
		Flowplayer.fireEvent(playerId, "onLoad");
		
		// setup buffer loading
		var timer = setInterval(function() {	
			bytesLoaded += 32;
			if (bytesLoaded > bytesTotal) {
				bytesLoaded = bytesTotal;
				clearInterval(timer);	
			}			
		}, 100);
		
	}, 200);

	
	this.play = function(clip) {
		
		console.log(playerId, "play", clip);
		clip = clip || 0;		
		index = clip >= 0 ? clip : clip.index;		
		state = 2;
		
		// setup playHead running
		var timer = setInterval(function() {
			time += 100;
			if (time >= (duration * 1000)) {
				clearInterval(timer);	
			}
			
		}, 100);
		
		
		setTimeout(function() { 
			console.log("metadata fired", index);
			Flowplayer.fireEvent(playerId, "onMetaData", index, {duration:duration, bytesTotal: bytesTotal});
		}, 100);		
		
		setTimeout(function() {
			Flowplayer.fireEvent(playerId, "onPlay", index);
			state = 3;
		}, 150);
		
		setTimeout(function() { 
			Flowplayer.fireEvent(playerId, "onCuePoint", index, 4);
		}, 700);
		
		setTimeout(function() { 
			Flowplayer.fireEvent(playerId, "onFinish", index);
			state = 5;
		}, duration * 1000);
		
	};
	
	this.getVersion = function() {
		return [3,0,0];	
	};
		
	
	this.pause = function() {
		console.log(playerId, "pause");
		state = 5;
		setTimeout(function() {
			Flowplayer.fireEvent(playerId, "onPause", index);
		}, 50);		
	};
	
	this.resume = function() {
		console.log(playerId, "resume");
		state = 3;
		setTimeout(function() {
			Flowplayer.fireEvent(playerId, "onResume", index);
		}, 50);		
	};	
	
	this.setVolume = function(level) {
		volume = level;
		setTimeout(function() {
			Flowplayer.fireEvent(playerId, "onVolume");
		}, level);				
	};
	
	
	

	this.status = function() {
		return {
			state:state,
			time:(time / 1000), 
			volume:volume, 
			bytesLoaded:bytesLoaded
		};	
	};
	
	this.state = function() {
		return -1;	
	};
	
	
	this.addCuePoints = function(cuePoints, index) {
		console.log(self.getVersion(), "addCuePoints", cuePoints, index || -1);
	};

	this.plugin = function(name) {
		return {
			top: 10,
			left: 50,
			opacity: 0.4,
			methods: ['html', 'append', 'setStyle']
		}
	};
	
	this.plugin_load = function(name, url, properties) {
		return {
			top: 40,
			left: 113,
			opacity: 0.9,
			methods: ['load', 'camelize', 'geekalize']
		}	
	};

	
	this.plugin_animate = function(pluginName, props, speed, callbackId) {
		console.log("plugin_animate", arguments);
		setTimeout(function() { Flowplayer.fireEvent(playerId, "onAnimate", callbackId); }, speed);
	};
	
	this.plugin_invoke = function(pluginName, methodName, args) {
		console.log("plugin_invoke", pluginName, methodName, args);	
		return true;
	};
	
	
}

