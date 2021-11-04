<?php

namespace Drupal\social_virtual_event_bbb\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\virtual_event_bbb\VirtualEventBBB;
use BigBlueButton\Parameters\DeleteRecordingsParameters;
use Drupal\social_virtual_event_bbb\Entity\VirtualEventBBBConfigEntity;
use Drupal\social_virtual_event_bbb\Entity\VirtualEventBBBConfigEntityInterface;
use Drupal\node\NodeInterface;
use BigBlueButton\Parameters\HooksCreateParameters;
use Drupal\Core\Url;

class SocialVirtualEventBBBDeleteRecordingForm extends FormBase {

  /**
   * Get the recording ID of a recording and sends API Call to delete it
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {

    $form_id = 'social_virtual_event_bbb_delete_recording';
    
    static $count = 0;
    $count++;

    return $form_id . '_' . $count;

  }

  public function buildForm(array $form, FormStateInterface $form_state, $recording_id = NULL, $entity_type = NULL, $entity_id = NULL) {

   
   
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['recording_id'] = [
      '#type' => 'hidden',
      '#value' => $recording_id
    ];

    $form['entity_type'] = [
      '#type' => 'hidden',
      '#value' => $entity_type
    ];

    $form['entity_id'] = [
      '#type' => 'hidden',
      '#value' => $entity_id
    ];    


    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#attributes' => array('onclick' => 'if(!confirm("Are you sure you want to delete that recording?")){return false;}')
    );

    return $form;

  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    //kint('form values: ', $form_state->getValues());
    //exit;
    $recording_id = $form_state->getValue('recording_id');
    $entity_type = $form_state->getValue('entity_type');
    $entity_id = $form_state->getValue('entity_id');

    if (isset($recording_id) &&
        isset($entity_type) &&
        isset($entity_id)) {

      $BBBKeyPluginManager = \Drupal::service('plugin.manager.bbbkey_plugin');    
      $virtualEventsCommon = \Drupal::service('virtual_events.common');    

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
        $deleteRecordingsParameters = new DeleteRecordingsParameters($recording_id,$source_data['settings']['moderatorPW']);
        try {        
          $response = $bbb->deleteRecordings($deleteRecordingsParameters);          
        }
        catch (\RuntimeException $exception) {
          watchdog_exception('social_virtual_event_bbb', $exception, $exception->getMessage());
          drupal_set_message(t("Couldn't get meeting info! please contact system administrator."), 'error');
        }



      }
    }

    /*
    
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



    $messenger = \Drupal::messenger();
    $messenger->addMessage('Recording was successfully deleted!');
    */



  } 








}