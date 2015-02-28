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
    import flash.events.NetStatusEvent;
import org.flowplayer.model.Clip;
    import org.flowplayer.model.PluginModel;
    import org.flowplayer.util.Log;
import org.flowplayer.view.PluginRegistry;

    public class CompositeClipUrlResolver implements ClipURLResolver {
        private static var log:Log = new Log("org.flowplayer.controller::CompositeClipUrlResolver");
        private var _resolvers:Array;
        private var _current:int = 0;
        private var _successListener:Function;
        private var _clip:Clip;
        private var _provider:StreamProvider;

        public function CompositeClipUrlResolver(resolvers:Array) {
            _resolvers = resolvers;
        }

        public static function createResolver(names:Array, pluginRegistry:PluginRegistry):ClipURLResolver {
            if (! names || names.length == 0) {
                throw new Error("resolver name not supplied");
            }
//            if (names.length == 1) return getResolver(names[0], pluginRegistry);

            log.debug("creating composite resolver with " + names.length + " resolvers");
            var resolvers:Array = new Array();
            for (var i:int = 0; i < names.length; i++) {
                log.debug("initializing resolver " + names[i]);
                resolvers.push(getResolver(names[i], pluginRegistry));
            }
            return new CompositeClipUrlResolver(resolvers);
        }

        private static function getResolver(name:String, pluginRegistry:PluginRegistry):ClipURLResolver {
            var resolver:ClipURLResolver = PluginModel(pluginRegistry.getPlugin(name)).pluginObject as ClipURLResolver;
            if (! resolver) {
                throw new Error("clipURLResolver '" + name + "' not loaded");
            }
            return resolver;
        }

        public function resolve(provider:StreamProvider, clip:Clip, successListener:Function):void {
            if (clip.getResolvedUrl()) {
                log.debug("clip URL has been already resolved to '" + clip.url + "', calling successListener");
                successListener(clip)
                return;
            }
            log.debug("resolve(): resolving with " + _resolvers.length + " resolvers");
            _provider = provider;
            _clip = clip;
            _successListener = successListener;
            _current = 0;
            resolveNext();
        }

        private function resolveNext():void {
            if (_current == _resolvers.length) {
                log.debug("all resolvers done, calling the successListener");
                _successListener(_clip);
                return;
            }
            var resolver:ClipURLResolver = _resolvers[_current++];
            log.debug("resolving with " + resolver);
            resolver.resolve(_provider, _clip, function(clip:Clip):void {
                log.debug("resolver "+ resolver +" done, url is now " + clip.url);
                resolveNext();
            });
        }

        public function set onFailure(listener:Function):void {
            for (var i:int = 0; i < _resolvers.length; i++) {
                ClipURLResolver(_resolvers[i]).onFailure = listener;
            }
        }

        public function handeNetStatusEvent(event:NetStatusEvent):Boolean {
            return true;
        }

        public function get resolvers():Array {
            return _resolvers;
        }
    }
}