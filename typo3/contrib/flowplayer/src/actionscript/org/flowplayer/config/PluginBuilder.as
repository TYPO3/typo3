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
    import org.flowplayer.flow_internal;
    import org.flowplayer.model.Clip;
    import org.flowplayer.model.ClipType;
    import org.flowplayer.model.Playlist;
	
	import flash.display.DisplayObject;
	
	import org.flowplayer.model.DisplayPluginModel;
	import org.flowplayer.model.DisplayPluginModelImpl;
	import org.flowplayer.model.DisplayProperties;
	import org.flowplayer.model.DisplayPropertiesImpl;
	import org.flowplayer.model.Loadable;
	import org.flowplayer.model.Plugin;
	import org.flowplayer.model.PluginModel;
	import org.flowplayer.util.Log;
	import org.flowplayer.util.PropertyBinder;


    use namespace flow_internal;

	internal class PluginBuilder {

		private var log:Log = new Log(this);
		private var _pluginObjects:Object;
        private var _skinObjects:Object;
        private var _config:Config;
        private var _playerURL:String;
        private var _controlsVersion:String;
        private var _audioVersion:String;
        private var _loadables:Array;
		private var _callType:String

        public function PluginBuilder(playerSwfName:String, controlsVersion:String, audioVersion:String, config:Config, pluginObjects:Object, skinObjects:Object) {
            _playerURL = playerSwfName;
            _config = config;
            _pluginObjects = pluginObjects || new Object();
            _skinObjects = skinObjects || new Object();
            _controlsVersion = controlsVersion;
            _audioVersion = audioVersion;
            _loadables = [];
            updatePrototypedLoadableUrls();
			if(new RegExp("config={").exec(playerSwfName))
				_callType = "URL";
			else
				_callType = "default";
            log.debug("pluginObject ", _pluginObjects);
        }


        public function createLoadables(playlist:Playlist):Array {
            for (var name:String in _pluginObjects) {
                if (! isObjectDisabled(name, _pluginObjects) && (_pluginObjects[name].hasOwnProperty("url") || name == "controls" || name == "audio")) {
                    log.debug("creating loadable for '" + name + "', " + _pluginObjects[name]);
					_pluginObjects[name].callType = _callType;
                    _loadables.push(newLoadable(_pluginObjects, name));
                }
            }

            log.debug("initializing default loadables: controls and audio if needed");
            var builtIn:Boolean = isBuiltIn("controls");
            log.debug("controls is builtin? " + builtIn);
            if (! builtIn) {
                initLoadable("controls", _controlsVersion);
            }
            if (hasAudioClipsWithoutProvider(playlist) && ! isBuiltIn("audio")) {
                initLoadable("audio", _audioVersion);
            }
            createInStreamProviders(playlist, _loadables);
            return _loadables;
        }

        private function hasAudioClipsWithoutProvider(playlist:Playlist):Boolean {
            var clips:Array = playlist.clips; 
            for (var i:int; i < clips.length; i++) {
                var clip:Clip = clips[i] as Clip;

                if (ClipType.AUDIO == clip.type) {
                    return ! clip.clipObject || ! clip.clipObject.hasOwnProperty("provider");
                }
            }
            return false;
        }

        private function isBuiltIn(name:String):Boolean {
            return _pluginObjects[name] && _pluginObjects[name].hasOwnProperty("url") && String(_pluginObjects[name]["url"]).toLocaleLowerCase().indexOf(".swf") < 0;
        }

        private function updatePrototypedLoadableUrls():void {
            for (var name:String in _pluginObjects) {
                var plugin:Object = _pluginObjects[name];
                if (plugin && plugin.hasOwnProperty("prototype")) {
                    var prototype:Object = _pluginObjects[plugin["prototype"]];
                    if (! prototype) {
                        throw new Error("Prototype " + plugin["prototype"] + " not available");
                    }
                    log.debug("found a prototype reference '" + plugin["prototype"] + "', resolved to class name " + prototype.url);
                    plugin.url = prototype.url;
                }
            }
        }

        private function newLoadable(fromObjects:Object, name:String, nameInConf:String = null, url:String = null):Loadable {
            var loadable:Loadable = new PropertyBinder(new Loadable(name, _config), "config").copyProperties(fromObjects[nameInConf || name]) as Loadable;
            if (url) {
                loadable.url = url;
            }
            return loadable;
        }

        private function createInStreamProviders(playlist:Playlist, loadables:Array):void {
            var children:Array = playlist.childClips;
            for (var i:int = 0; i < children.length; i++) {
                var clip:Clip = children[i];
                if (clip.configuredProviderName != "http") {
                    var loadable:Loadable = findLoadable(clip.configuredProviderName);
                    if (loadable && ! findLoadable(clip.provider)) {
                        loadable = newLoadable(_pluginObjects, clip.provider, clip.configuredProviderName);
                        loadables.push(loadable);
                    }
                }
            }
        }

		private function isObjectDisabled(name:String, confObjects:Object):Boolean {
			if (! confObjects.hasOwnProperty(name)) return false;
			var pluginObj:Object = confObjects[name];
			return pluginObj == null;
		}
		
		private function initLoadable(name:String, version:String):Loadable {
            log.debug("createLoadable() '" + name + "' version " + version);
			if (isObjectDisabled(name, _pluginObjects)) {
				log.debug(name + " is disabled");
				return null;
			}
			var loadable:Loadable = findLoadable(name);

			if (! loadable) {
				loadable = new Loadable(name, _config);
                _loadables.push(loadable);
			} else {
				log.debug(name + " was found in configuration, will not automatically add it into loadables");
			}
			
			if (! loadable.url) {
				loadable.url = getLoadableUrl(name, version);
			}
            log.debug("createLoadable(), created loadable with url " + loadable.url)
            return loadable;
		}
		
		private function findLoadable(name:String):Loadable {
			for (var i:Number = 0; i < _loadables.length; i++) {
				var plugin:Loadable = _loadables[i];
				if (plugin.name == name) {
					return plugin;
				}
			}
			return null;
		}

		private function getLoadableUrl(name:String, version:String):String {
			var playerVersion:String = getPlayerVersion();
            log.debug("player version detected from SWF name is " + playerVersion);
			if (playerVersion) {
				return "flowplayer." + name + "-" + version + ".swf";
			} else {
				return "flowplayer." + name + ".swf";
			}
		}
		
		private function getPlayerVersion():String {
			var version:String = getVersionFromSwfName("flowplayer");
            if (version) return version;

            version = getVersionFromSwfName("flowplayer.commercial");
            if (version) return version;

            return getVersionFromSwfName("flowplayer.unlimited");
		}
		
		private function getVersionFromSwfName(swfName:String):String {
            log.debug("getVersionFromSwfName() " + playerSwfName);
			if (playerSwfName.indexOf(swfName + "-") < 0) return null;
			if (playerSwfName.indexOf(".swf") < (swfName + "-").length) return null;
            return playerSwfName.substring(playerSwfName.indexOf("-") + 1, playerSwfName.indexOf(".swf"));
		}

        private function get playerSwfName():String {
            var lastSlash:Number = _playerURL.lastIndexOf("/");
            return _playerURL.substring(lastSlash + 1, _playerURL.indexOf(".swf") + 4); 
        }


		public function getDisplayProperties(conf:Object, name:String, DisplayPropertiesClass:Class = null):DisplayProperties {
			if (isObjectDisabled(name, _skinObjects)) {
				log.debug(name + " is disabled");
				return null;
			}
			var props:DisplayProperties = DisplayPropertiesClass ? new DisplayPropertiesClass() as DisplayProperties : new DisplayPropertiesImpl();
			if (conf) {
				new PropertyBinder(props, null).copyProperties(conf);
			}
			props.name = name;
			return props;
		}
		
		public function getScreen(screenObj:Object):DisplayProperties {
			log.warn("getScreen " + screenObj);
			var screen:DisplayProperties = new DisplayPropertiesImpl(null, "screen", false);
			new PropertyBinder(screen, null).copyProperties(getScreenDefaults());
			if (screenObj) {
				log.info("setting screen properties specified in configuration");
				new PropertyBinder(screen, null).copyProperties(screenObj);
			}
			screen.zIndex = 0;
			return screen;
		}

		private function getScreenDefaults():Object {
			var screen:Object = new Object();
			screen.left = "50%";
			screen.bottom = "50%";
			screen.width = "100%";
			screen.height = "100%";
			screen.name = "screen";
			screen.zIndex = 0;
			return screen;
		}
		
		public function getPlugin(disp:DisplayObject, name:String, config:Object):PluginModel {
			var plugin:DisplayPluginModel = new PropertyBinder(new DisplayPluginModelImpl(disp, name, false), "config").copyProperties(config, true) as DisplayPluginModel;
			log.debug(name + " position specified in config " + plugin.position);
			
			// add defaults settings from the plugin instance (will not override those set in config)
			if (disp is Plugin) {
				log.debug(name + " implements Plugin, querying defaultConfig");
				var defaults:Object = Plugin(disp).getDefaultConfig();
				if (defaults) {
					fixPositionSettings(plugin, defaults);
					if (! (config && config.hasOwnProperty("opacity")) && defaults.hasOwnProperty("opacity")) {
						plugin.opacity = defaults["opacity"];
					}

					plugin = new PropertyBinder(plugin, "config").copyProperties(defaults, false) as DisplayPluginModel;
					log.debug(name + " position after applying defaults " + plugin.position + ", zIndex " + plugin.zIndex);
				}
			}
			return plugin;
		}
		
		private function fixPositionSettings(props:DisplayProperties, defaults:Object):void {
			clearOpposite("bottom", "top", props, defaults);
			clearOpposite("left", "right", props, defaults);
		}
		
		private function clearOpposite(prop1:String, prop2:String, props:DisplayProperties, defaults:Object):void {
			if (props.position[prop1].hasValue() && defaults.hasOwnProperty(prop2)) {
				delete defaults[prop2];
			} else if (props.position[prop2].hasValue() && defaults.hasOwnProperty(prop1)) {
				delete defaults[prop1];
			}
		}
	}
}
