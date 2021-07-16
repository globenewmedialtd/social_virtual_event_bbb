<?php

namespace Drupal\social_virtual_event_bbb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupType;

/**
 * Class SocialVirtualEventBBBSettingsForm.
 */
class SocialVirtualEventBBBSettingsForm extends ConfigFormBase {

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

    $form['nodejs_support'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate NODEJS'),
      '#description' => $this->t('Nodejs capabilities detected!'),
      '#default_value' => FALSE
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
   // kint($form_state->getValue('recording_access_allowed'));
    //exit;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('social_virtual_event_bbb.settings');
    $config->set('recording_admin_only', $form_state->getValue('recording_admin_only'));
    $config->set('recording_access_default', $form_state->getValue('recording_access_default'));
    
    $recording_access_allowed_options = $form_state->getValue('recording_access_allowed');
    $recording_access_allowed = array_filter($recording_access_allowed_options);
    // If none selected enable all options
    if ($recording_access_allowed === 0) {
      $recording_access_allowed = $this->getRecordingAccessAllowedOptions();  
    }
    $config->set('recording_access_allowed', $recording_access_allowed);
    $config->set('bbb_statistic_refresh', $form_state->getValue('bbb_statistic_refresh'));
    $config->set('count_down_font_size', $form_state->getValue('count_down_font_size'));
    $config->set('count_down_date_time_format', $form_state->getValue('count_down_date_time_format'));
    $config->set('nodejs_support', $form_state->getValue('nodejs_support'));
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
      'recording_access_viewer' => 'View access all users',
      'recording_access_viewer_authenticated' => 'View access authenticated users',
      'recording_access_viewer_moderator' => 'View access Moderator',
      'recording_access_viewer_enrolled' => 'View Access enrolled users'
    ];
  }

}

