<?php

namespace Drupal\social_virtual_event_bbb\Plugin\VirtualEvent\FormatterPlugin;

use Drupal\virtual_event_bbb\Plugin\VirtualEvent\FormatterPlugin\VirtualEventBBBFormatter;
use Drupal\virtual_events\Plugin\VirtualEventFormatterPluginBase;
use Drupal\virtual_events\Entity\VirtualEventsEventEntity;
use Drupal\virtual_events\Entity\VirtualEventsFormatterEntity;
use Drupal\virtual_event_bbb\VirtualEventBBB;
use Drupal\virtual_event_bbb\Form\VirtualEventBBBLinkForm;
use Drupal\Core\Entity\EntityInterface;
use BigBlueButton\Parameters\JoinMeetingParameters;
use BigBlueButton\Parameters\GetRecordingsParameters;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\Core\Url;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\group\Entity\GroupInterface;



/**
 * Plugin implementation of the 'virtual_event_bbb_meeting_recordings' formatter.
 *
 * @VirtualEventFormatterPlugin(
 *   id = "virtual_event_bbb_meeting_recordings",
 *   label = @Translation("Virtual Event BBB Meeting Recordings Formatter"),
 *   sourceTypes = {
 *        "virtual_event_bbb"
 *   },
 * )
 */
class SocialVirtualEventBBBRecordingFormatter extends VirtualEventBBBFormatter {

  /**
   * {@inheritdoc}
   */
  public function handleSettingsForm(FormStateInterface &$form_state, ?EntityDisplayInterface $display, ?array $options) {
    
    $form = [];

    $form['recordings'] = [
      '#type' => 'details',
      '#title' => t('Recordings'),
    ];
    $form['recordings']['show_recordings'] = [
      '#title' => t('Show Recordings'),
      '#type' => 'checkbox',
      '#default_value' => $options["recordings"]["show_recordings"] ? TRUE : FALSE,
      '#description' => t('Show meeting recordings if any'),
      '#attributes' => [
        'id' => 'field_show_recordings',
      ],
    ];
    $form['recordings']['recordings_display'] = [
      '#title' => t('Recordings Display'),
      '#type' => 'select',
      "#options" => [
        'links' => "Links",
        'linked_thumbnails' => "Linked Thumbnails",
        'video' => "Video Player",
      ],
      '#default_value' => $options["recordings"]["recordings_display"] ? $options["recordings"]["recordings_display"] : 'links',
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          ':input[id="field_show_recordings"]' => ['checked' => TRUE],
        ],
      ],
      '#description' => t('How to display the recordings'),
    ]; 

    $form['modal'] = [
      '#type' => 'details',
      '#title' => t('Modal'),
    ];
    
    $form['modal']['width'] = [
      '#title' => t('Modal Width'),
      '#type' => 'textfield',
      '#default_value' => $options["width"] ? $options["width"] : "95%",
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          ':input[id="field_open_in_modal"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['modal']['height'] = [
      '#title' => t('Modal Height'),
      '#type' => 'textfield',
      '#default_value' => $options["height"] ? $options["height"] : "95%",
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          ':input[id="field_open_in_modal"]' => ['checked' => TRUE],
        ],
      ],
    ];  

    return $form;

  }


  /**
   * {@inheritdoc}
   */
  public function viewElement(EntityInterface $entity, VirtualEventsEventEntity $event, VirtualEventsFormatterEntity $formatters_config, array $source_config, array $source_data, array $formatter_options) {
    
    //$element = parent::viewElement($entity, $event, $formatters_config, $source_config, $source_data, $formatter_options);
    
    $element = [];

    $grant_access = FALSE;
    $user = \Drupal::currentUser();
    $entity_type = $entity->getEntityTypeId();
    $entity_bundle = $entity->bundle();
    $entity_id = $entity->id();
    $BBBKeyPluginManager = \Drupal::service('plugin.manager.bbbkey_plugin');
    $socialVirtualEventsCommon = \Drupal::service('social_virtual_event_bbb.common');


    
    //$element = [];
    $settings = [];
    if (isset($source_data["settings"])) {
      $settings = $source_data["settings"];
    }

    if(!isset($source_config["data"]["key_type"])) return;

    $keyPlugin = $BBBKeyPluginManager->createInstance($source_config["data"]["key_type"]);
    $keys = $keyPlugin->getKeys($source_config);
    try {
      if ($event) {
        if ($formatter_options) {
          $display_options = $formatter_options;     


          if ($display_options["recordings"]["show_recordings"]) {

            $grant_access = TRUE;

            // We need to check if we are on event node
            if ($entity_type === 'node' && $entity_bundle === 'event') {

              // Check if we have to restrict recording access
              $virtual_event_bbb_config_entity = $socialVirtualEventsCommon->getSocialVirtualEventBBBEntityConfig($entity_id);
              $grant_access = TRUE;

              if ($virtual_event_bbb_config_entity) {
                $grant_access = FALSE;
                $recording_access = $virtual_event_bbb_config_entity->getRecordingAccess();
  
                if ($recording_access === 'recording_access_viewer_moderator') {
                  if ($entity->access('update')) {
                    $grant_access = TRUE;
                  }
                }

                if ($recording_access === 'recording_access_viewer') {
                  if ($entity->access('view')) {
                    $grant_access = TRUE;
                  }
                }

                if ($recording_access === 'recording_access_viewer_authenticated') {
                  if ($entity->access('view') && $user->isAuthenticated()) {
                    $grant_access = TRUE;
                  }
                }

                if ($recording_access === 'recording_access_viewer_enrolled') {

		              // Set member to false
                  $is_member = FALSE; 

                  $event_enrollment = \Drupal::entityTypeManager()->getStorage('event_enrollment');
                  $enrolled = $event_enrollment->loadByProperties([
                    'field_account' => $user->id(),
                    'field_event' => $entity_id,
                    'field_enrollment_status' => 1,
                  ]);

                 
		              $group = _social_group_get_current_group($entity);

		              // Get Account
    		          $account = \Drupal::entityTypeManager()
        	          ->getStorage('user')
                    ->load($user->id());

                  if ($group instanceof GroupInterface) {
                    $is_member = $group->getMember($account);
                  }
 
                  if ($entity->access('view') && ($enrolled || $is_member)) {
                    $grant_access = TRUE;
                  }
                
                }

                if ($recording_access === 'recording_access_viewer_group') {

                  // Set member to false
                  $is_member = FALSE; 

                  $group = _social_group_get_current_group($entity);

		              // Get Account
    		          $account = \Drupal::entityTypeManager()
        	          ->getStorage('user')
                    ->load($user->id());

                  if ($group instanceof GroupInterface) {
                    $is_member = $group->getMember($account);
                  }
 
                  if ($entity->access('update') && $is_member) {
                    $grant_access = TRUE;
                  }
                
                }
              }
            }
            
            if ($grant_access) {
            
              $apiUrl = $keys["url"];
              $secretKey = $keys["secretKey"];
              $bbb = new VirtualEventBBB($secretKey, $apiUrl);

              $recordingParams = new GetRecordingsParameters();
              $recordingParams->setMeetingID($event->id());

              try {
                $response = $bbb->getRecordings($recordingParams);                
                if (!empty($response->getRawXml()->recordings->recording)) {
                  $recordings = [];
                  foreach ($response->getRawXml()->recordings->recording as $key => $recording){
                    foreach ($recording->playback as $key => $playback){
                      foreach ($recording->playback->format as $key => $format){
                        if($format->type == "video_mp4" || $format->type == "presentation"){
                          $format->recordID = $recording->recordID;
                          $recordings[] = $format;
                        }
                      }
                    }
                  }

                  

                switch ($display_options["recordings"]["recordings_display"]) {
                  case 'links':
                    $element["meeting_recordings"] = [
                      '#theme' => 'virtual_event_bbb_recordings_links',
                      '#url' => Url::fromRoute('virtual_event_bbb.virtual_event_b_b_b_recording_controller_view_recording', ['event' => $event->id()]),
                      '#display_options' => $display_options,
                      '#recordings' => $recordings,
                    ];
                    break;

                  case 'linked_thumbnails':
                    $element["meeting_recordings"] = [
                      '#theme' => 'virtual_event_bbb_recordings_linked_thumbnails',
                      '#url' => Url::fromRoute('virtual_event_bbb.virtual_event_b_b_b_recording_controller_view_recording', ['event' => $event->id()]),
                      '#display_options' => $display_options,
                      '#recordings' => $recordings,
                    ];
                    break;

                  case 'video':
                    $element["meeting_recordings"] = [
                      '#theme' => 'virtual_event_bbb_recordings_video',
                      '#recordings' => $recordings,
                    ];
                    break;

                  default:
                    $element["meeting_recordings"] = [
                      '#theme' => 'virtual_event_bbb_recordings_links',
                      '#url' => Url::fromRoute('virtual_event_bbb.virtual_event_b_b_b_recording_controller_view_recording', ['event' => $event->id()]),
                      '#display_options' => $display_options,
                      '#recordings' => $recordings,
                    ];
                    break;
                }
              }
              } catch (\RuntimeException $exception) {
                watchdog_exception('virtual_event_bbb', $exception, $exception->getMessage());
                drupal_set_message(t("Couldn't get recordings! please contact system administrator."), 'error');
              }
            }
            else {
              // We do not have any right to see recordings
              unset($element["meeting_recordings"]);
            }
          }
        }
      }
    }
    catch (\RuntimeException $error) {
      $element = [];
    }
    return $element;
  }

}
