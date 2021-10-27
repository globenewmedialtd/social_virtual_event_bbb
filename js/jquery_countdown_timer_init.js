(function ($, Drupal, drupalSettings) {
    "use strict";
    /**
     * Attaches the JS countdown behavior
     */
    Drupal.behaviors.jsCountdownTimer = {
        attach: function (context) {

            if(drupalSettings.countdown) {

                $.each( drupalSettings.countdown, function( index, value ) {
                    let ts = new Date(value.unixtimestamp * 1000);
                    let form_class = value.form_class + ' button';
		    let note = $('#' + value.timer_element_note);
                    
                    $(context).find('#' + value.timer_element).once(value.timer_element).countdown({
                        timestamp: ts,
                        font_size: value.fontsize,
                        callback: function (weeks, days, hours, minutes, seconds) {
                            let done = weeks + days + hours + minutes + seconds;
                            let dateStrings = new Array();
                            dateStrings['@weeks'] = Drupal.formatPlural(weeks, '@count week', '@count weeks');
                            dateStrings['@days'] = Drupal.formatPlural(days, '@count day', '@count days');
                            dateStrings['@hours'] = Drupal.formatPlural(hours, '@count hour', '@count hours');
                            dateStrings['@minutes'] = Drupal.formatPlural(minutes, '@count minute', '@count minutes');
                            dateStrings['@seconds'] = Drupal.formatPlural(seconds, '@count second', '@count seconds');
			    let message_headline = Drupal.t('Event starts in:');
			    let message = Drupal.t('@days, @hours, @minutes, @seconds.', dateStrings);
                    	    note.html('<div>' + message_headline + '</div>&nbsp;<div>' + message + '</div>');
                            if (done == 0) {
                              console.log('yep');
                              console.log(done);
			      note.hide();
                              $('#' + value.timer_element).hide();
                              $('form.' + form_class).removeClass('visually-hidden');
                            }
                        }
                    });

                });
                
            }

            if(drupalSettings.timer) {

                $.each( drupalSettings.timer, function( index, value ) {
                    let ts = new Date(value.unixtimestamp * 1000);
                    let form_class = value.form_class + ' button';
                    
                    $(context).find('#' + value.timer_element).once(value.timer_element).countdown({
                        timestamp: ts,
                        font_size: value.fontsize,
                        callback: function (weeks, days, hours, minutes, seconds) {
                            let dateStrings = new Array();
                            dateStrings['@weeks'] = Drupal.formatPlural(weeks, '@count week', '@count weeks');
                            dateStrings['@days'] = Drupal.formatPlural(days, '@count day', '@count days');
                            dateStrings['@hours'] = Drupal.formatPlural(hours, '@count hour', '@count hours');
                            dateStrings['@minutes'] = Drupal.formatPlural(minutes, '@count minute', '@count minutes');
                            dateStrings['@seconds'] = Drupal.formatPlural(seconds, '@count second', '@count seconds');
                            let message = Drupal.t('@hours : @minutes : @seconds', dateStrings);
                            if (message === '0 hours : 0 minutes : 0 seconds') {  
                                $('form.' + form_class).addClass('visually-hidden');
                                $('.block-social-virtual-event-bbb-join-button-block').addClass('visually-hidden');
                            }

                        }
                    });

                });
                
            }

        }
    };
})(jQuery, Drupal, drupalSettings);
