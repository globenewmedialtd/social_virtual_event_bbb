<?php

namespace Drupal\social_virtual_event_bbb\Plugin\VirtualEvent\SourcePlugin;

use Drupal\virtual_event_bbb\Plugin\VirtualEvent\SourcePlugin\VirtualEventBBBSource;
use Drupal\virtual_events\Plugin\VirtualEventSourcePluginBase;
use Drupal\virtual_events\Entity\VirtualEventsEventEntity;
use Drupal\Core\Form\FormStateInterface;
use Drupal\virtual_event_bbb\VirtualEventBBB;
use BigBlueButton\Parameters\GetMeetingInfoParameters;
use BigBlueButton\Parameters\CreateMeetingParameters;
use BigBlueButton\Parameters\HooksCreateParameters;
use Drupal\Core\Url;

class SocialVirtualEventBBBSource extends VirtualEventBBBSource {

  /**
   * {@inheritdoc}
   */
  public function checkMeeting(VirtualEventsEventEntity $event) {
    $BBBKeyPluginManager = \Drupal::service('plugin.manager.bbbkey_plugin');
    $event_config = $event->getVirtualEventsConfig($event->id());
    $source_config = $event_config->getSourceConfig($this->pluginId);
    $source_data = $event->getSourceData($this->pluginId);
    
    if(!isset($source_config["data"]["key_type"])) return;

    $keyPlugin = $BBBKeyPluginManager->createInstance($source_config["data"]["key_type"]);
    $keys = $keyPlugin->getKeys($source_config);

    $apiUrl = $keys["url"];
    $secretKey = $keys["secretKey"];
    $bbb = new VirtualEventBBB($secretKey, $apiUrl);

    /* Check if meeting is not active,
    recreate it before showing the join url */
    $getMeetingInfoParams = new GetMeetingInfoParameters($event->id(), $source_data["settings"]["moderatorPW"]);

    try {
      $response = $bbb->getMeetingInfo($getMeetingInfoParams);
      if ($response->getReturnCode() == 'FAILED') {
        return FALSE;
      }
    } catch (\RuntimeException $exception) {
      watchdog_exception('virtual_event_bbb', $exception, $exception->getMessage());
      drupal_set_message(t("Couldn't get meeting info! please contact system administrator."), 'error');
    }


    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createMeeting(VirtualEventsEventEntity $event) {
    $BBBKeyPluginManager = \Drupal::service('plugin.manager.bbbkey_plugin');
    $entity = $event->getEntity();
    $entityUrl = \Drupal::request()->getSchemeAndHttpHost() . $entity->toUrl()->toString();
    $logout_url = \Drupal::request()->getSchemeAndHttpHost() . Url::fromRoute('virtual_events.virtual_events_event_ended_controller_reload', ['url' => $entityUrl])->toString();
    $event_config = $event->getVirtualEventsConfig($event->id());
    $socialVirtualEventsCommon = \Drupal::service('social_virtual_event_bbb.common');
    

    /* Check if current enitity configured as meeting
    and there is no meeting entity created for it.
     */

    if ($event) {
      $source_config = $event_config->getSourceConfig($this->pluginId);
      $source_data = $event->getSourceData($this->pluginId);
      if(!isset($source_config["data"]["key_type"])) return;

      $keyPlugin = $BBBKeyPluginManager->createInstance($source_config["data"]["key_type"]);
      $keys = $keyPlugin->getKeys($source_config);

      $apiUrl = $keys["url"];
      $secretKey = $keys["secretKey"];
      $bbb = new VirtualEventBBB($secretKey, $apiUrl);
      $createMeetingParams = new CreateMeetingParameters($event->id(), $entity->label());
      if ($source_data["settings"]["welcome"]) {
        $createMeetingParams->setWelcomeMessage($source_data["settings"]["welcome"]);
      }
      // Prepare Event Link for moderators
      $node_url = Url::fromRoute('entity.node.canonical', ['node' => $entity->id()]);
      $node_url->setAbsolute(TRUE);    
      $moderator_only_message_text = t('To invite someone to the meeting, send them this link:'); 
      $moderator_only_message_link = $node_url->toString(); 
      
      if (isset($source_data["settings"]["moderator_only_message"])) {
        $moderator_message = $source_data["settings"]["moderator_only_message"] . ' ' . $moderator_only_message_text . ' ' . $moderator_only_message_link;
      }
      else {
        $moderator_message = $moderator_only_message_text . ' ' . $moderator_only_message_link;
      }
      if ($moderator_message) {
        $createMeetingParams->setModeratorOnlyMessage($moderator_message);
      }
      $logoPath = file_create_url(theme_get_setting('logo.url'));
      $createMeetingParams->setLogoutUrl($source_data["settings"]["logoutURL"] ? $source_data["settings"]["logoutURL"] : $logout_url);
      $createMeetingParams->setDuration(0);
      $createMeetingParams->setRecord(TRUE);
      $createMeetingParams->setAllowStartStopRecording(TRUE);
      $createMeetingParams->setLogo($logoPath);


      // Add Metadata
      $drupalHost = \Drupal::request()->getHost();
      \Drupal::logger('social_virtual_event_bbb')->notice('<pre><code>' . print_r($drupalHost, TRUE) . '</code></pre>' );
      $createMeetingParams->addMeta("bbb-origin", "Drupal");
      if (isset($drupalHost)) {
        $createMeetingParams->addMeta("bbb-origin-server-name", $drupalHost);
      }
      $createMeetingParams->addMeta("bbb-context", $entity->label());
      $createMeetingParams->addMeta("bbb-context-id", $entity->id());


      if ($source_data["settings"]["record"]) {
        $createMeetingParams->setAutoStartRecording(TRUE);
      }

      if($source_data["settings"]["mute_on_start"]) {
        $createMeetingParams->setMuteOnStart(TRUE);
      }

      try {
        $response = $bbb->createMeeting($createMeetingParams);
        \Drupal::logger('social_virtual_event_bbb')->notice('<pre><code>' . print_r($response, TRUE) . '</code></pre>' );
        if ($response->getReturnCode() == 'FAILED') {
          drupal_set_message(t("Couldn't create room! please contact system administrator."), 'error');
        }
        else {
          // We want to have a Hook created but only for Node Events only for now
          if ($entity instanceof NodeInterface && $entity->getType() === 'event') {
            $socialVirtualEventsCommon->createMeetingCallback($entity->id());
          }        

          $typeId = $this->getType();
          $source_data["settings"]["attendeePW"] = $response->getAttendeePassword();
          $source_data["settings"]["moderatorPW"] = $response->getModeratorPassword();
          $event->setSourceData($this->pluginId, $source_data);
          $event->save();
          return $event;

        }
        
      }
      catch (\RuntimeException $exception) {
        watchdog_exception('virtual_event_bbb', $exception, $exception->getMessage());
        drupal_set_message(t("Couldn't create room! please contact system administrator."), 'error');
      }
      catch (Exception $exception) {
        watchdog_exception('virtual_event_bbb', $exception, $exception->getMessage());
        drupal_set_message(t("Couldn't create room! please contact system administrator."), 'error');
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(FormStateInterface $form_state, ?array $pluginConfigValues) {
    
    $form = parent::buildConfigurationForm($form_state,$pluginConfigValues);
    
    $BBBKeyPluginManager = \Drupal::service('plugin.manager.bbbkey_plugin');
    $keyPlugins = $BBBKeyPluginManager->getDefinitions();
    $keyOpts = [];
    foreach ($keyPlugins as $key => $plugin_definition) {
      $plugin = $BBBKeyPluginManager->createInstance($key);
      $keyOpts[$key] = $plugin_definition["label"];
    }


    if(!empty($keyOpts)){
      $form["key_type"] = [
        '#title' => t('BBB Key Type'),
        '#type' => 'select',
        '#options' => $keyOpts,
        '#default_value' => $pluginConfigValues["key_type"] ? $pluginConfigValues["key_type"] : array_keys($keyOpts)[0],
        '#attributes' => [
          'id' => 'virtual_event_bbb_key_type',
        ]
      ];
    }
    foreach ($keyPlugins as $key => $plugin_definition) {
      $plugin = $BBBKeyPluginManager->createInstance($key);
      $settings_form = $plugin->buildConfigurationForm($form_state, $pluginConfigValues['keys'][$key]['settings']);
      if(!empty($settings_form)){
        $form['keys'][$key] = [
          '#title' => $plugin->getPluginDefinition()['label'],
          '#description' => $plugin_definition["description"] ? $plugin_definition["description"] : "",
          '#type' => 'details',
          '#open' => TRUE,
          '#weight' => 100,
          '#states' => [
            'visible' => [
              ':input[id="virtual_event_bbb_key_type"]' => ['value' => $key]
            ],
          ],
        ];
        $form['keys'][$key]['settings'] = $plugin->buildConfigurationForm($form_state, $pluginConfigValues['keys'][$key]['settings']);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntityForm(FormStateInterface $form_state, ?VirtualEventsEventEntity $event, array $source_data = []) {
    $form = parent::buildEntityForm($form_state, $event, $source_data);
    $settings = [];
    if (isset($source_data["settings"])) {
      $settings = $source_data["settings"];
    }

    $entity = $form_state->getformObject()->getEntity();
    $entityTypeId = $entity->getEntityTypeId();
    $entityBundle = $entity->bundle();

    $is_original_language = (bool) $entity->getFieldValue('default_langcode', 'value');
    
    if (!$is_original_language) {
      $on_translation_disabled = TRUE;
      $on_translation_description = t('This field has been disabled when translating.');      
    }
    else {
      $on_translation_disabled = FALSE;
      $on_translation_description = '';
    }

    if (!$is_original_language) {
      $form['translate_info'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $on_translation_description,
        '#weight' => -100,
        '#attributes' => [
          'class' => ['help-block']
        ]
      ];
    }

    $form['welcome'] = [
      '#title' => t('Welcome message'),
      '#type' => 'textfield',
      '#default_value' => $settings["welcome"] ? $settings["welcome"] : "",
      '#maxlength' => 255,
      '#description' => t('A welcome message that gets displayed on the chat window when the participant joins. You can include keywords (%%CONFNAME%%, %%DIALNUM%%, %%CONFNUM%%) which will be substituted automatically.'),
      '#disabled' => $event !== NULL,
    ];
    $form['moderator_only_message'] = [
      '#title' => t('Message to moderators only'),
      '#type' => 'textfield',
      '#default_value' => $settings["moderator_only_message"] ? $settings["moderator_only_message"] : "",
      '#maxlength' => 255,
      '#description' => t('A message that gets displayed on the chat window when the moderator joins.'),
      '#disabled' => $event !== NULL, 
    ];
    $form['logoutURL'] = [
      '#title' => t('Logout URL'),
      '#type' => 'textfield',
      '#default_value' => $settings["logoutURL"] ? $settings["logoutURL"] : "",
      '#maxlength' => 255,
      '#description' => t('The URL that the users will be redirected to after they logs out of the conference, leave empty to redirect to the current entity.'),
      '#disabled' => $event !== NULL,
    ];
    $form['mute_on_start'] = [
      '#type' => 'checkbox',
      '#options' => [
         0 => t('Disable'),
         1 => t('Enable'),
      ],
      '#title' => t('Mute on start'),
      '#default_value' => $settings['mute_on_start'] ? $settings['mute_on_start'] : TRUE,
      '#disabled' => $event !== NULL, 
    ];

    // We do not want auto recording
    if (isset($form['record'])) {
      unset($form['record']);
    }
    
    /*       
    $form['record'] = [
      '#title' => t('Record meeting'),
      '#type' => 'select',
      '#default_value' => $settings["record"] ? $settings["record"] : "",
      '#options' => [
        0 => t('Do not record'),
        1 => t('Record'),
      ],
      '#description' => t('Whether to automatically start recording when first user joins, Moderators in the session can still pause and restart recording using the UI control.'),
      '#disabled' => $event !== NULL,
    ];

    */


    

    if (isset($entityTypeId) && isset($entityBundle) && $entityTypeId === 'node' && $entityBundle === 'event' ) {

      $socialVirtualEventsCommon = \Drupal::service('social_virtual_event_bbb.common');
      // Recording Access
      $default_recording_access_allowed_options = $socialVirtualEventsCommon->getAllAllowedRecordingAccessOptions();
      $social_virtual_event_bbb_settings = \Drupal::config('social_virtual_event_bbb.settings');
      $recording_access_allowed_options = $social_virtual_event_bbb_settings->get('recording_access_allowed');
      $recording_access_allowed_default_option = $social_virtual_event_bbb_settings->get('recording_access_default');
      // Get Join Meeting Button options
      $join_meeting_button_options = $socialVirtualEventsCommon->getOptionsForJoinMeetingButton();
      // Join Meeting Button Before
      $join_meeting_button_before_default_option = $social_virtual_event_bbb_settings->get('join_meeting_button_before_default');
      // Join Meeting Button After
      $join_meeting_button_after_default_option = $social_virtual_event_bbb_settings->get('join_meeting_button_after_default');
      // Get config for the event    
      $config = $socialVirtualEventsCommon->getSocialVirtualEventBBBEntityConfig($entity->id());
   
      if ($config) {
        $recording_access_default = $config->getRecordingAccess();
        $join_meeting_button_visible_before_default = $config->getJoinButtonVisibleBefore();
        $join_meeting_button_visible_after_default = $config->getJoinButtonVisibleAfter();       
      }
      
  
      if (!isset($recording_access_allowed_options) || empty($recording_access_allowed_options)) {
        $recording_access_allowed = $default_recording_access_allowed_options;
      }
      else {
        $recording_access_allowed = array_intersect_key($default_recording_access_allowed_options, $recording_access_allowed_options);
      }

      // Attach new fieldset
      $form['social_virtual_event_bbb_config_entity'] = [
        '#type' => 'fieldset',
        '#title' => t('Virtual Event BBB extra settings'),      
        '#tree' => TRUE,
        '#description' => $on_translation_description,
        '#weight' => 5,
        '#states' => [
          'visible' => [
            ':input[name="virtual_events_sources[enable_virtual_session]"]' => ['checked' => TRUE],
          ]
        ],
        '#attributes' => [
          'class' => [
            'card'
          ]
        ]
      ];
      $form['social_virtual_event_bbb_config_entity']['recording_access'] = [
        '#type' => 'select',
        '#title' => t('Who can see the recordings?'),
        '#options' => $recording_access_allowed,
        '#default_value' => $recording_access_default ? $recording_access_default : $recording_access_allowed_default_option,
        '#disabled' => $on_translation_disabled,
      ];
      $form['social_virtual_event_bbb_config_entity']['join_button_visible_before'] = [
        '#type' => 'select',
        '#title' => t('Display join button before event start'),
        '#default_value' => $join_meeting_button_visible_before_default ? $join_meeting_button_visible_before_default : $join_meeting_button_before_default_option,
        '#options' => $join_meeting_button_options,
        '#disabled' => $on_translation_disabled,
      ];
      $form['social_virtual_event_bbb_config_entity']['join_button_visible_after'] = [
        '#type' => 'select',
        '#title' => t('Display join button after event closes'),
        '#default_value' => $join_meeting_button_visible_after_default ? $join_meeting_button_visible_after_default : $join_meeting_button_after_default_option,
        '#options' => $join_meeting_button_options,
        '#disabled' => $on_translation_disabled,
      ];

    } 

    // Submit handler for config
    //$form['actions']['submit']['#submit'][] = 'social_virtual_event_extra_form_submit';
       

    // We want to be able to delete the attached event
    // if any.
    if ($event && $is_original_language) {
      $url = Url::fromRoute('entity.virtual_events_event_entity.delete_form', array('virtual_events_event_entity' => $event->id()),['query' => \Drupal::destination()->getAsArray()]);
      $form['event_reset'] = array(
        '#type' => 'link',
        '#title' => t('Reset'),
        '#attributes' => array(
          'class' => array('btn', 'btn-primary'),
        ),
        '#url' => $url,
      );
    }  

    return $form;

  }

}
