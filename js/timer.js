(function ($, Drupal, drupalSettings) {
    "use strict";
    /**
     * Attaches the JS countdown behavior
     */
    Drupal.behaviors.showHideJoinButton = {
      attach: function (context, settings) {
        function ticker() {
          var current = Math.floor(Date.now() / 1000)
      	  var timer = drupalSettings.timer.unixtimestamp;
          var timedifference = current - timer;

console.log(timedifference);
	  if (timedifference >= 0) {
            $('#virtual-event-bbb-link-form #edit-submit').addClass('visually-hidden');
          }         
        }    
    

      setInterval(function() {
       ticker();
      }, 1000);
     }
    };
})(jQuery, Drupal, drupalSettings);
