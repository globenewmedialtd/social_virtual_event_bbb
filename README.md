# social_virtual_event_bbb
This module overwrites the handling for Virtual Event BBB Meetings
## Installation
Install this module like any other Drupal module.
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





 
