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
    import org.flowplayer.model.Clip;
    import org.flowplayer.model.ClipError;

    import org.flowplayer.util.Log;
    import org.flowplayer.view.Flowplayer;

    /**
     * An helper class that does the whole resolving job
     */
    public class ClipURLResolverHelper {

        private var _defaultClipUrlResolver:ClipURLResolver;
        private var _clipUrlResolver:ClipURLResolver;
		private var _streamProvider:StreamProvider;
        private var _player:Flowplayer;

		protected var log:Log = new Log(this);

        public function ClipURLResolverHelper(player:Flowplayer, streamProvider:StreamProvider, defaultURLResolver:ClipURLResolver = null) {
			_player = player;
			_streamProvider = streamProvider;
			_defaultClipUrlResolver = _defaultClipUrlResolver ? _defaultClipUrlResolver : getDefaultClipURLResolver();
        }

        /**
         * Resolves the url for the specified clip.
         */
        public function resolveClipUrl(clip:Clip, successListener:Function):void {
            getClipURLResolver(clip).resolve(_streamProvider, clip, successListener);
        }

        /**
         * Gets the default clip url resolver to be used if the ProviderModel
         * supplied to this provider does not specify a connection provider.
         */
        protected function getDefaultClipURLResolver():ClipURLResolver {
            return new DefaultClipURLResolver();
        }

        public function getClipURLResolver(clip:Clip):ClipURLResolver {
            log.debug("get clipURLResolver,  clip.urlResolver = " + clip.urlResolvers + ", _clipUrlResolver = " + _defaultClipUrlResolver);
            if (! clip || (clip.urlResolvers && clip.urlResolvers[0] == null)) {
                clip.urlResolverObjects = [_defaultClipUrlResolver];
                return _defaultClipUrlResolver;
            }

            // defined in clip?
            if (clip.urlResolvers) {
                _clipUrlResolver = CompositeClipUrlResolver.createResolver(clip.urlResolvers, _player.pluginRegistry);
            } else {
                // get all resolvers from repository
                var configured:Array = _player.pluginRegistry.getUrlResolvers();
                if (configured && configured.length > 0) {
                    log.debug("using configured URL resolvers", configured);
                    _clipUrlResolver = CompositeClipUrlResolver.createResolver(configured, _player.pluginRegistry);
                }
            }

            if (! _clipUrlResolver) {
                _clipUrlResolver = _defaultClipUrlResolver;
            }

            _clipUrlResolver.onFailure = function(message:String = null):void {
                log.error("clip URL resolving failed: " + message);
                clip.dispatchError(ClipError.STREAM_LOAD_FAILED, "failed to resolve clip url" + (message ? ": " + message : ""));
            };

            clip.urlResolverObjects = _clipUrlResolver is CompositeClipUrlResolver ? CompositeClipUrlResolver(_clipUrlResolver).resolvers : [_clipUrlResolver];
            return _clipUrlResolver;
        }


    }
}
