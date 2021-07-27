(function ($, Drupal, drupalSettings) {

  Drupal.Nodejs.callbacks.nodejsBBBEventStatistic = {
    callback: function (message) {
      console.log('where is the message log:');
      console.log(message);
      Drupal.nodejs_ajax.runCommands(message);
    }
  };

})(jQuery, Drupal, drupalSettings);

