(function ($, Drupal, drupalSettings) {
    "use strict";
    /**
     * Attaches the JS countdown behavior
     */
    Drupal.behaviors.jsCountdownTimer = {
        attach: function (context) {
            var note = $('#jquery-countdown-timer-note'),
                ts = new Date(drupalSettings.countdown.unixtimestamp * 1000);

            $(context).find('#jquery-countdown-timer').once('jquery-countdown-timer').countdown({
                timestamp: ts,
                font_size: drupalSettings.countdown.fontsize,
                callback: function (weeks, days, hours, minutes, seconds) {
                    var dateStrings = new Array();
                    dateStrings['@weeks'] = Drupal.formatPlural(weeks, '@count week', '@count weeks');
                    dateStrings['@days'] = Drupal.formatPlural(days, '@count day', '@count days');
                    dateStrings['@hours'] = Drupal.formatPlural(hours, '@count hour', '@count hours');
                    dateStrings['@minutes'] = Drupal.formatPlural(minutes, '@count minute', '@count minutes');
                    dateStrings['@seconds'] = Drupal.formatPlural(seconds, '@count second', '@count seconds');
                    var message = Drupal.t('@hours : @minutes : @seconds', dateStrings);
                    if (message === '0 hours : 0 minutes : 0 seconds') {
                      $('#jquery-countdown-timer').hide();
                      $('#virtual-event-bbb-link-form #edit-submit').removeClass('visually-hidden');
                    }
	            //console.log(message);
                    //note.html(message);
                }
            });
        }
    };
})(jQuery, Drupal, drupalSettings);