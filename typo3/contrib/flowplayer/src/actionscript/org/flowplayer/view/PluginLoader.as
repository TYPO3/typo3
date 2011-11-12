/*    
 *    Copyright 2008 Flowplayer Oy
 *
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
    import flash.display.AVM1Movie;
import flash.system.Security;

    import org.flowplayer.model.ErrorCode;
    import org.flowplayer.model.Plugin;
	import org.flowplayer.controller.NetStreamControllingStreamProvider;	
	
	import com.adobe.utils.StringUtil;
	
	import org.flowplayer.config.ExternalInterfaceHelper;
	import org.flowplayer.controller.StreamProvider;
	import org.flowplayer.model.Callable;
	import org.flowplayer.model.DisplayPluginModel;
	import org.flowplayer.model.FontProvider;
	import org.flowplayer.model.Loadable;
	import org.flowplayer.model.PlayerError;
    import org.flowplayer.model.PluginError;
    import org.flowplayer.model.PluginEvent;
    import org.flowplayer.model.PluginModel;
	import org.flowplayer.model.ProviderModel;
	import org.flowplayer.util.Log;
	import org.flowplayer.util.URLUtil;
	
	import flash.display.DisplayObject;
	import flash.display.Loader;
	import flash.display.LoaderInfo;
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import flash.events.IOErrorEvent;
	import flash.events.ProgressEvent;
	import flash.net.URLRequest;
	import flash.system.ApplicationDomain;
	import flash.system.LoaderContext;
	import flash.system.SecurityDomain;
	import flash.utils.Dictionary;
	import flash.utils.getDefinitionByName;
	import flash.utils.getQualifiedClassName;


    /**
	 * @author api
	 */
	public class PluginLoader extends EventDispatcher {

		private var log:Log = new Log(this);
		private var _loadables:Array;
		private var _loadedPlugins:Dictionary;
		private var _loadedCount:int;
		private var _errorHandler:ErrorHandler;
		private var _swiffsToLoad:Array;
		private var _pluginRegistry:PluginRegistry;
		private var _providers:Dictionary;
		private var _callback:Function;
		private var _baseUrl:String;
		private var _useExternalInterface:Boolean;
		private var _loadErrorListener:Function;
		private var _loadListener:Function;
        private var _loadComplete:Boolean;
        private var _allPlugins:Array;
        private var _loaderContext:LoaderContext;
        private var _loadStartedCount:int = 0;

		public function PluginLoader(baseUrl:String, pluginRegistry:PluginRegistry, errorHandler:ErrorHandler, useExternalInterface:Boolean) {
			_baseUrl = baseUrl;
			_pluginRegistry = pluginRegistry;
			_errorHandler = errorHandler;
			_useExternalInterface = useExternalInterface;
			_loadedCount = 0;
		}

		private function constructUrl(url:String):String {
			if (url.indexOf("..") >= 0) return url;
			if (url.indexOf("/") >= 0) return url;
			return URLUtil.addBaseURL(_baseUrl, url);
		}

		public function loadPlugin(model:Loadable, callback:Function = null):void {
			_callback = callback;
            _loadListener = null;
            _loadErrorListener = null;
			load([model]);
		}

		public function load(plugins:Array, loadListener:Function = null, loadErrorListener:Function = null):void {
			log.debug("load()");
            _loadListener = loadListener;
            _loadErrorListener = loadErrorListener;

            Security.allowDomain("*");

			_providers = new Dictionary();
            _allPlugins = plugins.concat([]);
			_loadables = plugins.filter(function(plugin:*, index:int, array:Array):Boolean {
                return plugin.url && String(plugin.url).toLocaleLowerCase().indexOf(".swf") > 0;
            });
			_swiffsToLoad = getPluginSwiffUrls(plugins);

			_loadedPlugins = new Dictionary();
			_loadedCount = 0;
            _loadStartedCount = 0;

			_loaderContext = new LoaderContext();
			_loaderContext.applicationDomain = ApplicationDomain.currentDomain;
			if (!URLUtil.localDomain(_baseUrl)) {
				_loaderContext.securityDomain = SecurityDomain.currentDomain;
			}

            for (var i:Number = 0; i < _loadables.length; i++) {
                Loadable(_loadables[i]).onError(_loadErrorListener);
            }

            intitializeBuiltInPlugins(plugins);
            if (_swiffsToLoad.length == 0) {
                setConfigPlugins();
                dispatchEvent(new Event(Event.COMPLETE, true, false));
                return;
            }

            loadNext();
		}

        private function loadNext():Boolean {
            if (_loadStartedCount >= _swiffsToLoad.length) {
                log.debug("loadNext(): all plugins loaded");
                return false;
            }

            var loader:Loader = new Loader();
            loader.contentLoaderInfo.addEventListener(Event.COMPLETE, loaded);
            var url:String = _swiffsToLoad[_loadStartedCount];

            loader.contentLoaderInfo.addEventListener(IOErrorEvent.IO_ERROR, createIOErrorListener(url));
            loader.contentLoaderInfo.addEventListener(ProgressEvent.PROGRESS, onProgress);
            log.debug("starting to load plugin from url " + _swiffsToLoad[_loadStartedCount]);
            loader.load(new URLRequest(url), _loaderContext);
            _loadStartedCount++;
            return true;
        }

        private function getPluginSwiffUrls(plugins:Array):Array {
            var result:Array = new Array();
            for (var i:Number = 0; i < plugins.length; i++) {
                var loadable:Loadable = Loadable(plugins[i]);
                if (! loadable.isBuiltIn && loadable.url && result.indexOf(loadable.url) < 0) {
                    result.push(constructUrl(loadable.url));
                }
            }
            return result;
        }

        private function intitializeBuiltInPlugins(plugins:Array):void {
            for (var i:int = 0; i < plugins.length; i++) {
                var loadable:Loadable = plugins[i] as Loadable;
                log.debug("intitializeBuiltInPlugins() " + loadable);
                if (loadable.isBuiltIn) {
                    log.info("intitializeBuiltInPlugins(), instantiating from loadable " + loadable + ", with config ", loadable.config);
                    var instance:Object = loadable.instantiate();
                    var model:PluginModel = createPluginModel(loadable, instance);
                    model.isBuiltIn = true;
//                    if (instance.hasOwnProperty("onConfig")) {
//                        instance.onConfig(model);
//                    }
                    initializePlugin(model, instance);
                }
            }
        }
		
        private function createIOErrorListener(url:String):Function {
            return function(event:IOErrorEvent):void {
                log.error("onIoError " + url);
                _loadables.forEach(function(loadable:Loadable, index:int, array:Array):void {
                    if (! loadable.loadFailed && hasSwiff(url, loadable.url)) {
                        log.debug("onIoError: this is the swf for loadable " + loadable);
                        loadable.loadFailed = true;
                        loadable.dispatchError(PluginError.INIT_FAILED);
                        incrementLoadedCountAndFireEventIfNeeded();
                    }
                });
            };
        }

		private function onProgress(event:ProgressEvent):void {
			log.debug("load in progress");
		}


		public function get plugins():Dictionary {
			return _loadedPlugins;
		}

		private function loaded(event:Event):void {
			var info:LoaderInfo = event.target as LoaderInfo;
			log.debug("loaded class name " + getQualifiedClassName(info.content));

			var instanceUsed:Boolean = false;
			_loadables.forEach(function(loadable:Loadable, index:int, array:Array):void {
				if (! loadable.plugin && hasSwiff(info.url, loadable.url)) {
					log.debug("this is the swf for loadable " + loadable);
					if (loadable.type == "classLibrary") {
						initializeClassLibrary(loadable, info);
					} else {
                        var plugin:Object = info.content is AVM1Movie ? info.loader : createPluginInstance(instanceUsed, info.content);
						initializePlugin(createPluginModel(loadable, plugin), plugin);
						//initializePlugin(loadable, instanceUsed, info);
						instanceUsed = true;
					}
				}
			});
            incrementLoadedCountAndFireEventIfNeeded();
			if (_callback != null) {
				_callback();
			}
            loadNext();
		}

        private function incrementLoadedCountAndFireEventIfNeeded():void {
            if (++_loadedCount == _swiffsToLoad.length) {
                log.debug("all plugin SWFs loaded. loaded total " + loadedCount + " plugins");
                setConfigPlugins();
                dispatchEvent(new Event(Event.COMPLETE, true, false));
            }
        }

		private function initializeClassLibrary(loadable:Loadable, info:LoaderInfo):void {
            log.debug("initializing class library " + info.applicationDomain);
            _loadedPlugins[loadable] = info.applicationDomain;
			_pluginRegistry.registerGenericPlugin(loadable.createPlugin(info.applicationDomain));
		}

		private function createPluginModel(loadable:Loadable, pluginInstance:Object):PluginModel {
			log.debug("creating model for loadable " + loadable + ", instance " + pluginInstance);
				
			_loadedPlugins[loadable] = pluginInstance;
		
			log.debug("pluginInstance " + pluginInstance);
			if (pluginInstance is DisplayObject) {
				return Loadable(loadable).createDisplayPlugin(pluginInstance as DisplayObject);

			} else if (pluginInstance is StreamProvider) {
				return Loadable(loadable).createProvider(pluginInstance);
			} else {
				return Loadable(loadable).createPlugin(pluginInstance);
			}
		}

        private function initializePlugin(model:PluginModel, pluginInstance:Object):void {
            if (pluginInstance is FontProvider) {
                _pluginRegistry.registerFont(FontProvider(pluginInstance).fontFamily);

            } else if (pluginInstance is DisplayObject) {
                _pluginRegistry.registerDisplayPlugin(model as DisplayPluginModel, pluginInstance as DisplayObject);

            } else if (pluginInstance is StreamProvider) {
                _providers[model.name] = pluginInstance;
                _pluginRegistry.registerProvider(model as ProviderModel);
            } else {
                _pluginRegistry.registerGenericPlugin(model);
            }
            if (pluginInstance is Plugin) {
                if (_loadListener != null) {
                    model.onLoad(_loadListener);
                }
                model.onError(onPluginError);
            }
            if (model is Callable && _useExternalInterface) {
                ExternalInterfaceHelper.initializeInterface(model as Callable, pluginInstance);
            }
        }

        private function onPluginError(event:PluginEvent):void {
            log.debug("onPluginError() " + event.error);
            if (event.error) {
                _errorHandler.handleError(event.error, event.info + ", " + event.info2, true);
            }
        }

		private function createPluginInstance(instanceUsed:Boolean, instance:DisplayObject):Object {
			if (instance.hasOwnProperty("newPlugin")) return instance["newPlugin"](); 
			
			if (! instanceUsed) {
				log.debug("using existing instance " + instance);
				return instance; 
			}
			var className:String = getQualifiedClassName(instance);
			log.info("creating new " + className);
			var PluginClass:Class = Class(getDefinitionByName(className));
			return new PluginClass() as DisplayObject;
		}
		
		public function setConfigPlugins():void {
			_allPlugins.forEach(function(loadable:Loadable, index:int, array:Array):void {
                if (! loadable.loadFailed) {
                    var pluginInstance:Object = plugins[loadable];
                    // we don't have a plugin instance for all of these (dock for example)
                    if (pluginInstance) {
                        log.info(index + ": setting config to " + pluginInstance + ", " + loadable);
                        if (pluginInstance is NetStreamControllingStreamProvider) {
                            log.debug("NetStreamControllingStreamProvider(pluginInstance).config = " +loadable.plugin);
                            NetStreamControllingStreamProvider(pluginInstance).model = ProviderModel(loadable.plugin);
                        } else {
                            if (pluginInstance.hasOwnProperty("onConfig")) {
                                pluginInstance.onConfig(loadable.plugin);
                            }
                        }
                    }
                }
			});
		}

		private function hasSwiff(infoUrl:String, modelUrl:String):Boolean {
            if (! modelUrl) return false;
			var slashPos:int = modelUrl.lastIndexOf("/");
			var swiffUrl:String = slashPos >= 0 ? modelUrl.substr(slashPos) : modelUrl;
			return StringUtil.endsWith(infoUrl, swiffUrl);
		}
		
		public function get providers():Dictionary {
			return _providers;
		}
		
		public function get loadedCount():int {
			return _loadedCount;
		}
        
        public function get loadComplete():Boolean {
            return _loadComplete;
        }
    }
}
