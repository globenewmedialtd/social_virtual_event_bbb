<?php

namespace Drupal\social_virtual_event_bbb\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Welcome Message entities.
 */
interface VirtualEventBBBConfigEntityInterface extends ConfigEntityInterface {

  // Add get/set methods for your configuration properties here.
  public function getRecordingAccess();

  public function setRecordingAccess(string $recording_access);

  public function getJoinButtonVisibleBefore();

  public function setJoinButtonVisibleBefore(string $join_button_visible_before);

  public function getJoinButtonVisibleAfter();

  public function setJoinButtonVisibleAfter(string $join_button_visible_after);

  public function getNode();

  public function setNode(string $node);


}
