<?php

namespace Drupal\social_virtual_event_bbb;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\virtual_event_bbb\VirtualEventBBB;
use BigBlueButton\Parameters\GetMeetingInfoParameters;


/**
 * Defines Social Virtual Event BBB Common Service.
 */
class SocialVirtualEventBBBCommonService {

  /**
   * Configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /** Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SocialVirtualEventBBBCommonService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Get all allowed recording access options.
   *
   * @return []
   *   Array of allowed recording access options.
   */
  public function getAllAllowedRecordingAccessOptions() {
    return [
      'recording_access_viewer' => 'All users (including anonymous users)',
      'recording_access_viewer_authenticated' => 'All authenticated users',
      'recording_access_viewer_moderator' => 'Only event organisers',
      'recording_access_viewer_enrolled' => 'Organisers & participants'
    ];   
  }

  public function getFontSize() {
    $config = $this->configFactory->getEditable('social_virtual_event_bbb.settings');
    $font_size = $config->get('count_down_font_size');
    if(isset($font_size)) {
      return $config->get('count_down_font_size');
    } 
    return FALSE;
  }

  public function createNodejsChannel($event_id) {

    //nodejs_send_content_channel_token($event_id);

    $testing['#markup'] = 'Super!!!';

    $replace_command = new ReplaceCommand('#bbb-meeting-info', $testing);
    $commands[] = $replace_command->render();

    $nodejs_message = (object) [
      //'channel' => $event_id,
      'commands' => $commands,
      'callback' => 'nodejsBBBEventStatistic',
    ];
    //nodejs_send_content_channel_message($nodejs_message);
    //nodejs_send_message($nodejs_message);

    

  }

  public function nodejsGetBBBStatistic($node) {
    $meeting_info = FALSE;
    $BBBKeyPluginManager = \Drupal::service('plugin.manager.bbbkey_plugin');    
    $virtualEventsCommon = \Drupal::service('virtual_events.common');
    $entity_type = $node->getEntityTypeId();
    $entity_id = $node->id();
    $event = $virtualEventsCommon->getEventByRefernce($entity_type, $entity_id);
    if ($event) {             
      $event_config = $event->getVirtualEventsConfig();
      $source_data = $event->getSourceData('virtual_event_bbb');
      $source_config = $event_config->getSourceConfig('virtual_event_bbb');
      if (!isset($source_config) && !$source_config['data']['key_type']) {
        return;
      }
      
      $keyPlugin = $BBBKeyPluginManager->createInstance($source_config["data"]["key_type"]);
      $keys = $keyPlugin->getKeys($source_config);
      $apiUrl = $keys["url"];
      $secretKey = $keys["secretKey"];
      $bbb = new VirtualEventBBB($secretKey, $apiUrl);
      $getMeetingInfoParameters = new GetMeetingInfoParameters($event->id(),$source_data['settings']['moderatorPW']);           

      try {
        
        $response = $bbb->getMeetingInfo($getMeetingInfoParameters); 
        if (!empty($response->getRawXml())) {
          $meeting_name = $response->getRawXml()->meetingName->__toString();
          $meeting_viewers = $response->getRawXml()->participantCount->__toString();
          $meeting_moderators = $response->getRawXml()->moderatorCount->__toString();
          $meeting_running = $response->getRawXml()->running->__toString();
          $meeting_joined = $response->getRawXml()->hasUserJoined->__toString();
          
          $meeting_info = [
            'meeting_name' => $meeting_name,
            'count_viewers' => $meeting_viewers,
            'count_moderators' => $meeting_moderators,
            'meeting_running' => $meeting_running,
            'meeting_joined' => $meeting_joined,
          ];
          
        }     
        
      }
      catch (\RuntimeException $exception) {
        watchdog_exception('social_virtual_event_bbb', $exception, $exception->getMessage());
        drupal_set_message(t("Couldn't get meeting info! please contact system administrator."), 'error');
      }
    }

    return $meeting_info;

  }
}
