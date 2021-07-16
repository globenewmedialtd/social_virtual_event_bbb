(function ($, Drupal, drupalSettings) {
    "use strict";
    /**
     * Attaches the JS countdown behavior
     */
    Drupal.behaviors.showHideJoinButton = {
      attach: function (context, settings) {
        function ticker() {
          var current = Math.round((new Date()).getTime() / 1000);
      	  var timer = new Date(drupalSettings.timer.unixtimestamp * 1000);
          var timedifference = current - timer;
	  if (timedifference === 0) {
            $('#virtual-event-bbb-link-form #edit-submit').addClass('visually-hidden');
          }         
        }    
    

      setInterval(function() {
       ticker();
      }, 1000);
     }
    };
})(jQuery, Drupal, drupalSettings);
