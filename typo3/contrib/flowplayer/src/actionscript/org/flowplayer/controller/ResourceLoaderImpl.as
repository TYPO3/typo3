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

package org.flowplayer.controller {
    import flash.system.LoaderContext;

    import org.flowplayer.model.PlayerError;
	import org.flowplayer.util.URLUtil;	
	
	import flash.display.Loader;
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import flash.events.IOErrorEvent;
	import flash.events.SecurityErrorEvent;
	import flash.net.URLLoader;
	import flash.net.URLRequest;
	
	import org.flowplayer.util.Log;
	import org.flowplayer.view.ErrorHandler;		

	/**
	 * @author api
	 */
	public class ResourceLoaderImpl implements ResourceLoader {

		private var log:Log = new Log(this);
		private var _loaders:Object = new Object();
		private var _errorHandler:ErrorHandler;
		private var _urls:Array = new Array();
		private var _loadedCount:Number;
		private var _completeListener:Function;
		private var _baseUrl:String;
        private var _loadCompete:Boolean;

		public function ResourceLoaderImpl(baseURL:String, errorHandler:ErrorHandler = null) {
			_baseUrl = baseURL;
			_errorHandler = errorHandler;
		}

		public function addTextResourceUrl(url:String):void {
			_urls.push(url);
			_loaders[url] = createURLLoader();
		}

		public function addBinaryResourceUrl(url:String):void {
			_urls.push(url);
			_loaders[url] = createLoader();
		}

		public function set completeListener(listener:Function):void {
			_completeListener = listener;		
		}

		/**
		 * Starts loading.
		 * @param url the resource to be loaded, alternatively add the URLS using addUrl() before calling this
		 * @see #addTextResourceUrl()
		 * @see #addBinaryResourceUrl()
		 */
		public function load(url:String = null, completeListener:Function = null, isTextResource:Boolean = false):void {
			if (completeListener != null) {
				_completeListener = completeListener;
			}
			if (url) {
				clear();
                if (isTextResource) {
                    log.debug("loading text resource from " + url);
                    addTextResourceUrl(url);
                } else {
                    log.debug("loading binary resource from " + url);
				    addBinaryResourceUrl(url);
                }
			}
			if (! _urls || _urls.length == 0) {
				log.debug("nothing to load");
				return;
			}
			startLoading();
		}
		
		public function getContent(url:String = null):Object {
			try {
				var loader:Object = _loaders[url ? url : _urls[0]];
				return loader is URLLoader ? URLLoader(loader).data : loader;
			} catch (e:SecurityError) {
				handleError("cannot access file (try loosening Flash security settings): " + e.message);
			}
			return null;
		}

		private function startLoading():void {
			_loadedCount = 0;
            _loadCompete = false;
			for (var url:String in _loaders) {
				log.debug("startLoading() " + URLUtil.addBaseURL(_baseUrl, url));
                if (_loaders[url] is URLLoader) {
                    _loaders[url].load(new URLRequest(URLUtil.addBaseURL(_baseUrl, url)));
                } else {
                    var context:LoaderContext = new LoaderContext();
                    // set the check policy flag in the loader context
                    context.checkPolicyFile=true;
                    Loader(_loaders[url]).load(new URLRequest(URLUtil.addBaseURL(_baseUrl, url)), context);
                }
			}
		}

		private function createURLLoader():URLLoader {
			var loader:URLLoader = new URLLoader();
			loader.addEventListener(Event.COMPLETE, onLoadComplete);
			loader.addEventListener(IOErrorEvent.IO_ERROR, onIOError);
			loader.addEventListener(SecurityErrorEvent.SECURITY_ERROR, onSecurityError);
            return loader;
		}

		private function createLoader():Loader {
			log.debug("creating new loader");
			var loader:Loader = new Loader();
			loader.contentLoaderInfo.addEventListener(Event.COMPLETE, onLoadComplete);
			loader.contentLoaderInfo.addEventListener(IOErrorEvent.IO_ERROR, onIOError);
			loader.contentLoaderInfo.addEventListener(SecurityErrorEvent.SECURITY_ERROR, onSecurityError);
			return loader;
		}

		private function onLoadComplete(event:Event):void {
			log.debug("onLoadComplete, loaded " + (_loadedCount + 1) + " resources out of " + _urls.length);
			if (++_loadedCount == _urls.length) {
                _loadCompete = true;
				log.debug("onLoadComplete, all resources were loaded");
				if (_completeListener != null) {
					log.debug("calling complete listener function");
					_completeListener(this);
				}
			}
		}
		
		private function onIOError(event:IOErrorEvent):void {
			log.error("IOError: " + event.text);
			handleError("Unable to load resources: " + event.text);
		}

		private function onSecurityError(event:SecurityErrorEvent):void {
			log.error("SecurityError: " + event.text);
			handleError("cannot access the resource file (try loosening Flash security settings): " + event.text);
		}
		
		protected function handleError(errorMessage:String, e:Error = null):void {
			if (_errorHandler) {
				_errorHandler.handleError(PlayerError.RESOURCE_LOAD_FAILED, errorMessage + (e ? ": " + e.message : ""));
			}
		}
		
		/**
		 * Sets the error handler. All load errors will be handled with the specified
		 * handler.
		 */		
		public function set errorHandler(errorHandler:ErrorHandler):void {
			_errorHandler = errorHandler;
		}
		
		public function clear():void {
			_urls = new Array();
			_loaders = new Array();
		}

        public function get loadComplete():Boolean {
            return _loadCompete;
        }

        public function get baseUrl():String {
            return _baseUrl;
        }
    }
}
