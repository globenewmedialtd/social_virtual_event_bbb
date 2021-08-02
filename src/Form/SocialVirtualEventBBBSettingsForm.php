<?php

namespace Drupal\social_virtual_event_bbb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupType;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class SocialVirtualEventBBBSettingsForm.
 */
class SocialVirtualEventBBBSettingsForm extends ConfigFormBase {

  /**
   * ModuleHandler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler; 

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_virtual_event_bbb_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('social_virtual_event_bbb.settings');

    $socialVirtualEventsCommon = \Drupal::service('social_virtual_event_bbb.common');
    $join_meeting_button_options = $socialVirtualEventsCommon->getOptionsForJoinMeetingButton();
    $nodejs = FALSE;
    if ($this->moduleHandler->moduleExists('nodejs')) {
      $nodejs = TRUE;
    }

    $recording_access_allowed_options = $this->getRecordingAccessAllowedOptions();

    $form['recordings'] = [
      '#type' => 'details',
      '#title' => $this->t('Recording access'),
      '#open' => TRUE,
      '#tree' => FALSE
    ];
   
    $form['recordings']['recording_admin_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Recordings for admins only'),
      '#description' => t('Grant access to recordings for adminstrators only'),
      '#default_value' => $config->get('recording_admin_only') ? $config->get('recording_admin_only') : FALSE
    ];

    $form['recordings']['recording_access_default'] = [
      '#type' => 'select',
      '#title' => $this->t('Recording access default'),
      '#options' => $recording_access_allowed_options,
      '#default_value' => $config->get('recording_access_default') ? $config->get('recording_access_default') : 'recording_access_viewer',
    ];

    $form['recordings']['recording_access_allowed'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Recording access allowed'),
      '#options' => $recording_access_allowed_options,
      '#description' => t('Define who can access recordings. If you do not enable any option, all will be allowed inside the virtual event setting.'),
      '#default_value' => $config->get('recording_access_allowed') ? $config->get('recording_access_allowed') : 'recording_access_viewer',
    ];

    $form['bbb_event_statistic'] = [
      '#type' => 'details',
      '#title' => $this->t('BBB statistic'),
      '#tree' => FALSE
    ];

    $form['bbb_event_statistic']['bbb_statistic_refresh'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Refresh rate for Virtual Event BBB statistics block'),
      '#default_value' => $config->get('bbb_statistic_refresh') ? $config->get('bbb_statistic_refresh') : 10
    ];

    $form['joinbutton'] = [
      '#type' => 'details',
      '#title' => $this->t('Join meeting button'),
      '#tree' => FALSE
    ];

    $form['joinbutton']['join_meeting_button_before_default'] = [
      '#type' => 'select',
      '#options' => $join_meeting_button_options,
      '#default_value' => $config->get('join_meeting_button_before_default') ? $config->get('join_meeting_button_before_default') : 'show_always_open',
      '#title' => $this->t('Join meeting button before default'),
    ];

    $form['joinbutton']['join_meeting_button_after_default'] = [
      '#type' => 'select',
      '#options' => $join_meeting_button_options,
      '#default_value' => $config->get('join_meeting_button_after_default') ? $config->get('join_meeting_button_after_default') : 'show_always_open',
      '#title' => $this->t('Join meeting button after default'),
    ];    

    $form['joinbutton']['count_down_font_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Count down display font size'),      
      '#description' => $this->t('Enter the number for font size (default is 28).'),
      '#default_value' => $config->get('count_down_font_size') ? $config->get('count_down_font_size') : 28
    ];

    $form['joinbutton']['count_down_date_time_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Count down date time format'),     
      '#description' => $this->t('Enter a valid date time format (default is H:i:s).'),
      '#default_value' => $config->get('count_down_date_time_format') ? $config->get('count_down_date_time_format') : 'H:i:s'
    ];

    if ($nodejs) {

      $form['nodejs_support'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Activate NODEJS'),
        '#description' => $this->t('Nodejs module detected. Please configure nodejs properly and activate nodejs if you are sure it is up and running.'),
        '#default_value' => $config->get('nodejs_support') ? $config->get('nodejs_support') : FALSE
      ];

    }

    $form['add_bbb_server_callback'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add BBB Server Callback when creating meeting'),
      '#default_value' => $config->get('add_bbb_server_callback') ? $config->get('add_bbb_server_callback') : FALSE
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#button_level' => 'raised',
      '#value' => $this->t('Save configuration'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
   // kint($form_state->getValue('recording_access_default'));
    //kint($form_state->getValue('recording_access_allowed'));
    //exit;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $social_virtual_event_bbb_common = \Drupal::service('social_virtual_event_bbb.common');
    $recording_access_available_allowed_options = $social_virtual_event_bbb_common->getAllAllowedRecordingAccessOptions();
    $recording_access_allowed_options = $form_state->getValue('recording_access_allowed');
    $recording_access_allowed = array_filter($recording_access_allowed_options);
    $config = $this->configFactory->getEditable('social_virtual_event_bbb.settings');
    $config->set('recording_admin_only', $form_state->getValue('recording_admin_only'));
    $config->set('recording_access_default', $form_state->getValue('recording_access_default'));
    $config->set('recording_access_allowed', $recording_access_allowed);
    $config->set('bbb_statistic_refresh', $form_state->getValue('bbb_statistic_refresh'));
    $config->set('count_down_font_size', $form_state->getValue('count_down_font_size'));
    $config->set('count_down_date_time_format', $form_state->getValue('count_down_date_time_format'));
    $config->set('add_bbb_server_callback', $form_state->getValue('add_bbb_server_callback'));
    $nodejs_support = $form_state->getValue('nodejs_support'); 
    
    if(isset($nodejs_support)) {
      $config->set('nodejs_support', $nodejs_support);
    }
    else {
      $config->set('nodejs_support', FALSE);
    }
    $config->set('join_meeting_button_before_default', $form_state->getValue('join_meeting_button_before_default'));
    $config->set('join_meeting_button_after_default', $form_state->getValue('join_meeting_button_after_default'));
    $config->save();
  }

  /**
   * Gets the configuration names that will be editable.
   */
  protected function getEditableConfigNames() {
    // @todo Implement getEditableConfigNames() method.
  }

  /**
   * Gets the allowed options for recording access.
   */
  protected function getRecordingAccessAllowedOptions() {
    return [
      'recording_access_viewer' => 'All users (including anonymous users)',
      'recording_access_viewer_authenticated' => 'All authenticated users',
      'recording_access_viewer_moderator' => 'Only event organisers',
      'recording_access_viewer_enrolled' => 'Organisers & participants'
    ];
  }

}

