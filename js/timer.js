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
	  if (timedifference >= 0) {
            $('#virtual-event-bbb-link-form button').addClass('visually-hidden');
            $('.block-social-virtual-event-bbb-join-button-block').addClass('visually-hidden');
            clearInterval(interval);   
          }         
        }    

     var interval = setInterval(function() {
       ticker();
      }, 1000);

     }
    };
})(jQuery, Drupal, drupalSettings);
