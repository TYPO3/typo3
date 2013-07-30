
/*
 *    Copyright (c) 2008-2011 Flowplayer Oy *
 *    This file is part of Flowplayer.
 *
 *    Flowplayer is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    Flowplayer is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with Flowplayer.  If not, see <http://www.gnu.org/licenses/>.
 */

package org.flowplayer.view {
    import flash.display.DisplayObject;
    import flash.display.DisplayObjectContainer;
    import flash.events.MouseEvent;
    import flash.events.TimerEvent;
    import flash.utils.Timer;
    import flash.utils.getDefinitionByName;

    import org.flowplayer.controller.ResourceLoader;
    import org.flowplayer.model.Clip;
    import org.flowplayer.model.ClipEvent;
    import org.flowplayer.model.ClipEventSupport;
    import org.flowplayer.model.DisplayPluginModel;
    import org.flowplayer.model.DisplayProperties;
    import org.flowplayer.model.PlayButtonOverlay;
    import org.flowplayer.model.Playlist;
    import org.flowplayer.model.Plugin;
    import org.flowplayer.model.PluginEventType;
    import org.flowplayer.model.PluginModel;
    import org.flowplayer.model.State;
    import org.flowplayer.model.Status;
    import org.flowplayer.util.Arrange;
    import org.flowplayer.util.AccessibilityUtil;
    import org.flowplayer.view.BuiltInAssetHelper;

    public class PlayButtonOverlayView extends AbstractSprite implements Plugin {
		
		private var _button:DisplayObject;
		private var _pluginRegistry:PluginRegistry;

		private var _player:Flowplayer;
		private var _showButtonInitially:Boolean;
		private var _tween:Animation;
		private var _resizeToTextWidth:Boolean;
		private var _screen:Screen;
		private var _playlist:Playlist;
		private var _origAlpha:Number;
		private var _play:PlayButtonOverlay;
        private var _rotation:RotatingAnimation;
        private var _playDetectTimer:Timer;

		public function PlayButtonOverlayView(resizeToTextWidth:Boolean, play:PlayButtonOverlay, pluginRegistry:PluginRegistry) {
			_resizeToTextWidth = resizeToTextWidth;
			_pluginRegistry = pluginRegistry;
			_pluginRegistry.registerDisplayPlugin(play, this);
			_play = play;
			createChildren();
			buttonMode = true;

            //#443 set accessibility for play button
            AccessibilityUtil.setAccessible(this,  "play");
			
            startBuffering();
			
			addEventListener(MouseEvent.MOUSE_OVER, onMouseOver);
			addEventListener(MouseEvent.MOUSE_OUT, onMouseOut);
		}

        public function set playlist(playlist:Playlist):void {
            _playlist = playlist;
            addListeners(playlist);            
        }
		

		[External]
        public function set label(label:String):void {
            _play.label = label;
            switchLabel(label);
            _pluginRegistry.update(_play);
        }

        private function switchLabel(label:String):void {
            if (! _player) return;
            log.debug("switchLabel() label '" + label + "'");
			if (label && (! _button || ! (_button is LabelPlayButton))) {
				log.debug("switching to label button ");
				switchButton(new LabelPlayButton(_player, label));
			}
			if (! label && (! _button || (_button is LabelPlayButton))) {
                log.debug("switching to standard non-label button ");
				switchButton(new PlayOverlay());
			}
			if (label) {
				LabelPlayButton(_button).setLabel(label, _resizeToTextWidth);
			}
			onResize();
		}

        [External]
        public function set replayLabel(label:String):void {
            if (! _player) return;
            log.debug("set replayLabel '" + label + "'");
            _play.replayLabel = label;
            _pluginRegistry.update(_play);
        }

        CONFIG::commercialVersion {
            [External]
            public function set image(url:String):void {
                log.debug("set image() will show? " + (_button.parent == this));
                _play.url = url;
                loadImage(url, null, _button.parent == this);
                _pluginRegistry.update(_play);
            }
        }

		override public function set alpha(value:Number):void {
			log.debug("setting alpha to " + value + " tween " + _tween);
			super.alpha = value;
			if (_button) {
				_button.alpha = value;
			}
			_rotation.alpha = value;
		}

		private function switchButton(newButton:DisplayObject):void {
			removeChildIfAdded(_button);
			_button = newButton;
            if (_button is AbstractSprite) {
                AbstractSprite(_button).setSize(width - 15, height - 15);
            }
		}

		private function onMouseOut(event:MouseEvent = null):void {
			if (!_button) return;
			_button.alpha = Math.max(0, model.alpha - 0.3);
		}

		private function onMouseOver(event:MouseEvent):void {
			if (!_button) return;
			_button.alpha = model.alpha;
		}

		public function onLoad(player:Flowplayer):void {
			log.debug("onLoad");
			// we need the player to be as the ErrorHandler before loading the image file
			_player = player;

			if (_play.label && _showButtonInitially) {
				showButton(null, _play.label);
			}
			
			CONFIG::commercialVersion {
				if (useLoadedImage()) {
					loadImage(_play.url, function():void {
                        _play.dispatch(PluginEventType.LOAD);
                    }, _showButtonInitially);
				} else {
					log.debug("dispatching complete");
					_play.dispatch(PluginEventType.LOAD);
			}
			}
			CONFIG::freeVersion {
				log.debug("dispatching complete");
				_play.dispatch(PluginEventType.LOAD);
			}
		}
		
		CONFIG::commercialVersion
		private function useLoadedImage():Boolean {
			return Boolean(_play.url && ! _play.label && ! BuiltInAssetHelper.hasPlayButton);
		}

		private function addListeners(eventSupport:ClipEventSupport):void {
			//eventSupport.onConnect(showButton); // bug #38
			eventSupport.onConnect(startBuffering);

            // onBegin is here because onBeforeBegin is not dispatched when playing after a timed out and invalid netConnection
//            eventSupport.onStart(hideButton);
//            eventSupport.onStart(createPlaybackStartedCallback);

            eventSupport.onBeforeBegin(hideButton);
			eventSupport.onBegin(bufferUntilStarted);

			eventSupport.onResume(hide);
            eventSupport.onResume(bufferUntilStarted);

			// onPause: call stopBuffering first and then showButton (stopBuffering hides the button)
			eventSupport.onPause(stopBuffering);
			eventSupport.onPause(showButton);

			eventSupport.onStop(stopBuffering);
			eventSupport.onStop(showButton, isParentClip);
			
			// onBeforeFinish: call stopBuffering first and then showButton (stopBuffering hides the button)
			eventSupport.onBeforeFinish(stopBuffering);

			eventSupport.onBeforeFinish(showReplayButton, isParentClipOrPostroll);

            // showing the buffer animation on buffer empty causes trouble with live streams and also on other cases
            //#395 apply buffer animation status to VOD streams only.
			eventSupport.onBufferEmpty(startBuffering, applyForClip);

            //#415 regression issue with #395, stop the buffering animation correctly.
			eventSupport.onBufferFull(stopBuffering, applyForClip);
			
            eventSupport.onBeforeSeek(bufferUntilStarted);
            eventSupport.onSeek(stopBuffering);

			eventSupport.onBufferStop(stopBuffering);
			eventSupport.onBufferStop(showButton);
		}

        private function applyForClip(clip:Clip):Boolean {
            // #474
            if (_player.status.time >= clip.duration - 2) return false;

            return !clip.live;
        }

        private function isParentClip(clip:Clip):Boolean {
            return ! clip.isInStream;
        }

        private function isParentClipOrPostroll(clip:Clip):Boolean {
            return clip.isPostroll || ! clip.isInStream;
        }

		private function rotate(event:TimerEvent):void {
			_rotation.rotation += 10;
		}
		
		private function createChildren():void {			
			_rotation = new RotatingAnimation();
            //addChild(_rotation); // bug #38
            
			if (! _play.label) {
				createInternalButton();
			}
		}

        private function createInternalButton():void {
            _button = BuiltInAssetHelper.createPlayButton() || new PlayOverlay();
			addButton();
			onResize();
		}

        private function getClass(name:String):Class {
            return getDefinitionByName(name) as Class;
        }

		private function addButton():void {
			log.debug("addButton");
			if (model.visible) {
				addChild(_button);
			}
		}

		CONFIG::commercialVersion
		private function loadImage(url:String, callback:Function = null, show:Boolean = false):void {
			log.debug("loading a custom button image from url " + url + ", will show? " + show);
			_player.createLoader().load(url, function(loader:ResourceLoader):void {
                initializeButtonImage(loader.getContent() as DisplayObject, show);
                if (callback != null) {
                    callback();
                }
            });
		}
		
		CONFIG::commercialVersion
		private function initializeButtonImage(image:DisplayObject, show:Boolean):void {
            switchButton(image);
            _button.alpha = model.alpha;
            log.debug("loaded image " + _play.url);
            if (show) {
                log.debug("showing button");
                showButton();
            }
			onResize();
		}

		protected override function onResize():void {
			log.debug("onResise " + width);
			if (! _button) return;
			onMouseOut();
			if (_button is LabelPlayButton) {
				AbstractSprite(_button).setSize(width - 15, height - 15);
			} else {
				_button.height = height;
				_button.scaleX = _button.scaleY;
			}
			_rotation.setSize(width, height);
			
			Arrange.center(_button, width, height);
			log.debug("arranged to y " + _button.y + ", this height " + height + ", screen height " + (_screen ? _screen.height : 0));
		}

		private function hide(event:ClipEvent = null):void {
			log.debug("hide()");
			if (! this.parent) return;
			if (_player) {
				log.debug("fading out with speed " + _play.fadeSpeed + " current alpha is " + alpha);
//				_screen.hidePlay();
				_origAlpha = model.alpha;
				_tween = _player.animationEngine.fadeOut(_button, _play.fadeSpeed, onFadeOut, false);
			} else {
				onFadeOut();
			}
		}
		
		private function onFadeOut():void {
			restoreOriginalAlpha();
			if (_tween && _tween.canceled) {
				_tween = null;
				return;
			}
			_tween = null;
			log.debug("removing button");
			
			removeChildIfAdded(_button);
//			_screen.hidePlay();
		}
		
		private function show():void {
			if (_tween) {
				restoreOriginalAlpha();
				log.debug("canceling fadeOut tween");
				_tween.cancel();
			}
			
			if (_screen && this.parent == _screen) {
				_screen.arrangePlay();
				return;
			}
			
			if (_screen) {
				log.debug("calling screen.showPlay");
				_screen.showPlay();
			}
		}
		
		private function restoreOriginalAlpha():void {
			alpha = _origAlpha;
			var play:DisplayProperties = model;
			play.alpha = _origAlpha;
			_pluginRegistry.updateDisplayProperties(play);
		}

		public function showButton(event:ClipEvent = null, label:String = null):void {
			log.debug("showButton(), label " + label);

			// we only support labels if a custom button is not defined
			CONFIG::commercialVersion {
				if (! _play.url) {
					switchLabel(label || _play.label);
				}
			}
			CONFIG::freeVersion {
				switchLabel(label || _play.label);
			}
			
			if (! _button) return;
			if (_rotation.parent == this) return;
			
			if (event == null) {
				// not called based on event --> update display props
			
				var props:DisplayProperties = model;
				props.display = "block";
				_pluginRegistry.updateDisplayProperties(props);
			}
            // #474
            stopBuffering();
			addButton();
			show();
			onResize();
		}
		
		public function showReplayButton(event:ClipEvent = null):void {
            
			log.info("showReplayButton, playlist has more clips " + _playlist.hasNext(false));
			if (event.isDefaultPrevented() && _playlist.hasNext(false)) {
				// default prevented, will stop after current clip. Show replay button.
				log.debug("showing replay button");
				showButton(null, _play.replayLabel);
				return; 
			}
            if (_playlist.hasNext(false) && _playlist.nextClip.autoPlay) {
                return;
            }
			showButton(event, _playlist.hasNext(false) ? null : _play.replayLabel);
		}
		
		public function hideButton(event:ClipEvent = null):void {
			log.debug("hideButton() " + _button);
			removeChildIfAdded(_button);
		}
		
		public function startBuffering(event:ClipEvent = null):void {
			log.debug("startBuffering()" + event);
            if (event && event.isDefaultPrevented()) return;
			if (!_play.buffering) return;

//			if (_button && _button.parent == this) {
//				// already showing button, don't show buffering
//				return;
//			}
			addChild(_rotation);
			
			// bug #62
			if ( _tween && _player && _player.state == State.PLAYING ) {
				removeChildIfAdded(_button);
			}
			
			show();
			_rotation.start();
		}
		
		public function stopBuffering(event:ClipEvent = null):void {
			log.debug("stopBuffering()");
			_rotation.stop();
			removeChildIfAdded(_rotation);
			if (! _tween && _player.state == State.BUFFERING || _player.state == State.BUFFERING) {
				removeChildIfAdded(_button);
			}
		}

		private function removeChildIfAdded(child:DisplayObject):void {
			if (! child) return;
			if (child.parent != this) return;
			log.debug("removing child " + child);
			removeChild(child);
		}
		
		public function onConfig(configProps:PluginModel):void {
		}
		
		public function getDefaultConfig():Object {
			return null;
		}
		
		public function setScreen(screen:Screen, showInitially:Boolean = false):void {
			_screen = screen;
			_showButtonInitially = showInitially;
			if (showInitially) {
				showButton();
			}
			startBuffering();
		}
		
		private function get model():DisplayPluginModel {
			return DisplayPluginModel(_pluginRegistry.getPlugin("play"));
		}

        private function bufferUntilStarted(event:ClipEvent = null):void {
            if (event && event.isDefaultPrevented()) return;
            startBuffering();
            createPlaybackStartedCallback(stopBuffering);
        }

        private function createPlaybackStartedCallback(callback:Function):void {
            log.debug("detectPlayback()");

            if (! _player.isPlaying()) {
                log.debug("detectPlayback(), not playing, returning");
                return;
            }
            if (_playDetectTimer && _playDetectTimer.running) {
                log.debug("detectPlayback(), not playing, returning");
                return;
            }

            var time:Number = _player.status.time;

            _playDetectTimer = new Timer(200);
            _playDetectTimer.addEventListener(TimerEvent.TIMER,
                    function(event:TimerEvent):void {
                        var currentTime:Number = _player.status.time;
                        log.debug("on detectPlayback() currentTime " + currentTime + ", time " + time);

                        if (Math.abs(currentTime - time) > 0.2) {
                            _playDetectTimer.stop();
                            log.debug("playback started");
                            callback();
                        } else {
                            log.debug("not started yet, currentTime " + currentTime + ", time " + time);
                        }
                    });
            log.debug("doStart(), starting timer");
            _playDetectTimer.start();
        }
    }
}
