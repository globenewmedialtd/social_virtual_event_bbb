<?php

namespace Drupal\social_virtual_event_bbb\Plugin\VirtualEvent\SourcePlugin;

use Drupal\virtual_event_bbb\Plugin\VirtualEvent\SourcePlugin\VirtualEventBBBSource;
use Drupal\virtual_events\Plugin\VirtualEventSourcePluginBase;
use Drupal\virtual_events\Entity\VirtualEventsEventEntity;
use Drupal\Core\Form\FormStateInterface;
use Drupal\virtual_event_bbb\VirtualEventBBB;
use BigBlueButton\Parameters\GetMeetingInfoParameters;
use BigBlueButton\Parameters\CreateMeetingParameters;
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
      $createMeetingParams = new CreateMeetingParameters($event->id(), $event->label());
      if ($source_data["settings"]["welcome"]) {
        $createMeetingParams->setWelcomeMessage($source_data["settings"]["welcome"]);
      }

      $logoPath = file_create_url(theme_get_setting('logo.url'));
      $createMeetingParams->setLogoutUrl($source_data["settings"]["logoutURL"] ? $source_data["settings"]["logoutURL"] : $logout_url);
      $createMeetingParams->setDuration(0);
      $createMeetingParams->setRecord(TRUE);
      $createMeetingParams->setAllowStartStopRecording(TRUE);
      $createMeetingParams->setLogo($logoPath);

      if ($source_data["settings"]["record"]) {
        $createMeetingParams->setAutoStartRecording(TRUE);
      }

      if($source_data["settings"]["mute_on_start"]) {
        $createMeetingParams->setMuteOnStart(TRUE);
      }

      try {
        $response = $bbb->createMeeting($createMeetingParams);
        if ($response->getReturnCode() == 'FAILED') {
          drupal_set_message(t("Couldn't create room! please contact system administrator."), 'error');
        }
        else {
          $typeId = $this->getType();
          $source_data["settings"]["attendeePW"] = $response->getAttendeePassword();
          $source_data["settings"]["moderatorPW"] = $response->getModeratorPassword();
          $event->setSourceData($this->pluginId, $source_data);
          $event->save();
          return $event;
        }
      }catch (\RuntimeException $exception) {
        watchdog_exception('virtual_event_bbb', $exception, $exception->getMessage());
        drupal_set_message(t("Couldn't create room! please contact system administrator."), 'error');
      }catch (Exception $exception) {
        watchdog_exception('virtual_event_bbb', $exception, $exception->getMessage());
        drupal_set_message(t("Couldn't create room! please contact system administrator."), 'error');
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(FormStateInterface $form_state, ?array $pluginConfigValues) {
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
    $form = [];
    $settings = [];
    if (isset($source_data["settings"])) {
      $settings = $source_data["settings"];
    }

    $recording_access = array_filter($settings["recording_access"]);

    $social_virtual_event_bbb = \Drupal::config('social_virtual_event_bbb.settings');
    $recording_access_allowed_options = $social_virtual_event_bbb->get('recording_access_allowed');
    $recording_access_allowed_default_option = $social_virtual_event_bbb->get('recording_access_default');

    if (!isset($recording_access_allowed_options) || empty($recording_access_allowed_options)) {
      $social_virtual_event_bbb_common = \Drupal::service('social_virtual_event_bbb.common');
      $recording_access_allowed_options = $social_virtual_event_bbb_common->getAllAllowedRecordingAccessOptions();
    }

    kint($recording_access_allowed_default_option);

    $form['welcome'] = [
      '#title' => t('Welcome message'),
      '#type' => 'textfield',
      '#default_value' => $settings["welcome"] ? $settings["welcome"] : "",
      '#maxlength' => 255,
      '#description' => t('A welcome message that gets displayed on the chat window when the participant joins. You can include keywords (%%CONFNAME%%, %%DIALNUM%%, %%CONFNUM%%) which will be substituted automatically.'),
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
      '#default_value' => $settings['mute_on_start'] ? $settings['mute_on_start'] : '',
      '#disabled' => $event !== NULL, 
    ];
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
    $form['recording_access'] = [
      '#type' => 'radios',
      '#title' => t('Define access level for recordings'),
      '#options' => $recording_access_allowed_options,
      '#default_value' => $recording_access ? $recording_access : $recording_access_allowed_default_option,
    ];
    $form['join_button_visible_before'] = [
      '#type' => 'select',
      '#title' => t('Display join button before event start'),
      '#default_value' => $settings["join_button_visible_before"] ? $settings["join_button_visible_before"] : "show_always_open",
      '#options' => [
        'show_always_open' => t('Show always open'),
        '15' => t('15 minutes'),
        '30' => t('30 minutes'),
        '45' => t('45 minutes'),
        '60' => t('60 minutes'),
        '90' => t('90 minutes'),
      ],
      //'#disabled' => $event !== NULL,
    ];
    $form['join_button_visible_after'] = [
      '#type' => 'select',
      '#title' => t('Display join button after event closes'),
      '#default_value' => $settings['join_button_visible_after'] ? $settings["join_button_visible_after"] : "show_always_open",
      '#options' => [
        'show_always_open' => t('Show always open'),
        '15' => t('15 minutes'),
        '30' => t('30 minutes'),
        '45' => t('45 minutes'),
        '60' => t('60 minutes'),
        '90' => t('90 minutes'),
      ],
      //'#disabled' => $event !== NULL,
    ];

    // We want to be able to delete the attached event
    // if any.
    if ($event) {
      $url = Url::fromRoute('entity.virtual_events_event_entity.delete_form', array('virtual_events_event_entity' => $event->id()),['query' => \Drupal::destination()->getAsArray()]);
      $form['event_reset'] = array(
        '#type' => 'link',
        '#title' => t('Reset'),
        '#attributes' => array(
          'class' => array('button', 'button--danger'),
        ),
        '#url' => $url,
      );
    }  

    return $form;

  }

}
