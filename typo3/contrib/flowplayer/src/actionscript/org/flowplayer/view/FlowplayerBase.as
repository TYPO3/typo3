/**
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
    import flash.display.Loader;
    import flash.display.Stage;
    import flash.text.TextField;
    import flash.utils.getDefinitionByName;

    import org.flowplayer.config.Config;
    import org.flowplayer.controller.NetConnectionClient;
    import org.flowplayer.controller.PlayListController;
    import org.flowplayer.controller.ResourceLoader;
    import org.flowplayer.controller.ResourceLoaderImpl;
    import org.flowplayer.controller.StreamProvider;
    import org.flowplayer.flow_internal;
    import org.flowplayer.model.Clip;
    import org.flowplayer.model.ClipEventType;
    import org.flowplayer.model.Cuepoint;
    import org.flowplayer.model.DisplayPluginModel;
    import org.flowplayer.model.DisplayProperties;
    import org.flowplayer.model.DisplayPropertiesImpl;
    import org.flowplayer.model.ErrorCode;
    import org.flowplayer.model.EventDispatcher;
    import org.flowplayer.model.Loadable;
    import org.flowplayer.model.PlayerError;
    import org.flowplayer.model.PlayerEvent;
    import org.flowplayer.model.PlayerEventType;
    import org.flowplayer.model.Playlist;
    import org.flowplayer.model.Plugin;
    import org.flowplayer.model.PluginFactory;
    import org.flowplayer.model.PluginModel;
    import org.flowplayer.model.ProviderModel;
    import org.flowplayer.model.State;
    import org.flowplayer.model.Status;
    import org.flowplayer.util.Assert;
    import org.flowplayer.util.Log;
    import org.flowplayer.util.LogConfiguration;
    import org.flowplayer.util.PropertyBinder;
    import org.flowplayer.util.TextUtil;
    import org.flowplayer.util.TimeUtil;
    import org.flowplayer.util.VersionUtil;
    import org.flowplayer.util.URLUtil;
	import org.flowplayer.view.KeyboardHandler;
    import org.flowplayer.view.PlayButtonOverlayView;
    import org.flowplayer.view.PlayButtonOverlayView;

    use namespace flow_internal;

	/**
	 * @author anssi
	 */
	public class FlowplayerBase extends PlayerEventDispatcher implements ErrorHandler {

		protected var _playListController:PlayListController;
		protected var _pluginRegistry:PluginRegistry;
		protected var _config:Config;
		protected var _animationEngine:AnimationEngine;
		protected var _panel:Panel;

		private static var _instance:FlowplayerBase = null;
		private var _stage:Stage;
		private var _errorHandler:ErrorHandler;
		private var _fullscreenManager:FullscreenManager;
		private var _pluginLoader:PluginLoader;
		private var _playerSWFBaseURL:String;
		

		private var _keyHandler:KeyboardHandler;
		
		public function FlowplayerBase(
			stage:Stage, 
			pluginRegistry:PluginRegistry,
			panel:Panel, 
			animationEngine:AnimationEngine, 
			errorHandler:ErrorHandler, 
			config:Config, 
			playerSWFBaseURL:String) {

			// dummy references to get stuff included in the lib
			Assert.notNull(1);
			URLUtil.isCompleteURLWithProtocol("foo");
			
			var plug:Plugin;
			var plugFac:PluginFactory;
			var style:FlowStyleSheet;
			var styleable:StyleableSprite;
			var animation:Animation;
            var version:VersionUtil;
            var client:NetConnectionClient;
            var time:TimeUtil;

			if (_instance) {
				log.error("Flowplayer already instantiated");
				throw new Error("Flowplayer already instantiated");
			}
			_stage = stage;
//			registerCallbacks();
			_pluginRegistry = pluginRegistry;
			_panel = panel;
			_animationEngine = animationEngine;
			_errorHandler = errorHandler;
			_config = config;
			_playerSWFBaseURL = playerSWFBaseURL;
			_instance = this;

		}

        internal function set playlistController(control:PlayListController):void {
            _playListController = control;
            addStreamAndConnectionCallbacks();
        }

        internal function set fullscreenManager(value:FullscreenManager):void {
            _fullscreenManager = value;
            _fullscreenManager.playerEventDispatcher = this;            
        }
		
		/**
		 * Plays the current clip in playList or the specified clip.
		 * @param clip an optional clip to play. If specified it will replace the player's
		 * playlist.
		 */
		public function play(clip:Clip = null):FlowplayerBase {
			log.debug("play(" + clip + ")");
			_playListController.play(clip);
			return this;
		}

        /**
         * Starts playing the specified clip "in stream". The clip currently playing is paused
         * and the specified clip is started. When the instream clip is finished the original clip
         * is resumed.
         * @param clip
         * @return
         */
        public function playInstream(clip:Clip):void {
            if (! (isPlaying() || isPaused())) {
                handleError(PlayerError.INSTREAM_PLAY_NOTPLAYING);
                return;
            }
            // mark this clip to be "one shot" that will be removed once played
            clip.position = -2;
            addClip(clip, playlist.currentIndex);
            _playListController.playInstream(clip);
        }
        
        public function switchStream(clip:Clip, netStreamPlayOptions:Object = null):void {
        	log.debug("playSwitchStream(" + clip + ")");
        	_playListController.switchStream(clip, netStreamPlayOptions);
        }

		/**
		 * Starts buffering the current clip in playList.
		 */
		public function startBuffering():FlowplayerBase {
			log.debug("startBuffering()");
			_playListController.startBuffering();
			return this;
		}

		/**
		 * Stops buffering.
		 */
		public function stopBuffering():FlowplayerBase {
			log.debug("stopBuffering()");
			_playListController.stopBuffering();
			return this;
		}
		
		/**
		 * Pauses the current clip.
		 */
		public function pause(silent:Boolean = false):FlowplayerBase {
			log.debug("pause()");
			_playListController.pause(silent);
			return this;
		}
		
		/**
		 * Resumes playback of the current clip.
		 */
		public function resume(silent:Boolean = false):FlowplayerBase {
			log.debug("resume()");
			_playListController.resume(silent);
			return this;
		}
		
		/**
		 * Toggles between paused and resumed states.
		 * @return true if the player is playing after the call, false if it's paused
		 */
		public function toggle():Boolean {
			log.debug("toggle()");
			if (state == State.PAUSED) {
				resume();
				return true;
            } else if (state == State.WAITING) {
                play();
                return true;
			} else {
				pause();
				return false;
			}
			return false;
		}
		
		/**
		 * Is the player currently paused?
		 * @return true if the player is currently in the paused state
		 * @see #state
		 */
		public function isPaused():Boolean {
			return state == State.PAUSED;
		}

		/**
		 * Is the player currently playing?
		 * @return true if the player is currently in the playing or buffering state
		 * @see #state
		 */
		public function isPlaying():Boolean {
			return state == State.PLAYING || state == State.BUFFERING;
		}

		/**
		 * Stops the player and rewinds to the beginning of the playList.
		 */
		public function stop():FlowplayerBase {
			log.debug("stop()");
			_playListController.stop();
			return this;
		}
		
		/**
		 * Stops the player and closes the stream and connection.
         * Does not dispatch any events.
		 */
		public function close():FlowplayerBase {
			log.debug("close()");
            dispatch(PlayerEventType.UNLOAD, null, false);
			_playListController.close(true);
			return this;
		}
		
		/**
		 * Moves to next clip in playList.
		 */
		public function next():Clip {
			log.debug("next()");
			return _playListController.next(false);
		}
		
		/**
		 * Moves to previous clip in playList.
		 */
		public function previous():Clip {
			log.debug("previous()");
			return _playListController.previous();
		}
		
		/**
		 * Toggles between the full-screen and normal display modeds.
		 */
		public function toggleFullscreen():Boolean {
			log.debug("toggleFullscreen");
			if (dispatchBeforeEvent(PlayerEvent.fullscreen())) {
				_fullscreenManager.toggleFullscreen();
			}
			return _fullscreenManager.isFullscreen;
		}
		
		/**
		 * Is the volume muted?
		 */
		public function get muted():Boolean {
			return _playListController.muted;
		}
		
		/**
		 * Sets the volume muted/unmuted.
		 */
		public function set muted(value:Boolean):void {
			_playListController.muted = value;
		}
		
		/**
		 * Sets the volume to the specified level.
		 * @param volume the new volume value, must be between 0 and 100
		 */
		public function set volume(volume:Number):void {
			_playListController.volume = volume;
		}
		
		/**
		 * Gets the current volume level.
		 * @return the volume level percentage (0-100)
		 */
		public function get volume():Number {
			log.debug("get volume");
			return _playListController.volume;
		}

        public function hidePlugin(pluginName:String):void {
            var plugin:Object = _pluginRegistry.getPlugin(pluginName);
            checkPlugin(plugin, pluginName, DisplayProperties);
            doHidePlugin(DisplayProperties(plugin).getDisplayObject());
        }

        public function showPlugin(pluginName:String, props:Object = null):void {
            pluginPanelOp(doShowPlugin, pluginName, props);
        }

        public function togglePlugin(pluginName:String, props:Object = null):Boolean {
            return pluginPanelOp(doTogglePlugin, pluginName, props) as Boolean;
        }

        public function bufferAnimate(enable:Boolean = true):void {
            var playBtn:Object = playButtonOverlay.getDisplayObject();
            if (enable) {
                playBtn.startBuffering();
            } else {
                playBtn.stopBuffering();
            }
        }

        private function pluginPanelOp(func:Function, pluginName:String, props:Object = null):Object {
            var plugin:Object = _pluginRegistry.getPlugin(pluginName);
            checkPlugin(plugin, pluginName, DisplayProperties);
            return func(DisplayProperties(plugin).getDisplayObject(),
                (props ? new PropertyBinder(new DisplayPropertiesImpl(), null).copyProperties(props) : plugin) as DisplayProperties) ;
        }

		protected function doShowPlugin(disp:DisplayObject, displayProps:Object):void {
            var props:DisplayProperties;
            if (! (displayProps is DisplayProperties)) {
                props = new PropertyBinder(new DisplayPropertiesImpl(), null).copyProperties(displayProps) as DisplayProperties;
            } else {
                props = displayProps as DisplayProperties;
            }
			disp.alpha = props ? props.alpha : 1;
			disp.visible = true;
			props.display = "block";
			if (props.zIndex == -1) {
				props.zIndex = newPluginZIndex;
			}
			log.debug("showPlugin, zIndex is " + props.zIndex);
			if (playButtonOverlay && disp == playButtonOverlay.getDisplayObject()) {
				playButtonOverlay.getDisplayObject()["showButton"]();
			} else {
				_panel.addView(disp, null, props);
			}
            var pluginProps:DisplayProperties = _pluginRegistry.getPluginByDisplay(disp);
            if (pluginProps) {
                _pluginRegistry.updateDisplayProperties(props);
            }
		}

		private function doHidePlugin(disp:DisplayObject):void {
			if (disp.parent == screen && disp == playButtonOverlay.getDisplayObject()) {
				playButtonOverlay.getDisplayObject()["hideButton"]();
			} else if (disp.parent && ! (disp.parent is Loader)) {
				disp.parent.removeChild(disp);
			}
            var props:DisplayProperties = _pluginRegistry.getPluginByDisplay(disp);
            if (props) {
                props.display = "none";
                _pluginRegistry.updateDisplayProperties(props);
            }
		}

		public function doTogglePlugin(disp:DisplayObject, props:DisplayProperties = null):Boolean {
			if (disp.parent == _panel) {
				doHidePlugin(disp);
				return false;
			} else {
				doShowPlugin(disp, props);
				return true;
			}
		}
		
		
		/**
		 * Gets the animation engine.
		 */
		public function get animationEngine():AnimationEngine {
			return _animationEngine;
		}

		/**
		 * Gets the plugin registry.
		 */
		public function get pluginRegistry():PluginRegistry {
			return _pluginRegistry;
		}

		/**
		 * Seeks to the specified target second value in the clip's timeline.
		 */
		public function seek(seconds:Number, silent:Boolean = false):FlowplayerBase {
			log.debug("seek to " + seconds + " seconds");
			_playListController.seekTo(seconds, silent);
			return this;
		}
		
		/**
		 * Seeks to the specified point.
		 * @param the point in the timeline, between 0 and 100
		 */
		public function seekRelative(value:Number, silent:Boolean = false):FlowplayerBase {
			log.debug("seekRelative " + value + "%, clip is " + playlist.current);
			seek(playlist.current.duration * (value/100), silent);
			return this;
		}

		/**
		 * Gets the current status {@link PlayStatus}
		 */
		public function get status():Status {
			return _playListController.status;
		}

		/**
		 * Gets the player state.
		 */
		public function get state():State {
			return _playListController.getState();
		}

		/**
		 * Gets the playList.
		 */
		public function get playlist():Playlist {
			return _playListController.playlist;
		}

		/**
		 * Gets the current clip (the clip currently playing or the next one to be played when playback is started).
		 */
		public function get currentClip():Clip {
			return playlist.current;
		}
		
		/**
		 * Shows the specified error message in the player area.
		 */
		public function showError(message:String):void {
			_errorHandler.showError(message);
		}

		/**
		 * Handles the specified error.
		 */
		public function handleError(error:ErrorCode, info:Object = null, throwError:Boolean = true):void {
			_errorHandler.handleError(error, info);
		}

		/**
		 * Gets the Flowplayer version number.
		 * @return for example [3, 0, 0, "free", "release"] - the 4th element
		 * tells if this is the "free" version or "commercial", the 5th
		 * element specifies if this is an official "release" or a "development" version.
		 */
		public function get version():Array {
			// this is hacked like this because we cannot have imports to classes
			// that are conditionally compiled - otherwise this class cannot by compiled by compc
			// library compiler
			var VersionInfo:Class = Class(getDefinitionByName("org.flowplayer.config.VersionInfo"));
			return VersionInfo.version;
		}

		/**
		 * Gets the player's id.
		 */
		public function get id():String {
			return _config.playerId;
		}
		
		/**
		 * Loads the specified plugin.
		 * @param plugin the plugin to load
		 * @param callback a function to call when the loading is complete
		 */
		public function loadPlugin(pluginName:String, url:String, callback:Function):void {
			loadPluginLoadable(new Loadable(pluginName, _config, url), callback);
		}
		
		public function loadPluginWithConfig(name:String, url:String, properties:Object = null, callback:Function = null):void
		{
			var loadable:Loadable = new Loadable(name, _config, url);
			if (properties) {
				new PropertyBinder(loadable, "config").copyProperties(properties);
			}
			loadPluginLoadable(loadable, callback);
		}
		
		/**
		 * Creates a text field with default font. If the player configuration has a FontProvider
		 * plugin configured, we'll use that. Otherwise platform fonts are used, the platform font
		 * search string used to specify the font is:
		 * "Trebuchet MS, Lucida Grande, Lucida Sans Unicode, Bitstream Vera, Verdana, Arial, _sans, _serif"
		 */
		public function createTextField(fontSize:int = 12, bold:Boolean = false):TextField {
			if (fonts && fonts.length > 0) {
				return TextUtil.createTextField(true, fonts[0], fontSize, bold);
			}
			return TextUtil.createTextField(false, null, fontSize, bold);
		}

        /**
         * Adds the specified display object to the panel.
         * @param displayObject
         * @param props
         */
        public function addToPanel(displayObject:DisplayObject, props:Object, resizeListener:Function = null):void {
            var properties:DisplayProperties = props is DisplayProperties ? props as DisplayProperties : new PropertyBinder(new DisplayPropertiesImpl(), null).copyProperties(props) as DisplayProperties;
            _panel.addView(displayObject, resizeListener, properties);
        }

		protected function loadPluginLoadable(loadable:Loadable, callback:Function = null):void {
			var loaderCallback:Function = function():void {
				log.debug("plugin loaded");
				_pluginRegistry.setPlayerToPlugin(loadable.plugin);
				if (loadable.plugin is DisplayPluginModel) {
					var displayPlugin:DisplayPluginModel = loadable.plugin as DisplayPluginModel;
					if (displayPlugin.visible) {
						log.debug("adding plugin to panel");
						if (displayPlugin.zIndex < 0) {
							displayPlugin.zIndex = newPluginZIndex;
						}
						_panel.addView(displayPlugin.getDisplayObject(), null,  displayPlugin);
					}
				} else if (loadable.plugin is ProviderModel){
					_playListController.addProvider(loadable.plugin as ProviderModel);
				}
				
				if (callback != null && loadable.plugin != null ) {
					callback(loadable.plugin); 				
				}
			};
			_pluginLoader.loadPlugin(loadable, loaderCallback);
		}
		
		private function get newPluginZIndex():Number {
			var play:DisplayProperties = _pluginRegistry.getPlugin("play") as DisplayProperties;
			if (! play) return 100;
			return play.zIndex;
		}

		/**
		 * Gets the fonts that have been loaded as plugins.
		 */
		public function get fonts():Array {
			return _pluginRegistry.fonts;
		}
		
		/**
		 * Is the player in fullscreen mode?
		 */
		public function isFullscreen():Boolean {
			return _fullscreenManager.isFullscreen;
		}
		
		/**
		 * Resets the screen and the controls to their orginal display properties
		 */
		public function reset(pluginNames:Array = null, speed:Number = 500):void {
			if (! pluginNames) {
				pluginNames = [ "controls", "screen" ];
			}
			for (var i:Number = 0; i < pluginNames.length; i++) {
				resetPlugin(pluginNames[i], speed);
			}
		}
		
		/**
		 * Configures logging.
		 */
		public function logging(level:String, filter:String = "*"):void {
			var config:LogConfiguration = new LogConfiguration();
			config.level = level;
			config.filter = filter;
			Log.configure(config);
		}
		
		/**
		 * Flowplayer configuration.
		 */
		public function get config():Config {
			return _config;
		}

		/**
		 * Creates a new resource loader.
		 */		
		public function createLoader():ResourceLoader {
			return new ResourceLoaderImpl(_config.playerId ? null : _playerSWFBaseURL, this);
		}

        /**
         * Sets a new playlist.
         * @param playlist an array of Clip instances
         * @see ClipEventType#PLAYLIST_REPLACE
         */
        public function setPlaylist(playlist:Array):void {
            _playListController.setPlaylist(playlist);
            log.debug("setPlaylist, currentIndex is " + this.playlist.currentIndex);
        }

        /**
         * Adds a new clip into the playlist. Insertion of clips does not change the current clip.
         * You can also add instream clips like so:
         * <ul>
         * <li>position == 0, the clip is added as a preroll</li>
         * <li>position == -1, the clip is added as a postroll</li>
         * <li>position > 0, the clip is added as a midroll (linear instream)</li>
         * </ul>
         * @param clip
         * @param index optional insertion point, if not given the clip is added to the end of the list.
         */
        public function addClip(clip:Clip, index:int = -1):void {
            _playListController.playlist.addClip(clip, index);
        }


        /**
         * Creates Clip objects from the specified array of objects
         * @param clips
         * @return
         * @see Clip
         */
        public function createClips(clips:Array):Array {
            return _config.createClips(clips);
        }

		private function resetPlugin(pluginName:String, speed:Number = 500):void {
			var props:DisplayProperties = _pluginRegistry.getOriginalProperties(pluginName) as DisplayProperties;
			if (props) {
				_animationEngine.animate(props.getDisplayObject(), props, speed);
			}
		}

		protected function checkPlugin(plugin:Object, pluginName:String, RequiredClass:Class = null):void {
			if (! plugin) {
				showError("There is no plugin called '" + pluginName + "'");
				return;
			}
			if (RequiredClass && ! plugin is RequiredClass) {
				showError("Specifiec plugin '" + pluginName + "' is not an instance of " + RequiredClass);
			}
		}

        /**
         * Gets the Screen.
         * @return
         */
		public function get screen():DisplayProperties {
			return _pluginRegistry.getPlugin("screen") as DisplayProperties;
		}

		public function get playButtonOverlay():DisplayProperties {
			return DisplayProperties(_pluginRegistry.getPlugin("play")) as DisplayProperties;
		}
		
        private function addStreamAndConnectionCallbacks():void {
            createCallbacks(_config.connectionCallbacks, addConnectionCallback, ClipEventType.CONNECTION_EVENT);
            createCallbacks(_config.streamCallbacks, addStreamCallback, ClipEventType.NETSTREAM_EVENT);
        }

        private function addConnectionCallback(name:String, listener:Function):void {
            _playListController.addConnectionCallback(name, listener);
        }

        private function addStreamCallback(name:String, listener:Function):void {
            _playListController.addStreamCallback(name, listener);
        }

        private function createCallbacks(callbacks:Array, registerFunc:Function, type:ClipEventType):void {
            if (! callbacks) return;
            log.debug("registering "+callbacks.length+" callbakcs");
            for (var i:int = 0; i < callbacks.length; i++) {
                var name:String = callbacks[i];
                registerFunc(name, createCallbackListener(type, name));
            }
        }

        private function createCallbackListener(type:ClipEventType, name:String):Function {
            return function(infoObj:Object):void {
                log.debug("received callback " + type.name + " forwarding it " + (typeof infoObj));

                if (name == "onCuePoint") {
                    var cuepoint:Cuepoint = Cuepoint.createDynamic(infoObj["time"], "embedded");
                    for (var prop:String in infoObj) {
                        log.debug(prop + ": " + infoObj[prop]);
                        if (prop == "parameters") {
                            for (var param:String in infoObj.parameters) {
                                log.debug(param + ": " + infoObj.parameters[param]);
                                cuepoint.addParameter(param, infoObj.parameters[param]);
                            }
                        } else {
                            cuepoint[prop] = infoObj[prop];
                        }
                    }
                    playlist.current.dispatch(ClipEventType.forName(name), cuepoint);
                    return;
                }
                playlist.current.dispatch(ClipEventType.forName(name), createInfo(infoObj));
            };
        }

        private function createInfo(infoObj:Object):Object {
            if (infoObj is Number || infoObj is String || infoObj is Boolean) {
                return infoObj;
            }
            var result:Object = {};
            for (var prop:String in infoObj) {
                result[prop] = infoObj[prop];
            }
            return result;
        }

        public function set pluginLoader(val:PluginLoader):void {
            _pluginLoader = val;
        }

		public function set keyboardHandler(val:KeyboardHandler):void {
			_keyHandler = val;
			_keyHandler.player = this as Flowplayer;
		}

		public function isKeyboardShortcutsEnabled():Boolean {
			return _keyHandler.isKeyboardShortcutsEnabled();
		}
		
		public function setKeyboardShortcutsEnabled(enabled:Boolean):void {
			_keyHandler.setKeyboardShortcutsEnabled(enabled);
		}
		
		public function addKeyListener(keyCode:uint, func:Function):void {
			_keyHandler.addKeyListener(keyCode, func);
		}
		
		public function removeKeyListener(keyCode:uint, func:Function):void {
			_keyHandler.removeKeyListener(keyCode, func);
		}

        /**
         * Gets the StreamProvider of the current clip.
         * @return
         */
        public function get streamProvider():StreamProvider {
            return _playListController.streamProvider;
        }

        public function get panel():Panel {
            return _panel;
        }
    }
}
