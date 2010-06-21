/**
 *
 * Ext.ux.EventZone Extension Class for Ext 3.x Library
 *
 * @author  Nigel White
 *
 * @license Ext.ux.EventZone is licensed under the terms of
 * the Open Source LGPL 3.0 license.  Commercial use is permitted to the extent
 * that the code/component(s) do NOT become part of another Open Source or Commercially
 * licensed development library or toolkit without explicit permission.
 * 
 * License details: http://www.gnu.org/licenses/lgpl.html
 *
 * @class Ext.ux.EventZone
 * <p>This class implements a "virtual element" at a relative size and position
 * <i>within</i> an existing element. It provides mouse events from a zone of an element of
 * defined dimensions.</p>
 * <p>The zone is defined using <code>top</code>, <code>right</code>, <code>bottom</code>,
 * <code>left</code>, <code>width</code> and <code>height</code> options which specify
 * the bounds of the zone in a similar manner to the CSS style properties of those names.</p>
 * @cfg {String|HtmlElement} el The element in which to create the zone.
 * @cfg {Array} points An Array of points within the element defining the event zone.
 * @cfg {Number} top The top of the zone. If negative means an offset from the bottom.
 * @cfg {Number} right The right of the zone. If negative means an offset from the right.
 * @cfg {Number} left The left of the zone. If negative means an offset from the right.
 * @cfg {Number} bottom The bottom of the zone. If negative means an offset from the bottom.
 * @cfg {Number} width The width of the zone.
 * @cfg {Number} height The height of the zone.
 * @constructor
 * Create a new EventZone
 * @param {Object} config The config object.
 */
Ext.ux.EventZone = Ext.extend(Ext.util.Observable, {

    constructor: function(config) {
        this.initialConfig = config;
        this.addEvents(
            /**
             * @event mouseenter
             * This event fires when the mouse enters the zone.
             * @param {EventObject} e the underlying mouse event.
             * @param {EventZone} this
             */
            'mouseenter',
            /**
             * @event mousedown
             * This event fires when the mouse button is depressed within the zone.
             * @param {EventObject} e the underlying mouse event.
             * @param {EventZone} this
             */
            'mousedown',
            /**
             * @event mousemove
             * This event fires when the mouse moves within the zone.
             * @param {EventObject} e the underlying mouse event.
             * @param {EventZone} this
             */
            'mousemove',
            /**
             * @event mouseup
             * This event fires when the mouse button is released within the zone.
             * @param {EventObject} e the underlying mouse event.
             * @param {EventZone} this
             */
            'mouseup',
            /**
             * @event mouseenter
             * This event fires when the mouse is clicked within the zone.
             * @param {EventObject} e the underlying mouse event.
             * @param {EventZone} this
             */
            'click',
            /**
             * @event mouseleave
             * This event fires when the mouse leaves the zone.
             * @param {EventObject} e the underlying mouse event.
             * @param {EventZone} this
             */
            'mouseleave'
        );
        Ext.apply(this, config);
        this.el = Ext.get(this.el);

//      If a polygon within the element is specified...
        if (this.points) {
            this.polygon = new Ext.lib.Polygon(this.points);
            this.points = this.polygon.points;
        }

        Ext.ux.EventZone.superclass.constructor.call(this);
        this.el.on({
            mouseenter: this.handleMouseEvent,
            mousedown: this.handleMouseEvent,
            mousemove: this.handleMouseEvent,
            mouseup: this.handleMouseEvent,
            click: this.handleMouseEvent,
            mouseleave: this.handleMouseEvent,
            scope: this
        });
    },

    handleMouseEvent: function(e) {
        var r = this.polygon ? this.getPolygon() : this.getRegion();
        var inBounds = r.contains(e.getPoint());

        switch (e.type) {
            // mouseenter fires this
            case 'mouseover':
               if (inBounds) {
                   this.mouseIn = true;
                   this.fireEvent('mouseenter', e, this);
               }
               break;
            // mouseleave fires this
            case 'mouseout':
               this.mouseIn = false;
               this.fireEvent('mouseleave', e, this);
               break;
           case 'mousemove':
               if (inBounds) {
                   if (this.mouseIn) {
                       this.fireEvent('mousemove', e, this);
                   } else {
                       this.mouseIn = true;
                       this.fireEvent('mouseenter', e, this);
                   }
               } else {
                   if (this.mouseIn) {
                       this.mouseIn = false;
                       this.fireEvent('mouseleave', e, this);
                   }
               }
               break;
           default:
               if (inBounds) {
                   this.fireEvent(e.type, e, this);
               }
        }
    },

    getPolygon: function() {
        var xy = this.el.getXY();
        return this.polygon.translate(xy[0], xy[1]);
    },

    getRegion: function() {
        var r = this.el.getRegion();

//      Adjust left boundary of region
        if (Ext.isNumber(this.left)) {
            if (this.left < 0) {
                r.left = r.right + this.left;
            } else {
                r.left += this.left;
            }
        }

//      Adjust right boundary of region
        if (Ext.isNumber(this.width)) {
            r.right = r.left + this.width;
        } else if (Ext.isNumber(this.right)) {
            r.right = (this.right < 0) ? r.right + this.right : r.left + this.right;
        }

//      Adjust top boundary of region
        if (Ext.isNumber(this.top)) {
            if (this.top < 0) {
                r.top = r.bottom + this.top;
            } else {
                r.top += this.top;
            }
        }

//      Adjust bottom boundary of region
        if (Ext.isNumber(this.height)) {
            r.bottom = r.top + this.height;
        } else if (Ext.isNumber(this.bottom)) {
            r.bottom = (this.bottom < 0) ? r.bottom + this.bottom : r.top + this.bottom;
        }

        return r;
    }
});

/**
 * @class Ext.lib.Polygon
 * <p>This class encapsulates an absolute area of the document bounded by a list of points.</p>
 * @constructor
 * Create a new Polygon
 * @param {Object} points An Array of <code>[n,n]</code> point specification Arrays, or
 * an Array of Ext.lib.Points, or an HtmlElement, or an Ext.lib.Region.
 */
Ext.lib.Polygon = Ext.extend(Ext.lib.Region, {
    constructor: function(points) {
        var i, l, el;
        if (l = points.length) {
            if (points[0].x) {
                for (i = 0; i < l; i++) {
                    points[i] = [ points[i].x, points[i].y ];
                }
            }
            this.points = points;
        } else {
            if (el = Ext.get(points)) {
                points = Ext.lib.Region.getRegion(el.dom);
            }
            if (points instanceof Ext.lib.Region) {
                this.points = [
                    [points.left, points.top],
                    [points.right, points.top],
                    [points.right, points.bottom],
                    [points.left, points.bottom]
                ];
            }
        }
    },

    /**
     * Returns a new Polygon translated by the specified <code>X</code> and <code>Y</code> increments.
     * @param xDelta {Number} The <code>X</code> translation increment.
     * @param xDelta {Number} The <code>Y</code> translation increment.
     * @return {Polygon} The resulting Polygon.
     */
    translate: function(xDelta, yDelta) {
        var r = [], p = this.points, l = p.length, i;
        for (i = 0; i < l; i++) {
            r[i] = [ p[i][0] + xDelta, p[i][1] + yDelta ];
        }
        return new Ext.lib.Polygon(r);
    },

    /**
     * Returns the area of this Polygon.
     */
    getArea: function() {
        var p = this.points, l = p.length, area = 0, i, j = 0;
        for (i = 0; i < l; i++) {
            j++;
            if (j == l) {
                j = 0;
            }
            area += (p[i][0] + p[j][0]) * (p[i][1] - p[j][1]);
        }
        return area * 0.5;
    },

    /**
     * Returns <code>true</code> if this Polygon contains the specified point. Thanks
     * to http://www.ecse.rpi.edu/Homepages/wrf/Research/Short_Notes/pnpoly.html for the algorithm.
     * @param pt {Point|Number} Either an Ext.lib.Point object, or the <code>X</code> coordinate to test.
     * @param py {Number} <b>Optional.</b> If the first parameter was an <code>X</code> coordinate, this is the <code>Y</code> coordinate.
     */
    contains: function(pt, py) {
        var f = (arguments.length == 1),
            testX = f ? pt.x : pt,
            testY = f ? pt.y : py,
            p = this.points,
            nvert = p.length,
            j = nvert - 1,
            i, j, c = false;
        for (i = 0; i < nvert; j = i++) {
            if ( ((p[i][1] > testY) != (p[j][1] > testY)) &&
             (testX < (p[j][0]-p[i][0]) * (testY-p[i][1]) / (p[j][1]-p[i][1]) + p[i][0])) {
                c = !c;
            }
        }
        return c;
    }
});

/**
 * @class Ext.Resizable
 * @extends Ext.util.Observable
 * This is an override of Ext.Resizable to make usage of the Ex.ux.EventZone
 * <p>Applies virtual drag handles to an element to make it resizable.</p>
 * <p>Here is the list of valid resize handles:</p>
 * <pre>
Value   Description
------  -------------------
 'n'     north
 's'     south
 'e'     east
 'w'     west
 'nw'    northwest
 'sw'    southwest
 'se'    southeast
 'ne'    northeast
 'all'   all
</pre>
 * <p>Here's an example showing the creation of a typical Resizable:</p>
 * <pre><code>
var resizer = new Ext.Resizable('element-id', {
    handles: 'all',
    minWidth: 200,
    minHeight: 100,
    maxWidth: 500,
    maxHeight: 400,
    pinned: true
});
resizer.on('resize', myHandler);
</code></pre>
 * <p>To hide a particular handle, set its display to none in CSS, or through script:<br>
 * resizer.east.setDisplayed(false);</p>
 * @constructor
 * Create a new resizable component
 * @param {Mixed} el The id or element to resize
 * @param {Object} config configuration options
  */
Ext.Resizable = function(el, config){
    this.el = Ext.get(el);

    /**
     * The proxy Element that is resized in place of the real Element during the resize operation.
     * This may be queried using {@link Ext.Element#getBox} to provide the new area to resize to.
     * Read only.
     * @type Ext.Element.
     * @property proxy
     */
    this.proxy = this.el.createProxy({tag: 'div', cls: 'x-resizable-proxy', id: this.el.id + '-rzproxy'}, Ext.getBody());
    this.proxy.unselectable();
    this.proxy.enableDisplayMode('block');

    Ext.apply(this, config);
    
    if(this.pinned){
        this.disableTrackOver = true;
        this.el.addClass('x-resizable-pinned');
    }
    // if the element isn't positioned, make it relative
    var position = this.el.getStyle('position');
    if(position != 'absolute' && position != 'fixed'){
        this.el.setStyle('position', 'relative');
    }
    if(!this.handles){ // no handles passed, must be legacy style
        this.handles = 's,e,se';
        if(this.multiDirectional){
            this.handles += ',n,w';
        }
    }
    if(this.handles == 'all'){
        this.handles = 'n s e w ne nw se sw';
    }
    var hs = this.handles.split(/\s*?[,;]\s*?| /);
    var ps = Ext.Resizable.positions;
    for(var i = 0, len = hs.length; i < len; i++){
        if(hs[i] && ps[hs[i]]){
            var pos = ps[hs[i]];
            this[pos] = new Ext.Resizable.Handle(this, pos);
        }
    }
    // legacy
    this.corner = this.southeast;
    
    if(this.handles.indexOf('n') != -1 || this.handles.indexOf('w') != -1){
        this.updateBox = true;
    }   
    this.activeHandle = null;

    if(this.adjustments == 'auto'){
        var hw = this.west, he = this.east, hn = this.north, hs = this.south;
        this.adjustments = [
            (he.el ? -he.el.getWidth() : 0) + (hw.el ? -hw.el.getWidth() : 0),
            (hn.el ? -hn.el.getHeight() : 0) + (hs.el ? -hs.el.getHeight() : 0) -1 
        ];
    }

    if(this.draggable){
        this.dd = this.dynamic ? 
            this.el.initDD(null) : this.el.initDDProxy(null, {dragElId: this.proxy.id});
        this.dd.setHandleElId(this.el.id);
    }

    this.addEvents(
        /**
         * @event beforeresize
         * Fired before resize is allowed. Set {@link #enabled} to false to cancel resize.
         * @param {Ext.Resizable} this
         * @param {Ext.EventObject} e The mousedown event
         */
        'beforeresize',
        /**
         * @event resize
         * Fired after a resize.
         * @param {Ext.Resizable} this
         * @param {Number} width The new width
         * @param {Number} height The new height
         * @param {Ext.EventObject} e The mouseup event
         */
        'resize'
    );
    
    if(this.width !== null && this.height !== null){
        this.resizeTo(this.width, this.height);
    }
    if(Ext.isIE){
        this.el.dom.style.zoom = 1;
    }
    Ext.Resizable.superclass.constructor.call(this);
};

Ext.extend(Ext.Resizable, Ext.util.Observable, {

    /**
     * @cfg {Array/String} adjustments String 'auto' or an array [width, height] with values to be <b>added</b> to the
     * resize operation's new size (defaults to <tt>[0, 0]</tt>)
     */
    adjustments : [0, 0],
    /**
     * @cfg {Boolean} animate True to animate the resize (not compatible with dynamic sizing, defaults to false)
     */
    animate : false,
    /**
     * @cfg {Mixed} constrainTo Constrain the resize to a particular element
     */
    /**
     * @cfg {Boolean} draggable Convenience to initialize drag drop (defaults to false)
     */
    draggable: false,
    /**
     * @cfg {Number} duration Animation duration if animate = true (defaults to 0.35)
     */
    duration : 0.35,
    /**
     * @cfg {Boolean} dynamic True to resize the element while dragging instead of using a proxy (defaults to false)
     */
    dynamic : false,
    /**
     * @cfg {String} easing Animation easing if animate = true (defaults to <tt>'easingOutStrong'</tt>)
     */
    easing : 'easeOutStrong',
    /**
     * @cfg {Boolean} enabled False to disable resizing (defaults to true)
     */
    enabled : true,
    /**
     * @property enabled Writable. False if resizing is disabled.
     * @type Boolean 
     */
    /**
     * @cfg {String} handles String consisting of the resize handles to display (defaults to undefined).
     * Specify either <tt>'all'</tt> or any of <tt>'n s e w ne nw se sw'</tt>.
     */
    handles : false,
    /**
     * @cfg {Boolean} multiDirectional <b>Deprecated</b>.  Deprecated style of adding multi-direction resize handles.
     */
    multiDirectional : false,
    /**
     * @cfg {Number} height The height of the element in pixels (defaults to null)
     */
    height : null,
    /**
     * @cfg {Number} width The width of the element in pixels (defaults to null)
     */
    width : null,
    /**
     * @cfg {Number} heightIncrement The increment to snap the height resize in pixels
     * (only applies if <code>{@link #dynamic}==true</code>). Defaults to <tt>0</tt>.
     */
    heightIncrement : 0,
    /**
     * @cfg {Number} widthIncrement The increment to snap the width resize in pixels
     * (only applies if <code>{@link #dynamic}==true</code>). Defaults to <tt>0</tt>.
     */
    widthIncrement : 0,
    /**
     * @cfg {Number} minHeight The minimum height for the element (defaults to 5)
     */
    minHeight : 5,
    /**
     * @cfg {Number} minWidth The minimum width for the element (defaults to 5)
     */
    minWidth : 5,
    /**
     * @cfg {Number} maxHeight The maximum height for the element (defaults to 10000)
     */
    maxHeight : 10000,
    /**
     * @cfg {Number} maxWidth The maximum width for the element (defaults to 10000)
     */
    maxWidth : 10000,
    /**
     * @cfg {Number} minX The minimum x for the element (defaults to 0)
     */
    minX: 0,
    /**
     * @cfg {Number} minY The minimum x for the element (defaults to 0)
     */
    minY: 0,
    /**
     * @cfg {Boolean} pinned True to ensure that the resize handles are always visible, false to display them only when the
     * user mouses over the resizable borders. This is only applied at config time. (defaults to false)
     */
    pinned : false,
    /**
     * @cfg {Boolean} preserveRatio True to preserve the original ratio between height
     * and width during resize (defaults to false)
     */
    preserveRatio : false,
    /**
     * @cfg {Ext.lib.Region} resizeRegion Constrain the resize to a particular region
     */

    
    /**
     * Perform a manual resize and fires the 'resize' event.
     * @param {Number} width
     * @param {Number} height
     */
    resizeTo : function(width, height){
        this.el.setSize(width, height);
        this.fireEvent('resize', this, width, height, null);
    },

    // private
    startSizing : function(e, handle){
        this.fireEvent('beforeresize', this, e);
        if(this.enabled){ // 2nd enabled check in case disabled before beforeresize handler
            e.stopEvent();

            Ext.getDoc().on({
                scope: this,
                mousemove: this.onMouseMove,
                mouseup: {
                    fn: this.onMouseUp,
                    single: true,
                    scope: this
                }
            });
            Ext.getBody().addClass('ux-resizable-handle-' + handle.position);

            this.resizing = true;
            this.startBox = this.el.getBox();
            this.startPoint = e.getXY();
            this.offsets = [(this.startBox.x + this.startBox.width) - this.startPoint[0],
                            (this.startBox.y + this.startBox.height) - this.startPoint[1]];

            if(this.constrainTo) {
                var ct = Ext.get(this.constrainTo);
                this.resizeRegion = ct.getRegion().adjust(
                    ct.getFrameWidth('t'),
                    ct.getFrameWidth('l'),
                    -ct.getFrameWidth('b'),
                    -ct.getFrameWidth('r')
                );
            }

            this.proxy.setStyle('visibility', 'hidden'); // workaround display none
            this.proxy.show();
            this.proxy.setBox(this.startBox);
            if(!this.dynamic){
                this.proxy.setStyle('visibility', 'visible');
            }
        }
    },

    // private
    onMouseDown : function(handle, e){
        if(this.enabled && !this.activeHandle){
            e.stopEvent();
            this.activeHandle = handle;
            this.startSizing(e, handle);
        }
    },

    // private
    onMouseUp : function(e){
        Ext.getBody().removeClass('ux-resizable-handle-' + this.activeHandle.position)
            .un('mousemove', this.onMouseMove, this);
        var size = this.resizeElement();
        this.resizing = false;
        this.handleOut(this.activeHandle);
        this.proxy.hide();
        this.fireEvent('resize', this, size.width, size.height, e);
        this.activeHandle = null;
    },

    // private
    snap : function(value, inc, min){
        if(!inc || !value){
            return value;
        }
        var newValue = value;
        var m = value % inc;
        if(m > 0){
            if(m > (inc/2)){
                newValue = value + (inc-m);
            }else{
                newValue = value - m;
            }
        }
        return Math.max(min, newValue);
    },

    /**
     * <p>Performs resizing of the associated Element. This method is called internally by this
     * class, and should not be called by user code.</p>
     * <p>If a Resizable is being used to resize an Element which encapsulates a more complex UI
     * component such as a Panel, this method may be overridden by specifying an implementation
     * as a config option to provide appropriate behaviour at the end of the resize operation on
     * mouseup, for example resizing the Panel, and relaying the Panel's content.</p>
     * <p>The new area to be resized to is available by examining the state of the {@link #proxy}
     * Element. Example:
<pre><code>
new Ext.Panel({
    title: 'Resize me',
    x: 100,
    y: 100,
    renderTo: Ext.getBody(),
    floating: true,
    frame: true,
    width: 400,
    height: 200,
    listeners: {
        render: function(p) {
            new Ext.Resizable(p.getEl(), {
                handles: 'all',
                pinned: true,
                transparent: true,
                resizeElement: function() {
                    var box = this.proxy.getBox();
                    p.updateBox(box);
                    if (p.layout) {
                        p.doLayout();
                    }
                    return box;
                }
           });
       }
    }
}).show();
</code></pre>
     */
    resizeElement : function(){
        var box = this.proxy.getBox();
        if(this.updateBox){
            this.el.setBox(box, false, this.animate, this.duration, null, this.easing);
        }else{
            this.el.setSize(box.width, box.height, this.animate, this.duration, null, this.easing);
        }
        if(!this.dynamic){
            this.proxy.hide();
        }
        return box;
    },

    // private
    constrain : function(v, diff, m, mx){
        if(v - diff < m){
            diff = v - m;    
        }else if(v - diff > mx){
            diff = v - mx; 
        }
        return diff;                
    },

    // private
    onMouseMove : function(e){
        if(this.enabled && this.activeHandle){
            try{// try catch so if something goes wrong the user doesn't get hung

            if(this.resizeRegion && !this.resizeRegion.contains(e.getPoint())) {
                return;
            }

            //var curXY = this.startPoint;
            var curSize = this.curSize || this.startBox,
                x = this.startBox.x, y = this.startBox.y,
                ox = x, 
                oy = y,
                w = curSize.width, 
                h = curSize.height,
                ow = w, 
                oh = h,
                mw = this.minWidth, 
                mh = this.minHeight,
                mxw = this.maxWidth, 
                mxh = this.maxHeight,
                wi = this.widthIncrement,
                hi = this.heightIncrement,
                eventXY = e.getXY(),
                diffX = -(this.startPoint[0] - Math.max(this.minX, eventXY[0])),
                diffY = -(this.startPoint[1] - Math.max(this.minY, eventXY[1])),
                pos = this.activeHandle.position,
                tw,
                th;
            
            switch(pos){
                case 'east':
                    w += diffX; 
                    w = Math.min(Math.max(mw, w), mxw);
                    break;
                case 'south':
                    h += diffY;
                    h = Math.min(Math.max(mh, h), mxh);
                    break;
                case 'southeast':
                    w += diffX; 
                    h += diffY;
                    w = Math.min(Math.max(mw, w), mxw);
                    h = Math.min(Math.max(mh, h), mxh);
                    break;
                case 'north':
                    diffY = this.constrain(h, diffY, mh, mxh);
                    y += diffY;
                    h -= diffY;
                    break;
                case 'west':
                    diffX = this.constrain(w, diffX, mw, mxw);
                    x += diffX;
                    w -= diffX;
                    break;
                case 'northeast':
                    w += diffX; 
                    w = Math.min(Math.max(mw, w), mxw);
                    diffY = this.constrain(h, diffY, mh, mxh);
                    y += diffY;
                    h -= diffY;
                    break;
                case 'northwest':
                    diffX = this.constrain(w, diffX, mw, mxw);
                    diffY = this.constrain(h, diffY, mh, mxh);
                    y += diffY;
                    h -= diffY;
                    x += diffX;
                    w -= diffX;
                    break;
               case 'southwest':
                    diffX = this.constrain(w, diffX, mw, mxw);
                    h += diffY;
                    h = Math.min(Math.max(mh, h), mxh);
                    x += diffX;
                    w -= diffX;
                    break;
            }
            
            var sw = this.snap(w, wi, mw);
            var sh = this.snap(h, hi, mh);
            if(sw != w || sh != h){
                switch(pos){
                    case 'northeast':
                        y -= sh - h;
                    break;
                    case 'north':
                        y -= sh - h;
                        break;
                    case 'southwest':
                        x -= sw - w;
                    break;
                    case 'west':
                        x -= sw - w;
                        break;
                    case 'northwest':
                        x -= sw - w;
                        y -= sh - h;
                    break;
                }
                w = sw;
                h = sh;
            }
            
            if(this.preserveRatio){
                switch(pos){
                    case 'southeast':
                    case 'east':
                        h = oh * (w/ow);
                        h = Math.min(Math.max(mh, h), mxh);
                        w = ow * (h/oh);
                       break;
                    case 'south':
                        w = ow * (h/oh);
                        w = Math.min(Math.max(mw, w), mxw);
                        h = oh * (w/ow);
                        break;
                    case 'northeast':
                        w = ow * (h/oh);
                        w = Math.min(Math.max(mw, w), mxw);
                        h = oh * (w/ow);
                    break;
                    case 'north':
                        tw = w;
                        w = ow * (h/oh);
                        w = Math.min(Math.max(mw, w), mxw);
                        h = oh * (w/ow);
                        x += (tw - w) / 2;
                        break;
                    case 'southwest':
                        h = oh * (w/ow);
                        h = Math.min(Math.max(mh, h), mxh);
                        tw = w;
                        w = ow * (h/oh);
                        x += tw - w;
                        break;
                    case 'west':
                        th = h;
                        h = oh * (w/ow);
                        h = Math.min(Math.max(mh, h), mxh);
                        y += (th - h) / 2;
                        tw = w;
                        w = ow * (h/oh);
                        x += tw - w;
                       break;
                    case 'northwest':
                        tw = w;
                        th = h;
                        h = oh * (w/ow);
                        h = Math.min(Math.max(mh, h), mxh);
                        w = ow * (h/oh);
                        y += th - h;
                        x += tw - w;
                        break;
                        
                }
            }
            this.proxy.setBounds(x, y, w, h);
            if(this.dynamic){
                this.resizeElement();
            }
            }catch(ex){}
        }
    },

    // private
    handleOver : function(handle){
        if(this.enabled){
            Ext.getBody().addClass('ux-resizable-handle-' + handle.position);
        }
    },

    // private
    handleOut : function(handle){
        if(!this.resizing){
            Ext.getBody().removeClass('ux-resizable-handle-' + handle.position);
        }
    },
    
    /**
     * Returns the element this component is bound to.
     * @return {Ext.Element}
     */
    getEl : function(){
        return this.el;
    },

    /**
     * Destroys this resizable. If the element was wrapped and 
     * removeEl is not true then the element remains.
     * @param {Boolean} removeEl (optional) true to remove the element from the DOM
     */
    destroy : function(removeEl){
        Ext.destroy(this.dd, this.proxy);
        this.proxy = null;
        
        var ps = Ext.Resizable.positions;
        for(var k in ps){
            if(typeof ps[k] != 'function' && this[ps[k]]){
                this[ps[k]].destroy();
            }
        }
        if(removeEl){
            this.el.update('');
            Ext.destroy(this.el);
            this.el = null;
        }
        this.purgeListeners();
    },

    syncHandleHeight : function(){
        var h = this.el.getHeight(true);
        if(this.west.el){
            this.west.el.setHeight(h);
        }
        if(this.east.el){
            this.east.el.setHeight(h);
        }
    }
});

// private
// hash to map config positions to true positions
Ext.Resizable.positions = {
    n: 'north', s: 'south', e: 'east', w: 'west', se: 'southeast', sw: 'southwest', nw: 'northwest', ne: 'northeast'
};
Ext.Resizable.cfg = {
    north: {left: 7, right: -7, height: 7},
    south: {left: 7, right: -7, top: -7},
    east: {top: 7, bottom: -7, left: -7},
    west: {top: 7, bottom: -7, width: 7},
    southeast: {top: -7, left: -7},
    southwest: {top: -7, width: 7},
    northwest: {height: 7, width: 7},
    northeast: {left: -7, height: 7}
};

// private
Ext.Resizable.Handle = function(rz, pos){
    this.position = pos;
    this.rz = rz;
    var cfg = Ext.Resizable.cfg[pos] || Ext.Resizable.cfg[Ext.Resizable.positions[pos]];
    this.ez = new Ext.ux.EventZone(Ext.apply({
        position: pos,
        el: rz.el
    }, cfg));
    this.ez.on({
        mousedown: this.onMouseDown,
        mouseenter: this.onMouseOver,
        mouseleave: this.onMouseOut,
        scope: this
    });
};

// private
Ext.Resizable.Handle.prototype = {
    cursor: 'move',

    // private
    afterResize : function(rz){
        // do nothing    
    },
    // private
    onMouseDown : function(e){
        this.rz.onMouseDown(this, e);
    },
    // private
    onMouseOver : function(e){
        this.rz.handleOver(this, e);
    },
    // private
    onMouseOut : function(e){
        this.rz.handleOut(this, e);
    },
    // private
    destroy : function(){
        Ext.destroy(this.el);
        this.el = null;
    }
};

/**
*
* Ext.ux.elasticTextArea Extension Class for Ext 3.x Library
*
* @author  Steffen Kamper
*
* @license Ext.ux.elasticTextArea is licensed under the terms of
* the Open Source LGPL 3.0 license.  Commercial use is permitted to the extent
* that the code/component(s) do NOT become part of another Open Source or Commercially
* licensed development library or toolkit without explicit permission.
* 
* License details: http://www.gnu.org/licenses/lgpl.html
*
*/
Ext.ux.elasticTextArea = function(){
    
    var defaultConfig = function(){
        return {
            minHeight : 0
            ,maxHeight : 0
            ,growBy: 12
        }
    }
    
    var processOptions = function(config){
        var o = defaultConfig();
        var options = {};
        Ext.apply(options, config, o);
        
        return options ;
    }
    
    return {
        div : null
        ,applyTo: function(elementId, options){
        
            var el = Ext.get(elementId);
            var width = el.getWidth();
            var height = el.getHeight();
            
            var styles = el.getStyles('padding-top', 'padding-bottom', 'padding-left', 'padding-right', 'line-height', 'font-size', 'font-family', 'font-weight');

            if(! this.div){
                var options = processOptions(options);
                
                this.div = Ext.DomHelper.append(Ext.getBody() || document.body, {
                    'id':elementId + '-preview-div'
                    ,'tag' : 'div'
                    ,'background': 'red'
                    ,'style' : 'position: absolute; top: -100000px; left: -100000px;'
                }, true)
                Ext.DomHelper.applyStyles(this.div, styles);
                
                el.on('keyup', function() {
                        this.applyTo(elementId, options);
                }, this);
            }
            
            //replace \n with <br>&nbsp; so that the enter key can trigger and height increase
            //but first remove all previous entries, so that the height mesurement can be as accurate as possible
            this.div.update( 
                    el.dom.value.replace(/<br \/>&nbsp;/, '<br />')
                                .replace(/<|>/g, ' ')
                                .replace(/&/g,"&amp;")
                                .replace(/\n/g, '<br />&nbsp;') 
                    );
            
			var growBy = parseInt(el.getStyle('line-height'));
			growBy = growBy ? growBy + 1 : 1;
			if (growBy === 1) {
				growBy = options.growBy;
			}
			var textHeight = this.div.getHeight();
			textHeight = textHeight ? textHeight + growBy : growBy;
            
            if ( (textHeight > options.maxHeight ) && (options.maxHeight > 0) ){
                textHeight = options.maxHeight ;
                el.setStyle('overflow', 'auto');
            }
            if ( (textHeight < options.minHeight ) && (options.minHeight > 0) ) {
                textHeight = options.minHeight ;
                el.setStyle('overflow', 'auto');
            }
            
            el.setHeight(textHeight , true);
        }
    }
}
