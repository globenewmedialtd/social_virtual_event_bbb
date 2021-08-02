<?php

namespace Drupal\social_virtual_event_bbb;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\virtual_event_bbb\VirtualEventBBB;
use BigBlueButton\Parameters\GetMeetingInfoParameters;
use Drupal\social_virtual_event_bbb\Entity\VirtualEventBBBConfigEntity;
use Drupal\social_virtual_event_bbb\Entity\VirtualEventBBBConfigEntityInterface;
use Drupal\node\NodeInterface;
use BigBlueButton\Parameters\HooksCreateParameters;
use Drupal\Core\Url;


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
        //kint($response->getReturnCode());
        if ($response->getReturnCode() === 'SUCCESS') {
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
            
        
      }
      catch (\RuntimeException $exception) {
        watchdog_exception('social_virtual_event_bbb', $exception, $exception->getMessage());
        drupal_set_message(t("Couldn't get meeting info! please contact system administrator."), 'error');
      }
    }

    return $meeting_info;

  }

  /*
   * Get config
   */
  public function getSocialVirtualEventBBBEntityConfig($nid) {

    $virtual_event_bbb_config_entity = $this->entityTypeManager
      ->getStorage('virtual_event_bbb_config_entity')
      ->load($nid);
    
    if ($virtual_event_bbb_config_entity instanceof VirtualEventBBBConfigEntityInterface) {
      return $virtual_event_bbb_config_entity;
    }

    return FALSE;

  }

  /*
   * Get extra config
   */
  public function createSocialVirtualEventBBBEntityConfig($nid, $config_data) {

    $config = VirtualEventBBBConfigEntity::create([
      'id' => $nid,
    ]);
    $config->setRecordingAccess($config_data['recording_access']);
    $config->setJoinButtonVisibleBefore($config_data['join_button_visible_before']);
    $config->setJoinButtonVisibleAfter($config_data['join_button_visible_after']);
    $config->setNode($nid);
    $config->save();

  }
  
  /*
   * Create extra config
   */
  public function updateSocialVirtualEventBBBEntityConfig($nid, $config_data) {

    $virtual_event_bbb_config_entity = $this->entityTypeManager
      ->getStorage('virtual_event_bbb_config_entity')
      ->load($nid);
    
    if ($virtual_event_bbb_config_entity instanceof VirtualEventBBBConfigEntityInterface) {
      $virtual_event_bbb_config_entity->setRecordingAccess($config_data['recording_access']);
      $virtual_event_bbb_config_entity->setJoinButtonVisibleBefore($config_data['join_button_visible_before']);
      $virtual_event_bbb_config_entity->setJoinButtonVisibleAfter($config_data['join_button_visible_after']);
      $virtual_event_bbb_config_entity->save();
    }

  }  

  /*
   * Get options for join meeting button
   */
  public function getOptionsForJoinMeetingButton() {
    return [
      'show_always_open' => t('Show always open'),
      '15' => t('15 minutes'),
      '30' => t('30 minutes'),
      '45' => t('45 minutes'),
      '60' => t('60 minutes'),
      '90' => t('90 minutes'),
    ];
  }

  /*
   * Nodejs support active or not
   */
  public function isNodejsActive() {
    $config = $this->configFactory->getEditable('social_virtual_event_bbb.settings');
    $nodejs = $config->get('nodejs_support') ? $config->get('nodejs_support') : FALSE; 
    if ($nodejs) {
      return TRUE;
    }

    return FALSE;

  }
  
  /*
   * Create Meeting Callback
   */
  public function createMeetingCallback($nid) {

    $config = $this->configFactory->getEditable('social_virtual_event_bbb.settings');
    
    // Don't create callback when setting is FALSE
    if ($config->get('add_bbb_server_callback') === FALSE) {
      return;
    }

    // Fire only on Node type event
    $callbackHost = \Drupal::request()->getSchemeAndHttpHost();
    $callbackPath  = '/api/bbb-meeting-webhook/' . $entity->id();
    $callbackUrl = Url::fromUri($callbackHost . $callbackPath);
    $callbackUrl->setOption('query', [
      '_format' => 'json',
    ]);
    $callbackLink = $callbackUrl->toString();
    $HooksCreateParameters = new HooksCreateParameters($callbackLink);
    $HooksCreateParameters->setMeetingId($event->id());
    try {
      $hook_response = $bbb->hooksCreate($HooksCreateParameters);
      \Drupal::logger('social_virtual_event_bbb')->notice('<pre><code>' . print_r($hook_response, TRUE) . '</code></pre>');
      if ($hook_response->getReturnCode() == 'FAILED') {
        drupal_set_message(t("Couldn't create bbb webhook! please contact system administrator."), 'error');
      }
      else {
        drupal_set_message(t("Successfully registered bbb webhook for meeteing"), 'info');
      }
    } 
    catch (\RuntimeException $exception) {
      watchdog_exception('social_virtual_event_bbb', $exception, $exception->getMessage());
      drupal_set_message(t("Couldn't create bbb webhook! please contact system administrator."), 'error');
    } 
    catch (Exception $exception) {
      watchdog_exception('social_virtual_event_bbb', $exception, $exception->getMessage());
      drupal_set_message(t("Couldn't create bbb webhook! please contact system administrator."), 'error');
    }   

  }

}
