# social_virtual_event_bbb
This module overwrites the handling for Virtual Event BBB Meetings
## Installation
Install this module like any other Drupal module.
## Configuaration
After installing the module please configure as followed:
- 1. Visit your content type 'event' admin/structure/types/manage/event/display and enable the new view mode 'BBB Recording'
- 2. Now make sure that the display 'BBB Recording' shows only the field formatter 'Virtual Event BBB Meeting Formatter' and the 'Get the related GROUP NAME group groups for this entity. Depending on your available groups. You can watch at a different view mode to see what you need.
- 3. Visit your Block Layout and enable the following blocks: 'BBB Recording List', 'Social Virtual Event BBB Join Button Block' and if you have activated NODEJS you may want to enable 'Social Virtual Event BBB Statistics Block'
- 4. Please make sure that you enalbe block visibility. All blocks work only on Node type events.
- 5. Visit admin/virtual_events/settings and save your preferred settings for the module.

## Nodejs - Installation
You need to have Node.js version 10+ installed on your system. 
- 1. https://www.digitalocean.com/community/tutorials/how-to-install-node-js-on-ubuntu-18-04-de
- 2. npm install drupal-node.js
- 3. Be sure the install the app outside of Drupal's root directory. The correct installation root would be the main direcotry of your project where your composer.json lives. After installing you should see node_modules inside your projects main directory.
- 4. Visit the backend config for nodejs: admin/config/nodejs/settings
- 5. Define Host name for NODEJS Server, The server port should be set to 8080, if available. Define a service key and rember that key. Define Node.js server host for client javascript. Set port again to 8080 if possible.
- 6. Now visit the nodejs config file inside node_modules/drupal-node.js/nodejs.config.js

Exmaple:

```
settings = {
  scheme: 'http',
  port: 8080,
  host: 'localhost',
  serviceKey: 'YOUR_SERVICE_KEY',
  backend: {
    port: 80,
    host: 'yourwebiste.com',
    scheme: 'http',
    basePath: '',
    messagePath: '/nodejs/message'
  },
  debug: true,
  bodyParserJsonLimit: '1mb',
  sslKeyPath: '',
  sslCertPath: '',
  sslCAPath: '',
  baseAuthPath: '/nodejs/',
  extensions: [],
  socketOptions: {}
};

```

- 7. Now start the app with node app.js inside that directory. On production you may want to use forever or pm2 to make sure nodejs will be running without the need to start it all the time.


## Dependencies
Make sure you have installed and enabled the following modules:
- virtual_event_bbb
- social_event_an_enroll
## How that module works
This module acts on event content types only and uses the enrollment handling from open social to show or hide the "Join Meeting" link. It uses the open social enrollment service for "anonymous users" and grants access to virtual events once a valid token has been detected. Please note that this works only for "Public groups" as designed by OpenSocial.
## New Features
- Option to increase the font size of the timer in the settings of this module. The solution for the timer does not use any icons or images and its size can be increased and decreased by the font size.
- Option for Recording Access lives now in the settings of this module
- New custom Block 'Social Virtual Event BBB Join Button Block'
- Reset Button on Virtual Event Sources inside Events
- Recording Access (Please do not forget to first visit settings page and make the adjustments)
- Additional Settings
- Nodejs Integration
- New custom Block 'Social Virtual Event BBB Statistics Block'
- New custom Block 'BBB Recording List'
- New view BBB Recording List'
- New BBB Setting 'moderator_only_message' 
- New BBB Setting 'allow_mods_to_unmute_users'
- New Callback for Communication between BBB and Drupal
- New Display Mode: BBB Recording
- New Rest Endpoint RestResourceBBBMeeting

Please note: The new block should replace the block view, because when setting "Show in entity page" has been disabled, the view does not show any results. So now the 'join meeting button' is a custom block and does not use any view or view mode.

## Removed Features
- View mode 'joinbutton_block'
- View 'joinbutton_block'
- Entity form display mode





 
