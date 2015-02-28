//--------------------------------------------------------------------------
// initial variables that might be useful to change
//--------------------------------------------------------------------------

// Stage variables

Stage.scaleMode = "noScale";
Stage.align = "TL";

// Add link to rightclick menu

var newMenu:ContextMenu = new ContextMenu();
newMenu.hideBuiltInItems();
newMenu.customItems.push(new ContextMenuItem("TYPO3 Media Player...",goTo));
this.menu = newMenu;
function goTo() { getURL("http://typo3.org"); };

//_root.allowFullScreen = "true";
var screenMode:String = 'normal';

function fullScreen()
{
	if(screenMode == 'normal')
	{
      Stage["displayState"] = "fullScreen";
	  screenMode = 'full';
	}
	else
	{
		Stage["displayState"] = "normal";
		screenMode = 'normal';
	}
}

var stageSize:Object = new Object();
stageSize.onResize = function()
{
    w = Stage.width;
 h = Stage.height;
 setDims(w,h);
}
Stage.addListener(stageSize);


if(_root.previewSeek == 'true')
{
 makePre = true;
}
else
{
 makePre = false;
}


// toggle for which file to play if none was set in html
// you can change the 'test.flv' in your filename
if(!_root.file) {
	file = "video.flv";
} else {
	file = _root.file;
}

// toggle for autostarting the video
// you can change the 'true' in 'false'
if(_root.autoPlay == "true") {
	autoStart = true;
} else {
	autoStart = false;
}

if(!_root.smoothing) {
	smoothing = true;
} else {
	smoothing = _root.smoothing;
}
if(!_root.deblocking) {
	deblocking = 5;
} else {
	deblocking = _root.deblocking;
}
if(!_root.volume) {
	volume = 80;
} else {
	volume = _root.volume;
}
if(!_root.prebuffer) {
	prebuffer = 5;
} else {
	prebuffer = _root.prebuffer;
}
if(!_root.preview || _root.preview == "true") {
	preview = true;
}
if(!_root.previewSeek) {
	previewSeek = 0.1
} else {
	previewSeek = _root.previewSeek
}

if (!_root.clickAlpha) {
	clickAlpha = 65;
} else {
	clickAlpha = _root.clickAlpha;
}

if (!_root.clickText) {
	clickText = "";
} else {
	clickText = _root.clickText;
}

// toggle for the width and height of the video
// you can change them to the width and height you want
w = Stage.width;
h = Stage.height;

//--------------------------------------------------------------------------
// stream setup and functions
//--------------------------------------------------------------------------

// create and set netstream
nc = new NetConnection();
nc.connect(null);
ns = new NetStream(nc);
ns.setBufferTime(2);

// create and set sound object
this.createEmptyMovieClip("snd", 0);
snd.attachAudio(ns);
audio = new Sound(snd);

//attach videodisplay
videoDisplay.attachVideo(ns);

// Retrieve duration meta data from netstream
ns.onMetaData = function(obj) {
	this.totalTime = obj.duration;
	// these three lines were used for automatically sizing
	// it is now done by sizing the video to stage dimensions
	// if(obj.height > 0 && obj.height < Stage.height-20) {
	// setDims(obj.width, obj.height);
	// }
};

// retrieve status messages from netstream
ns.onStatus = function(object) {
	if(object.code == "NetStream.Play.Stop") {
		// rewind and pause on when movie is finished
		ns.seek(0);
		if(_root.repeat == "true") {
			return;
		}
		if(preview) {
			ns.seek(previewSeek);
		}
		ns.pause();
		playBut._visible = true;
		pauseBut._visible = false;
		if (!preview) {
			videoDisplay._visible = false;
		}
		showClick(true);
	}
	if (info.code == "NetStream.Buffer.Full") {
	if(makePre) {
			ns.seek(previewSeek);
			makePre = false;
    }
   }
};


//--------------------------------------------------------------------------
// controlbar functionality
//--------------------------------------------------------------------------

function showClick(show) {
	if (show) {
		if (_root.click) {
			if (!alignedClick) {
				clickImage.loadMovie(_root.click);
				clickImage._alpha = clickAlpha;
			}
			clickImage._visible = true;
		} else {
			playText.text = clickText;
		}
	} else {
		if (playText.text.length) {
			playText.text = "";
		}
		if (clickImage._visible) {
			clickImage._visible = false;
		}
	}
}

// play the movie and hide playbutton
function playMovie() {
	if(!isStarted) {
		audio.setVolume(volume);
		ns.setBufferTime(prebuffer);
		ns.play(file);
		isStarted = true;
	} else {
		showClick(false);
		ns.pause();
	}
	pauseBut._visible = true;
	playBut._visible = false;
	videoDisplay._visible = true;
}

// pause the movie and hide pausebutton
function pauseMovie() {
	ns.pause();
	playBut._visible = true;
	pauseBut._visible = false;
};

// video click action
videoBg.onPress = function() {
	if(pauseBut._visible == false) {
		playMovie();
	} else {
		pauseMovie();
	}
};

// pause button action
pauseBut.onPress = function() {
	pauseMovie();
};

// play button action
playBut.onPress = function() {
	playMovie();
};

// file load progress
progressBar.onEnterFrame = function() {
	if (isStarted) {
		loaded = this._parent.ns.bytesLoaded;
		total = this._parent.ns.bytesTotal;
		if (loaded == total && loaded > 1000) {
			this.loa._xscale = 100;
			delete this.onEnterFrame;
		} else {
			this.loa._xscale = int(loaded/total*100);
		}
	}
};

// play progress function
progressBar.tme.onEnterFrame = function() {
	if (isStarted) {
		this._xscale = ns.time/ns.totalTime*100;

		if (bufferPercent != -1) {
			if (!bufferPercent) {
				showClick(false);
			}
			bufferPercent = int(ns.bufferLength/ns.bufferTime*100);
			if (bufferPercent >= 100) {
				playText.text = "";
				bufferPercent = -1;
			} else {
				playText.text = "buffering .. " + bufferPercent + "%";
			}
		}
	} else if (inPreview && ns.time > 0) {
		ns.close();
		inPreview = false;
	}

	if (clickImage._width && !alignedClick) {
		clickImage._x = (videoDisplay._width - clickImage._width) / 2;
		clickImage._y = (videoDisplay._height - clickImage._height) / 2;
		alignedClick = true;
	}
};

// start playhead scrubbing
progressBar.loa.onPress = function() {
	this.onEnterFrame = function() {
		scl = (this._xmouse/this._width)*(this._xscale/100)*(this._xscale/100);
		if(scl < 0.02) { scl = 0; }
		ns.seek(scl*ns.totalTime);
		if (isStarted) {
			showClick(false);
		}
	};
};

// stop playhead scrubbing
progressBar.loa.onRelease = progressBar.loa.onReleaseOutside = function () {
	delete this.onEnterFrame;
	pauseBut._visible == false ? videoDisplay.pause() : null;
};


// fullscreen
if(_root.allowFullScreen == "true") {
	FSBut.onPress = function() {
		fullScreen();
	};
} else if (_root.fs == "true") {
	FSBut.onPress = function() {
		getURL("javascript: history.go(-1)");
	};
}



// volume scrubbing
volumeBar.back.onPress = function() {
	this.onEnterFrame = function() {
		var xm = this._xmouse;
		if(xm>=0 && xm <= 20) {
			this._parent.mask._width = this._xmouse;
			this._parent._parent.audio.setVolume(this._xmouse*5);
		}
	};
}
volumeBar.back.onRelease = volumeBar.back.onReleaseOutside = function() {
	delete this.onEnterFrame;
}

volumeBar.icn.onPress = function() {
	if (volumeBar.mute._visible) {
		setVolume(volume);
	} else {
		volume = audio.getVolume();
		setVolume(0);
	}
}

function setVolume(vol) {
	audio.setVolume(vol);
	volumeBar.mask._width = vol/5;
	if (vol > 0 && volumeBar.mute._visible) {
		volumeBar.mute._visible = false;
	} else if (!vol && !volumeBar.mute._visible) {
		volumeBar.mute._visible = true;
	}
}

setVolume(volume);


//--------------------------------------------------------------------------
// resize and position all items
//--------------------------------------------------------------------------
function setDims(w,h) {
	// set videodisplay dimensions
	videoDisplay._width = videoBg._width = w;
	videoDisplay._height = videoBg._height = h-20;
	playText1._x = w/2-120;
	playText1._y = h/2-20;
	playText2._x = playText1._x + 1;
	playText2._y = playText1._y + 1;

	// resize the controlbar items ..
	if(_root.fs == "true") {
		colorBar._y = playBut._y = pauseBut._y = progressBar._y = FSBut._y = volumeBar._y = h-30;
		playBut._x = pauseBut._x = colorBar._x = w/2-150;
		colorBar._width = 300;
		colorBar._alpha = 25;
		progressBar._x = w/2-133;
		progressBar._width = 228;
		FSBut._x = w/2+95;
		volumeBar._x = w/2+112;
		videoDisplay._height = h;
	} else {
		colorBar._y = playBut._y = pauseBut._y = progressBar._y = FSBut._y = volumeBar._y = h-18;
		progressBar._width = w-56;
		colorBar._width = w;
		volumeBar._x = w-38;
		if(_root.allowFullScreen == "true") {
			FSBut._visible = true;
			progressBar._width -=17;
			FSBut._x = w-55;
		} else {
			FSBut._visible = false;
		}
	}
}

// here you can ovverride the dimensions of the video
setDims(w,h);


//--------------------------------------------------------------------------
// all done ? start the movie !
//--------------------------------------------------------------------------

// start playing the movie
// if no autoStart it searches for a placeholder jpg
// and hides the pauseBut

pauseBut._visible = false;
videoDisplay.smoothing = smoothing;
videoDisplay.deblocking = deblocking;
if (autoStart == true) {
	playMovie();
} else {
	showClick(true);
	if (preview) {
		inPreview = true;
		audio.setVolume(0);
		ns.play(file);
		ns.seek(previewSeek);
	} else {
		if(_root.image) {
			imageStr = _root.image;
		} else {
			imageStr = file.substring(0,file.length-3)+"jpg";
			if (imageStr.substring(0,2) == "..") {
				imageStr = imageStr.substring(3);
			}
		}
		imageClip.loadMovie(imageStr);
	}
}
