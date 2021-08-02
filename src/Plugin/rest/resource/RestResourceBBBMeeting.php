<?php

namespace Drupal\social_virtual_event_bbb\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Ajax\ReplaceCommand;


/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "rest_resource_bbb_meeting",
 *   label = @Translation("Rest resource BBB Meeting"),
 *   serialization_class = "",
 *   uri_paths = {
 *     "canonical" = "/api/bbb-meeting-webhook/{node}",
 *     "create" = "/api/bbb-meeting-webhook/{node}"
 *   }
 * )
 */
class RestResourceBBBMeeting extends ResourceBase {
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;  

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    Request $current_request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->currentRequest = $current_request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('ccms_rest'),
      $container->get('current_user'),
      $container->get('request_stack')->getCurrentRequest()
    );
  }

  /**
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param $node
   * @param $data
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */
  public function post($data) {


    
    //\Drupal::logger('social_virtual_event_bbb')->notice('<pre><code>' . print_r($node, TRUE) . '</code></pre>');

    $response_status['status'] = false;

    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }    
    
    // Read the parameter
    $node = \Drupal::routeMatch()->getParameter('node');
    
    // Checking for a node object
    if (!is_object($node) && !is_null($node)) {
      $node = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($node);        
    }

    // Check for an node type of event
    if ($node instanceof NodeInterface && $node->getType() === 'event') {      
      $virtualEventsCommon = \Drupal::service('virtual_events.common');
      $entity_type = $node->getEntityTypeId();
      $entity_id = $node->id();
      $event = $virtualEventsCommon->getEventByRefernce($entity_type, $entity_id);
      if ($event && $data && $data['data']['type'] === 'event') {
        $response_status['status'] = true;
        \Drupal::logger('social_virtual_event_bbb')->notice('<pre><code>' . print_r($data, TRUE) . '</code></pre>');
        
        // Get BBB API Call and send data to clients
        $socialVirtualEventBBBCommon = \Drupal::service('social_virtual_event_bbb.common');
        $bbb_meeting_statistic = $socialVirtualEventBBBCommon->nodejsGetBBBStatistic($node);
        $nodejs = $socialVirtualEventBBBCommon->isNodejsActive();
        $response_status['stats'] = $bbb_meeting_statistic;

        if($nodejs) {          
          if($bbb_meeting_statistic) {
            \Drupal::logger('social_virtual_event_bbb')->notice('<pre><code>' . print_r($bbb_meeting_statistic, TRUE) . '</code></pre>');
            $themed_data = [
              '#theme' => 'social_virtual_event_bbb_statistic',
              '#statistic' => $bbb_meeting_statistic,
              '#prefix' => '<div id="bbb-meeting-info" class="card__block">',
              '#suffix' => '</div>'
            ];
          }
          else {
            $themed_data = [
              '#theme' => 'social_virtual_event_bbb_statistic',
              '#statistic' => $bbb_meeting_statistic,
              '#prefix' => '<div id="bbb-meeting-info" class="card__block visually-hidden">',
              '#suffix' => '</div>'
            ];
          }

          $replace_command = new ReplaceCommand('#bbb-meeting-info', $themed_data);
          $commands[] = $replace_command->render();
  
          $nodejs_message = (object) [
            'channel' => $node->id(),
            'commands' => $commands,
            'callback' => 'nodejsBBBEventStatistic',
          ];
          nodejs_send_content_channel_message($nodejs_message);
        }
      }
    }    

    return new ResourceResponse($response_status);

	

/*
    $node = Node::create(
      array(
        'type' => $node_type,
        'title' => $data->title->value,
        'body' => [
          'summary' => '',
          'value' => $data->body->value,
          'format' => 'full_html',
        ],
      )
    );
    $node->save();
    return new ResourceResponse($node);
	*/

  }

}
