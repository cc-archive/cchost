/*
* Creative Commons has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use the ccHost software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of the ccHost software and you
* represent and warrant to Creative Commons that your use
* of the ccHost software will comply with the CC-GNU-GPL.
*
* $Id$
*
*/

function $D(msg) {
    if( $('debug') )
        $('debug').innerHTML = msg + '<br />' + $('debug').innerHTML;
}

ccDelayAndFade = Class.create();
ccDelayAndFade.prototype = {

    initialize: function ( delay, element, opacity, duration, steps, options) {
        this.e = element;
        this.o = opacity;
        this.d = duration;
        this.s = steps;
        this.opts = options;
        if( !delay )
            this.onDelay();
        else
            setTimeout( this.onDelay.bind(this), delay );
    },

    onDelay: function() {
        new Rico_Effect_FadeTo( this.e, this.o, this.d, this.s, this.opts );
    }
}

/* Ripped from rico.js */

Rico_Effect_FadeTo = Class.create();
Rico_Effect_FadeTo.prototype = {

   initialize: function( element, opacity, duration, steps, options) {
      this.element  = $(element);
      this.opacity  = opacity;
      this.duration = duration;
      this.steps    = steps;
      this.options  = arguments[4] || {};
      this.fadeTo();
   },

   fadeTo: function() {
      if (this.isFinished()) {
         if(this.options.complete) this.options.complete(this);
         return;
      }

      if (this.timer)
         clearTimeout(this.timer);

      var stepDuration = Math.round(this.duration/this.steps) ;
      var currentOpacity = this.getElementOpacity();
      var delta = this.steps > 0 ? (this.opacity - currentOpacity)/this.steps : 0;

      this.changeOpacityBy(delta);
      this.duration -= stepDuration;
      this.steps--;

      this.timer = setTimeout(this.fadeTo.bind(this), stepDuration);
   },

   changeOpacityBy: function(v) {
      var currentOpacity = this.getElementOpacity();
      var newOpacity = Math.max(0, Math.min(currentOpacity+v, 1));
      this.element.ricoOpacity = newOpacity;

      this.element.style.filter = "alpha(opacity:"+Math.round(newOpacity*100)+")";
      this.element.style.opacity = newOpacity; /*//*/;
   },

   isFinished: function() {
      return this.steps <= 0;
   },

   getElementOpacity: function() {
      if ( this.element.ricoOpacity == undefined ) {
         var opacity = MiniRicoUtil.getElementsComputedStyle(this.element, 'opacity');
         this.element.ricoOpacity = opacity != undefined ? opacity : 1.0;
      }
      return parseFloat(this.element.ricoOpacity);
   }
}

var MiniRicoUtil = {

   getElementsComputedStyle: function ( htmlElement, cssProperty, mozillaEquivalentCSS) {
      if ( arguments.length == 2 )
         mozillaEquivalentCSS = cssProperty;

      var el = $(htmlElement);
      if ( el.currentStyle )
         return el.currentStyle[cssProperty];
      else
         return document.defaultView.getComputedStyle(el, null).getPropertyValue(mozillaEquivalentCSS);
   }
}

Rico_Effect_SizeAndPosition = Class.create();
Rico_Effect_SizeAndPosition.prototype = {

   initialize: function(element, x, y, w, h, duration, steps, options) {
      this.element = $(element);
      this.x = x;
      this.y = y;
      this.w = w;
      this.h = h;
      this.duration = duration;
      this.steps    = steps;
      this.options  = arguments[7] || {};

      this.sizeAndPosition();
   },

   sizeAndPosition: function() {
      if (this.isFinished()) {
         if(this.options.complete) this.options.complete(this);
         return;
      }

      if (this.timer)
         clearTimeout(this.timer);

      var stepDuration = Math.round(this.duration/this.steps) ;

      // Get original values: x,y = top left corner;  w,h = width height
      var currentX = this.element.offsetLeft;
      var currentY = this.element.offsetTop;
      var currentW = this.element.offsetWidth;
      var currentH = this.element.offsetHeight;

      // If values not set, or zero, we do not modify them, and take original as final as well
      this.x = (this.x) ? this.x : currentX;
      this.y = (this.y) ? this.y : currentY;
      this.w = (this.w) ? this.w : currentW;
      this.h = (this.h) ? this.h : currentH;

      // how much do we need to modify our values for each step?
      var difX = this.steps >  0 ? (this.x - currentX)/this.steps : 0;
      var difY = this.steps >  0 ? (this.y - currentY)/this.steps : 0;
      var difW = this.steps >  0 ? (this.w - currentW)/this.steps : 0;
      var difH = this.steps >  0 ? (this.h - currentH)/this.steps : 0;

      this.moveBy(difX, difY);
      this.resizeBy(difW, difH);

      this.duration -= stepDuration;
      this.steps--;

      this.timer = setTimeout(this.sizeAndPosition.bind(this), stepDuration);
   },

   isFinished: function() {
      return this.steps <= 0;
   },

   moveBy: function( difX, difY ) {
      var currentLeft = this.element.offsetLeft;
      var currentTop  = this.element.offsetTop;
      var intDifX     = parseInt(difX);
      var intDifY     = parseInt(difY);

      var style = this.element.style;
      if ( intDifX != 0 )
         style.left = (currentLeft + intDifX) + "px";
      if ( intDifY != 0 )
         style.top  = (currentTop + intDifY) + "px";
   },

   resizeBy: function( difW, difH ) {
      var currentWidth  = this.element.offsetWidth;
      var currentHeight = this.element.offsetHeight;
      var intDifW       = parseInt(difW);
      var intDifH       = parseInt(difH);

      var style = this.element.style;
      if ( intDifW != 0 )
         style.width   = (currentWidth  + intDifW) + "px";
      if ( intDifH != 0 )
         style.height  = (currentHeight + intDifH) + "px";
   }
}
