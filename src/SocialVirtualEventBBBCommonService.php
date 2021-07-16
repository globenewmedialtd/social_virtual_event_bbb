<?php

namespace Drupal\social_virtual_event_bbb;

use Drupal\Core\Config\ConfigFactoryInterface;


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

  /**
   * SocialVirtualEventBBBCommonService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Get all allowed recording access options.
   *
   * @return []
   *   Array of allowed recording access options.
   */
  public function getAllAllowedRecordingAccessOptions() {
    return [
      'recording_access_viewer' => 'View access all users',
      'recording_access_viewer_authenticated' => 'View access authenticated users',
      'recording_access_viewer_moderator' => 'View access Moderator',
      'recording_access_viewer_enrolled' => 'View Access enrolled users'
    ];   
  }

  public function getFontSize() {
    $config = $this->configFactory()->getEditable('social_virtual_event_bbb.settings');
    return $config->get('count_down_font_size');
  }


}
