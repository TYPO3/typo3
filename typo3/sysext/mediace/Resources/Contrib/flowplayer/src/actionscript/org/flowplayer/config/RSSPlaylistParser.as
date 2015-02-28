package org.flowplayer.config {

	import com.adobe.utils.XMLUtil;

    import org.flowplayer.flow_internal;
    import org.flowplayer.model.Clip;
    import org.flowplayer.model.ClipType;
    import org.flowplayer.model.Playlist;
    import org.flowplayer.util.Log;
    import org.flowplayer.util.PropertyBinder;


    use namespace flow_internal;



    internal class RSSPlaylistParser {
        private static const UNSUPPORTED_TYPE:int = 10;
        private var log:Log = new Log(this);
        private var ns:Namespace = new Namespace("");
        private var ym:Namespace = new Namespace("http://search.yahoo.com/mrss/");
        private var fp:Namespace = new Namespace("http://flowplayer.org/fprss/");


        public function createClips(rawRSS:String, playlist:Playlist, commonClipObject:Object):Array {
            return parse(rawRSS, playlist, commonClipObject);
        }

        public function parse(rawRSS:String, playlist:Playlist, commonClipObject:Object):Array {
            var result:Array = [];
            if(! XMLUtil.isValidXML(rawRSS)) {
                throw new Error("Feed does not contain valid XML.");
            }

            default xml namespace = ns;

			var rss:XML = new XML(rawRSS);

            if (rss.name() == "rss" && Number(rss.@version) <= 2)
			{

			for each (var item:XML in rss.channel.item) {


			try {
						if(item.ym::content.length() > 0 && item.ym::thumbnail.length() > 0) {
                            var thumbnail:Clip = parseThumbnail(item, commonClipObject);
                        } else {
                            thumbnail = null;
                        }
                        var clip:Clip = parseClip(item, commonClipObject, thumbnail != null);
                    } catch (e:Error) {
	                        if (e.errorID == UNSUPPORTED_TYPE) {
					log.info("unsupported media type, ignoring this item");
	                        } else {
					throw e;
	                        }
	                }

                     if (clip) {
                         log.info("created clip " + clip);
                         result.push(clip);
                         if (playlist) {
                             playlist.addClip(clip, -1 , true);
                         }
                     }
                     if (thumbnail) {
                         log.info("created thumbnail clip " + thumbnail);
                         log.info("clip.index == " + playlist.indexOf(clip));
                         result.push(thumbnail);
                         if (playlist) {
                             playlist.addClip(thumbnail, playlist.indexOf(clip), true);
                         }
                     }

			}
            }

            //#470 check for a playlist when replacing the playlist with an rss feed.
            if (playlist) playlist.toIndex(0);
            return result;
        }

        private function parseClip(item:XML, commonClipObject:Object, hasThumbnail:Boolean = false):Clip {
            var clip:Clip =  new Clip();
            if (hasThumbnail) {
                clip.autoPlay = false;
            }
            new PropertyBinder(clip, "customProperties").copyProperties(commonClipObject) as Clip;

            if (!clip.getCustomProperty("bitrates")) clip.setCustomProperty("bitrates", []);
            if (item.link) clip.linkUrl = item.link;

		//parse a group media:content items inside a media:group tag
            if (item.ym::group.ym::content.length() > 0) {
                parseMediaGroup(item.ym::group, clip);
            }

            //parse a single media:content item
            if (item.ym::content.length() > 0) {
                parseMediaItem(XML(item.ym::content), clip);
                addBitrateItems(XML(item.ym::content), clip);
            }

			//add flowplayer clip properties
            if (item.fp::clip.attributes().length() > 0) {
		parseClipProperties(item.fp::clip, clip);
            }

            //add custom clip properties from rss elements
            for each (var childItem:XML in item.children()) {
                addClipCustomProperty(clip, childItem, parseCustomProperty(childItem));
            }

            log.debug("created clip " + clip);
            return clip;
        }

		private function parseThumbnail(item:XML, commonClipObject:Object):Clip {
			var clip:Clip =  new Clip();

			if (item.ym::thumbnail.length() > 0) {
                parseMediaThumbnail(XML(item.ym::thumbnail), clip);
            }

			//add flowplayer clip properties
            if (item.fp::thumbnail.attributes().length() > 0) {
		parseClipProperties(item.fp::thumbnail, clip);
            }

            //add custom clip properties from rss elements
            for each (var childItem:XML in item.children()) {
                addClipCustomProperty(clip, childItem, parseCustomProperty(childItem));
            }

			return clip;
		}

        private function setClipType(clip:Clip, typeVal:String):void {
            var type:ClipType = ClipType.fromMimeType(typeVal);
            if (! type) {
                throw new Error("unsupported media type '" + typeVal + "'", UNSUPPORTED_TYPE);
            }
            clip.type = type;
        }

        private function parseClipProperties(elem:XMLList, clip:Clip):void {
            var binder:PropertyBinder = new PropertyBinder(clip, "customProperties");
            for each (var attr:XML in elem.attributes()) {
                log.debug("parseClipProperties(), initializing clip property '" + attr.name() + "' to value " + attr.toString());
                binder.copyProperty(attr.name().toString(), attr.toString(), true);
            }
        }

        private function addClipCustomProperty(clip:Clip, elem:XML, value:Object):void {
            log.debug("getting property name for " + elem.localName() + " value is ", value);
            var name:String = getCustomPropName(elem);
            var existing:Object = clip.getCustomProperty(name);
            if (existing) {
                log.debug("found existing " + existing);
                var values:Array = existing is Array ? existing as Array : [existing];
                values.push(value);
                clip.customProperties[name] = values;
            } else {
                clip.setCustomProperty(name, value);
            }
            log.debug("clip custom property " + name + " now has value ", clip.customProperties[name]);

        }

        private function getCustomPropName(elem:XML):String {
            if (! elem.namespace()) return elem.localName().toString();
            if (! elem.namespace().prefix) return elem.localName().toString();
            return "'" + elem.namespace().prefix + ":" + elem.localName().toString() + "'";
//            return elem.namespace().prefix + elem.localName().charAt(0).toUpperCase() + elem.localName().substring(1);;
        }

        private function parseCustomProperty(elem:XML):Object {
            if (elem.children().length() == 0 && elem.attributes().length() == 0) {
                return elem.toString();
            }
            if (elem.children().length() == 1 && XML(elem.children()[0]).nodeKind() == "text" && elem.attributes().length() == 0) {
                log.debug("has one text child only, retrieving it's contents");
                return elem.text().toString();
            }
            var result:Object = new Object();
            for each (var attr:XML in elem.attributes()) {
                result[attr.localName().toString()] = attr.toString();
            }

            for each (var child:XML in elem.children()) {
                result[child.localName() ? child.localName().toString() : "text"] = parseCustomProperty(child);
            }
            return result;
        }

        private function parseMediaGroup(group:XMLList, clip:Clip):Boolean {

             var clipAdded:Boolean = false;

             //obtain the first default item
             var defaultItem:XMLList = group.ym::content.(hasOwnProperty('@isDefault') && @isDefault == 'true');

             if (defaultItem[0]) {
		log.debug("parseMedia(): found default media item");
		        if (parseMediaItem(defaultItem[0], clip)) {
				log.debug("parseMedia(): using the default media item");
				clipAdded = true;
		        }
             } else {
		//there are no default items obtain the first content that is streamable
                for each (var itm:XML in group.ym::content) {
                  if (parseMediaItem(itm, clip)) {
                    trace("adding item");
                    clipAdded = true;
                    break;
                  }
                }
             }

             //add bitrate items
             for each (var item:XML in group.ym::content) {
		        addBitrateItems(item, clip);
		     }

		    if (clipAdded) return true;

            log.info("could not find valid media type");
            throw new Error("Could not find a supported media type", UNSUPPORTED_TYPE);
            return false;
        }

		private function parseMediaItem(elem:XML, clip:Clip):Boolean {

            clip.url = elem.@url.toString();
            if(int(elem.@duration.toString()) > 0) {
                clip.duration = int(elem.@duration.toString());
            }

            if(elem.@type) {
                try {
                    setClipType(clip, elem.@type.toString());
                    log.info("found valid type " + elem.@type.toString());
                    return true;
                } catch (e:Error) {
                    if (e.errorID == UNSUPPORTED_TYPE) {
                        log.info("skipping unsupported media type " + elem.@type.toString());
                    } else {
                        throw e;
                    }
                }
            }
            return false;
        }

		private function parseMediaThumbnail(elem:XML, clip:Clip):Boolean {

            clip.url = elem.@url.toString();

            if(elem.@type) {
                try {
                    setClipType(clip, elem.@type.toString());
                    log.info("found valid type " + elem.@type.toString());
                    return true;
                } catch (e:Error) {
                    if (e.errorID == UNSUPPORTED_TYPE) {
                        log.info("skipping unsupported media type " + elem.@type.toString());
                    } else {
                        throw e;
                    }
                }
            }

            return false;
        }

        private function addBitrateItems(elem:XML, clip:Clip):void {
			if (elem.@bitrate && elem.@width)
			{
                var bitrateItem:Object = {url: new String(elem.@url), bitrate: new Number(elem.@bitrate), width: new Number(elem.@width), height: new Number(elem.@height)};
                //#586 add a bitrate label with a new namespace attribute fp:bitratelabel
                 if (elem.@fp::["bitratelabel"] !=="") bitrateItem.label = new String(elem.@fp::["bitratelabel"]);
				// need to explicitely cast attributes for external events, #47
				clip.customProperties["bitrates"].push(bitrateItem);
			}
        }
    }
}