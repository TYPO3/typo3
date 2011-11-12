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
    import org.flowplayer.controller.ClipURLResolver;
    import org.flowplayer.controller.ConnectionProvider;
    import org.flowplayer.controller.StreamProvider;
	import org.flowplayer.controller.NetStreamControllingStreamProvider;	
	import org.flowplayer.model.DisplayPluginModel;	
	import org.flowplayer.model.Cloneable;
	import org.flowplayer.model.DisplayProperties;
	import org.flowplayer.model.Plugin;
	import org.flowplayer.model.PluginModel;
	import org.flowplayer.model.ProviderModel;
	import org.flowplayer.util.Assert;
	import org.flowplayer.util.Log;
	import org.flowplayer.util.PropertyBinder;
	
	import flash.display.DisplayObject;
	import flash.utils.Dictionary;		

	/**
	 * @author api
	 */
	public class PluginRegistry {

		private var log:Log = new Log(this);
		private var _plugins:Dictionary = new Dictionary();
		private var _originalProps:Dictionary = new Dictionary();
		private var _providers:Dictionary = new Dictionary();
		private var _genericPlugins:Dictionary = new Dictionary();
		private var _fonts:Array = new Array();
		private var _panel:Panel;
		private var _flowPlayer:FlowplayerBase;

		public function PluginRegistry(panel:Panel) {
			_panel = panel;
		}
		
		/**
		 * Gets all plugins.
		 * @return the plugins keyed by the plugin name
		 */
		public function get plugins():Dictionary {
			return _plugins;
		}
		
		/**
		 * Gets all providers.
		 * @return the providers keyed by the plugin name
		 */
		public function get providers():Dictionary {
			return _providers;
		}

		/**
		 * Gets a plugin by it's name.
		 * @return the plugin mode, this is a clone of the current model and changes made
		 * to the returned object are not reflected to the copy stored in this registrty
		 */
		public function getPlugin(name:String):Object {
			var plugin:Object = _plugins[name] || _providers[name] || _genericPlugins[name];
			log.debug("found plugin " + plugin);
			if (plugin is DisplayProperties) {
				updateZIndex(plugin as DisplayProperties);
			}
            return plugin;
//			return clone(plugin);
		}
		
		private function updateZIndex(props:DisplayProperties):void {
			var zIndex:int = _panel.getZIndex(props.getDisplayObject());
			if (zIndex >= 0) {
				props.zIndex = zIndex;
			}
		}

		private function clone(obj:Object):Object {
			return obj && obj is Cloneable ? Cloneable(obj).clone() : obj;
		}

		/**
		 * Gets plugin's model corresponding to the specified DisplayObject.
		 * @param disp the display object whose model is looked up
		 * @param return the display properties, or <code>null</code> if a plugin cannot be found
		 */
		public function getPluginByDisplay(disp:DisplayObject):DisplayProperties {
			for each (var plugin:DisplayProperties in _plugins) {
				if (plugin.getDisplayObject() == disp) {
					updateZIndex(plugin);
					return plugin;
				}
			}
			return null;
		}

		/**
		 * Gets all FontProvider plugins.
		 * @return an array of FontProvider instances configured or loaded into the player
		 * @see FontProvider
		 */
		public function get fonts():Array {
			return _fonts;
		}

		/**
		 * Gets the original display properties. The original values were the ones
		 * configured for the plugin or as the ones specified when the plugin was loaded.
		 * @param pluginName
		 * @return a clone of the original display properties, or <code>null</code> if there is no plugin
		 * corresponding to the specified name
		 */
		public function getOriginalProperties(pluginName:String):DisplayProperties {
			return clone(_originalProps[pluginName]) as DisplayProperties;
		}

		internal function registerFont(fontFamily:String):void {
			_fonts.push(fontFamily);
		} 

		public function registerDisplayPlugin(plugin:DisplayProperties, view:DisplayObject):void {
            log.debug("registerDisplayPlugin() " + plugin.name);
			plugin.setDisplayObject(view);
			_plugins[plugin.name] = plugin;
			_originalProps[plugin.name] = plugin.clone();
		}
        
		internal function registerProvider(model:ProviderModel):void {
			log.info("registering provider " + model);
			_providers[model.name] = model;
		}
		
		internal function registerGenericPlugin(model:PluginModel):void {
			log.info("registering generic plugin " + model.name);
			_genericPlugins[model.name] = model;
		}
		
		internal function removePlugin(plugin:PluginModel):void {
            if (! plugin) return;
			delete _plugins[plugin.name];
			delete _originalProps[plugin.name];
			delete _providers[plugin.name];
			
			if (plugin is DisplayPluginModel) {
				_panel.removeChild(DisplayPluginModel(plugin).getDisplayObject());
			}
		}
		
		public function updateDisplayProperties(props:DisplayProperties, updateOriginalProps:Boolean = false):void {
			Assert.notNull(props.name, "displayProperties.name cannot be null");
			var view:DisplayObject = DisplayProperties(_plugins[props.name]).getDisplayObject();
			if (view) {
				props.setDisplayObject(view);
			}
			_plugins[props.name] = props.clone();
			if (updateOriginalProps) {
				_originalProps[props.name] = props.clone();
			}
		}
        
        public function update(plugin:PluginModel):void {
            _plugins[plugin.name] = plugin.clone();
        }
		
		internal function updateDisplayPropertiesForDisplay(view:DisplayObject, updated:Object):void {
			var props:DisplayProperties = getPluginByDisplay(view);
			if (props) {
				new PropertyBinder(props).copyProperties(updated);
				updateDisplayProperties(props);
			}
		}
		
		internal function onLoad(flowPlayer:FlowplayerBase):void {
			log.debug("onLoad");
			_flowPlayer = flowPlayer;
			setPlayerToPlugins(_providers);
			setPlayerToPlugins(_plugins);
			setPlayerToPlugins(_genericPlugins);
		}

		private function setPlayerToPlugins(plugins:Dictionary):void {
			// we need to create a copy because any change to the 
			// dictionary during the foreach makes it start again, 
			// which causes double onLoad calls
			
			var transientCopy:Dictionary = new Dictionary();
			for ( var name:String in plugins )
				transientCopy[name] = plugins[name];
			
			for each (var model:Object in transientCopy) {
				setPlayerToPlugin(model);
			}
		}
		
		internal function setPlayerToPlugin(plugin:Object):void {
			var pluginObj:Object;
			try {
				log.debug("setPlayerToPlugin " + plugin);
				if (plugin is DisplayProperties) {
					pluginObj = DisplayProperties(plugin).getDisplayObject(); 
				} else if (plugin is PluginModel) {
					pluginObj = PluginModel(plugin).pluginObject; 
				}
				if (pluginObj is NetStreamControllingStreamProvider) {
					log.debug("setting player to " + pluginObj);
					NetStreamControllingStreamProvider(pluginObj).player = _flowPlayer as Flowplayer;
				} else {
					pluginObj["onLoad"](_flowPlayer);
				}
				log.debug("onLoad() successfully executed for plugin " + plugin);
			} catch (e:Error) {
				if (pluginObj is Plugin || pluginObj is StreamProvider) {
					throw e;
				}
				log.warn("was not able to initialize player to plugin " + plugin + ": "+ e.message);
			}
		}

		internal function addPluginEventListener(listener:Function):void {
			for each (var model:Object in _plugins) {
				if (model is PluginModel) {
					PluginModel(model).onPluginEvent(listener);
				}
			}
		}

        public function getUrlResolvers():Array {
            var result:Array = [];
            for (var name:String in _genericPlugins) {
                var model:PluginModel = _genericPlugins[name] as PluginModel;
                var plugin:Object = model.pluginObject;
                if (plugin is ClipURLResolver && ! (plugin is ConnectionProvider)) {
                    result.push(name);
                }
            }
            result.sort();
            return result;
        }
    }
}
