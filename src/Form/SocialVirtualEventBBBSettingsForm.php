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

    
    $form['recording_admin_only'] = [
      '#type' => 'checkbox',
      '#title' => t('Recordings for admins only'),
    ];

    $form['bbb_statistic_refresh'] = [
      '#type' => 'textfield',
      '#title' => t('Refresh rate for Virtual Event BBB statistics block'),
      '#default_value' => 10,
    ];

    $form['nodejs_support'] = [
      '#type' => 'checkbox',
      '#title' => t('Activate NODEJS'),
      '#description' => t('Nodejs capabilities detected!')
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
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('social_virtual_event_bbb.settings');
    //$config->set('social_moodle_group_types', $form_state->getValue('social_moodle_group_types'));
    //$config->save();
  }

  /**
   * Gets the configuration names that will be editable.
   */
  protected function getEditableConfigNames() {
    // @todo Implement getEditableConfigNames() method.
  }

}

