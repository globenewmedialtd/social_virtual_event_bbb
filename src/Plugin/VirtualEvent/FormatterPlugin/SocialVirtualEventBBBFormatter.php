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

class SocialVirtualEventBBBFormatter extends VirtualEventBBBFormatter {

  /**
   * {@inheritdoc}
   */
  public function handleSettingsForm(FormStateInterface &$form_state, ?EntityDisplayInterface $display, ?array $options) {
    
    $form = parent::handleSettingsForm($form_state,$display,$options);

    $form['join_button_text'] = [
      '#title' => t('Join Button Text'),
      '#type' => 'textfield',
      '#default_value' => $options["join_button_text"] ? $options["join_button_text"] : "Join Meeting",
      '#maxlength' => 255,
    ];

    $form['show_iframe'] = [
      '#title' => t('Show Iframe'),
      '#type' => 'checkbox',
      '#default_value' => $options["show_iframe"] ? TRUE : FALSE,
      '#description' => t('Show meeting as iframe'),
    ];

    unset($form['recordings']);    

    $form['modal'] = [
      '#type' => 'details',
      '#title' => t('Modal'),
    ];

    $form['modal']['open_in_modal'] = [
      '#title' => t('Open in modal popup'),
      '#type' => 'checkbox',
      '#default_value' => $options["modal"]["open_in_modal"] ? TRUE : FALSE,
      '#description' => t('Open meeting in modal popup, this only works for non-administrators, admins will be redirected to the meeting in new tab.'),
      '#attributes' => [
        'id' => 'field_open_in_modal',
      ],
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
    
    $element = parent::viewElement($entity, $event, $formatters_config, $source_config, $source_data, $formatter_options);
     
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

            if (!$display_options["show_iframe"]) {
              if (isset($display_options["modal"], $display_options["modal"]["open_in_modal"]) && $display_options["modal"]["open_in_modal"]) {
                if ($entity->access('view')) {
                  if (empty($display_options)) {
                    $display_options = $this->defaultSettings();
                  }
                  if (!$entity->access('update')) {
                  
                    $element["virtual_event_bbb_modal"] = [
                      '#theme' => 'virtual_event_bbb_modal',
                      '#join_url' => Url::fromRoute('virtual_event_bbb.virtual_event_bbb_modal_controller_join', ['event' => $event->id()]),
                      '#display_options' => $display_options,
                    ];
                  }
                  else {
                    $element["join_link"] = \Drupal::formBuilder()->getForm(VirtualEventBBBLinkForm::class, $event, $display_options);
                 }
                }
              }
              else {
                $element["join_link"] = \Drupal::formBuilder()->getForm(VirtualEventBBBLinkForm::class, $event, $display_options);
              }

              // We want to hide the form if the setting points to TRUE
              if($display_options['show_recordings_only']) {
                unset($element["join_link"]);
              }

            }
            else {
              $apiUrl = $keys["url"];
              $secretKey = $keys["secretKey"];
              $bbb = new VirtualEventBBB($secretKey, $apiUrl);
              /* Check if meeting is not active,
              recreate it before showing the join url */
              $event->reCreate();

              /* Check access for current entity, if user can update
              then we can consider the user as moderator,
              otherwise we consider the user as normal attendee.
              */
              if ($entity->access('update')) {
                $joinMeetingParams = new JoinMeetingParameters($event->id(), $user->getDisplayName(), $settings["moderatorPW"]);
              }
              elseif ($entity->access('view')) {
                $joinMeetingParams = new JoinMeetingParameters($event->id(), $user->getDisplayName(), $settings["attendeePW"]);
              }

              $joinMeetingParams->setRedirect(TRUE);
              try {
                $url = $bbb->getJoinMeetingURL($joinMeetingParams);

                $element["meeting_iframe"] = [
                  '#theme' => 'virtual_event_bbb_iframe',
                  '#url' => $url,
                ];
              } catch (\RuntimeException $exception) {
                watchdog_exception('virtual_event_bbb', $exception, $exception->getMessage());
                drupal_set_message(t("Couldn't get meeting join link! please contact system administrator."), 'error');
             }         
            }         

            unset($element["meeting_recordings"]);

        }
        else {
          $element["join_link"] = \Drupal::formBuilder()->getForm(VirtualEventBBBLinkForm::class, $event);
        }
      }
    }
    catch (\RuntimeException $error) {
      $element = [];
    }
    return $element;
  }

}
