<?php

namespace Drupal\social_virtual_event_bbb\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;


/**
 * Defines the Social Virtual Event BBB Config entity.
 *
 * @ConfigEntityType(
 *   id = "virtual_event_bbb_config_entity",
 *   label = @Translation("Social Virtual Event BBB Config entity"),
 *   config_prefix = "virtual_event_bbb_config_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class VirtualEventBBBConfigEntity extends ConfigEntityBase implements VirtualEventBBBConfigEntityInterface {

  /**
   * The ID of the setting.
   *
   * @var string
   */
  protected $id;


  /**
   * The id of the event node.
   *
   * @var string
   */
  protected $node;

  /**
   * Recording Access.
   *
   * @var string
   */
  protected $recording_access;

  /**
   * Join button visible before.
   *
   * @var string
   */
  protected $join_button_visible_before;

  /**
   * Join button visible after.
   *
   * @var string
   */
  protected $join_button_visible_after;

  /**
   * {@inheritdoc}
   */
  public function getRecordingAccess() {
    return $this->recording_access;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecordingAccess(string $recording_access) {
    $this->recording_access = $recording_access;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getJoinButtonVisibleBefore() {
    return $this->join_button_visible_before;
  }

  /**
   * {@inheritdoc}
   */
  public function setJoinButtonVisibleBefore(string $join_button_visible_before) {
    $this->join_button_visible_before = $join_button_visible_before;
    return $this;
  }  

  /**
   * {@inheritdoc}
   */
  public function getJoinButtonVisibleAfter() {
    return $this->join_button_visible_after;
  }

  /**
   * {@inheritdoc}
   */
  public function setJoinButtonVisibleAfter(string $join_button_visible_after) {
    $this->join_button_visible_after = $join_button_visible_after;
    return $this;
  }   

  /**
   * {@inheritdoc}
   */
  public function getNode() {
    return $this->node;
  }

  /**
   * {@inheritdoc}
   */
  public function setNode(string $node) {
    $this->node = $node;
    return $this;
  }

}
