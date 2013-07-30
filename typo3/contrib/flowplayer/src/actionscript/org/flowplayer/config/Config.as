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

package org.flowplayer.config {
    import flash.utils.ByteArray;

    import org.flowplayer.config.PluginBuilder;
	import org.flowplayer.controller.NetStreamControllingStreamProvider;
	import org.flowplayer.flow_internal;
    import org.flowplayer.model.Canvas;
	import org.flowplayer.model.Clip;
	import org.flowplayer.model.DisplayProperties;
	import org.flowplayer.model.Loadable;
	import org.flowplayer.model.Logo;
	import org.flowplayer.model.PlayButtonOverlay;
	import org.flowplayer.model.Playlist;
	import org.flowplayer.model.PluginModel;
	import org.flowplayer.model.ProviderModel;
	import org.flowplayer.util.Assert;
	import org.flowplayer.util.LogConfiguration;
	import org.flowplayer.util.PropertyBinder;
	
	import flash.display.DisplayObject;		
	
	use namespace flow_internal;

	/**
	 * @author anssi
	 */
	public class Config { 

		private var playList:Playlist;
		private var _configObject:Object;
		private var _pluginBuilder:PluginBuilder;
		private var _playlistBuilder:PlaylistBuilder;
		public var logFilter:String;
		private var _playerSwfUrl:String;
		private var _controlsVersion:String;
		private var _audioVersion:String;
		private var _loadables:Array;
        private var _canvas:Canvas;

		public function Config(config:Object, builtInConfig:Object, playerSwfUrl:String, controlsVersion:String, audioVersion:String) {
			Assert.notNull(config, "No configuration provided.");
			this._configObject = createConfigObject(config, builtInConfig);
			_playerSwfUrl = playerSwfUrl;
			_playlistBuilder = new PlaylistBuilder(playerId, this._configObject.playlist, this._configObject.clip);
			_controlsVersion = controlsVersion;
			_audioVersion = audioVersion;
		}

        private function createConfigObject(configured:Object, builtInConfig:Object):Object {
            var buffer:ByteArray = new ByteArray();
            buffer.writeObject(builtInConfig);
            buffer.position = 0;
            var result:Object = buffer.readObject();

            return copyProps(result, configured);
        }

        private function copyProps(target:Object, source:Object, propName:String = null):Object {
            if (source is Number || source is String || source is Boolean) {
                target = source;
                return target;
            }

            if (source is Array) {
                if (target.hasOwnProperty(propName)) {
                    for (var i:int = 0; i < source.length; i++) {
                        (target[propName] as Array).push(source[i]);
                    }
                }
                return target;
            }

            for (var key:String in source) {
                if (target.hasOwnProperty(key)) {
                    target[key] = copyProps(target[key], source[key], key);
                } else {
                    target[key] = source[key];
                }
            }
            return target;
        }

        flow_internal function set playlistDocument(docObj:String):void {
            _playlistBuilder.playlistFeed = docObj;
        }

		public function get playerId():String {
			return this._configObject.playerId;
		}

		public function createClip(clipObj:Object):Clip {
			return _playlistBuilder.createClip(clipObj);
		}
		
		public function createCuepoints(cueObjects:Array, callbackId:String, timeMultiplier:Number):Array {
			return _playlistBuilder.createCuepointGroup(cueObjects, callbackId, timeMultiplier);
		}

		public function createClips(playlist:Object = null):Array {
            return _playlistBuilder.createClips(playlist);
		}

		public function getPlaylist():Playlist {
            if (_configObject.playlist is String && ! _playlistBuilder.playlistFeed) {
                throw new Error("playlist queried but the playlist feed file has not been received yet");
            }
			if (! playList) {
				playList = _playlistBuilder.createPlaylist();
			}
			return playList;
		}

        public function getLoadables():Array {
            if (!_loadables) {
                _loadables = viewObjectBuilder.createLoadables(getPlaylist());
            }
            return _loadables;
        }

		private function getLoadable(name:String):Loadable {
			var loadables:Array = getLoadables();
			for (var i:Number = 0; i < loadables.length; i++) {
				var loadable:Loadable = loadables[i];
				if (loadable.name == name) {
					return loadable;
				}
			}
			return null;
		}
		
		private function get viewObjectBuilder():PluginBuilder {
			if (_pluginBuilder == null) {
				_pluginBuilder = new PluginBuilder(_playerSwfUrl, _controlsVersion, _audioVersion, this, _configObject.plugins, _configObject);
			}
			return _pluginBuilder;
		}
		
		public function getScreenProperties():DisplayProperties {
			return viewObjectBuilder.getScreen(getObject("screen"));
		}

		public function getPlayButtonOverlay():PlayButtonOverlay {
			var play:PlayButtonOverlay = viewObjectBuilder.getDisplayProperties(getObject("play"), "play", PlayButtonOverlay) as PlayButtonOverlay;
			if (play) {
				play.buffering = useBufferingAnimation;
			}
			return play;
		}
		
		public function getLogo(view:DisplayObject):Logo {
            return new PropertyBinder(new Logo(view, "logo"), null).copyProperties(getObject("logo"), true) as Logo;
		}
		
		public function getObject(name:String):Object {
			return _configObject[name];
		}
		
		public function getLogConfiguration():LogConfiguration {
			if (! _configObject.log) return new LogConfiguration();
			return new PropertyBinder(new LogConfiguration(), null).copyProperties(_configObject.log) as LogConfiguration;
		}
		
		public function get licenseKey():Object {
			return _configObject.key || _configObject.keys;
		}
		
		public function get canvas():Canvas {
            if (! _canvas) {
			var style:Object = getObject("canvas");
			if (! style) {
				style = new Object();
			}
			setProperty("backgroundGradient", style, [ 0.3, 0 ]);
			setProperty("border", style, "0px");
			setProperty("backgroundColor", style, "transparent");
			setProperty("borderRadius", style, "0");

            var result:Canvas = new Canvas();
            result.style = style;

            _canvas = new PropertyBinder(result, "style").copyProperties(style) as Canvas;
		}
            return _canvas;
		}

		private function setProperty(prop:String, style:Object, value:Object):void {
			if (! style[prop]) {
				style[prop] = value;
			}
		}
		
		public function get contextMenu():Array {
			return getObject("contextMenu") as Array;
		}
		
		public function getPlugin(disp:DisplayObject, name:String, config:Object):PluginModel {
			return viewObjectBuilder.getPlugin(disp, name, config);
		}
		
		public function get showErrors():Boolean {
			if (! _configObject.hasOwnProperty("showErrors")) return true;
			return _configObject["showErrors"];
		}
		
		public function get useBufferingAnimation():Boolean {
			if (! _configObject.hasOwnProperty("buffering")) return true;
			return _configObject["buffering"];
		}
		
		public function createHttpProvider(name:String):ProviderModel {
			var provider:NetStreamControllingStreamProvider =  new NetStreamControllingStreamProvider();
			
			var model:ProviderModel = new ProviderModel(provider, name);
			provider.model = model;
			
//			var conf:Loadable = config.http;
//			if (conf) {
//				new PropertyBinder(model).copyProperties(conf.config);
//			}
			return model;
		}

        public function get streamCallbacks():Array {
            return _configObject["streamCallbacks"];
        }

        public function get connectionCallbacks():Array {
            return _configObject["connectionCallbacks"];
        }

        public function get playlistFeed():String {
            return _configObject.playlist is String ? _configObject.playlist : null;
        }
        
        public function get playerSwfUrl():String {
        	return _playerSwfUrl;
        }

        public function get configObject():Object {
            return _configObject;
        }

    }
}
