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
        ,renderTo: function(elementId, options){

            var el = Ext.get(elementId);
            var width = el.getWidth();
            var height = el.getHeight();

            var styles = el.getStyles('padding-top', 'padding-bottom', 'padding-left', 'padding-right', 'line-height', 'font-size', 'font-family', 'font-weight');

            if(! this.div){
                var options = processOptions(options);

                this.div = Ext.core.DomHelper.append(Ext.getBody() || document.body, {
                    'id':elementId + '-preview-div'
                    ,'tag' : 'div'
                    ,'background': 'red'
                    ,'style' : 'position: absolute; top: -100000px; left: -100000px;'
                }, true)
                Ext.core.DomHelper.applyStyles(this.div, styles);

                el.on('keyup', function() {
                        this.renderTo(elementId, options);
                }, this);
            }
            this.div.setWidth(parseInt(el.getStyle('width')));
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
