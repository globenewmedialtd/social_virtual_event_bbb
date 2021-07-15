<?php

namespace Drupal\social_virtual_event_bbb\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\node\Node;
use Drupal\social_group\SocialGroupHelperService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Entity\EntityTypeManagerInterface;


/**
 * Provides a 'SocialVirtualEventBBBStatisticsBlock' block.
 *
 * @Block(
 *  id = "social_virtual_event_bbb_statistics_block",
 *  admin_label = @Translation("Social Virtual Event BBB Statistics Block"),
 * )
 */
class SocialVirtualEventBBBStatisticsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Group helper service.
   *
   * @var \Drupal\social_group\SocialGroupHelperService
   */
  protected $groupHelperService;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;  

  /**
   * Social Virtual Event BBB Statistic Block constructor.
   *
   * @param array $configuration
   *   The given configuration.
   * @param string $plugin_id
   *   The given plugin id.
   * @param mixed $plugin_definition
   *   The given plugin definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Drupal\social_group\SocialGroupHelperService $groupHelperService
   *   The group helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.   * 
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $routeMatch, SocialGroupHelperService $groupHelperService, EntityTypeManagerInterface $entityTypeManager  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
    $this->groupHelperService = $groupHelperService;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('social_group.helper_service'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    
    $node = \Drupal::routeMatch()->getParameter('node');

    // Checking for a node object
    if (!is_object($node) && !is_null($node)) {
      $node = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($node);        
    }
    // Check for an node type of iteration
    if ($node instanceof NodeInterface && $node->getType() === 'event') {
      $event = $node;

    }
    if ($event instanceOf NodeInterface) {    
      // Load the user for Role check
      $user = User::load($account->id());
      // Get the group
      $gid_from_entity = $this->groupHelperService->getGroupFromEntity([
        'target_type' => 'node',
        'target_id' => $event->id(),
      ]);  
      if ($gid_from_entity !== NULL) {
        /** @var \Drupal\group\Entity\GroupInterface $group */
        $group = $this->entityTypeManager
          ->getStorage('group')
          ->load($gid_from_entity);
      }

    }

    return AccessResult::allowed();

    //return AccessResult::neutral();

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = parent::getCacheContexts();    
    return $cache_contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    return $cache_tags;
  }

  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $buttons = [];

    // Get current node so we can build correct links.
    $node = \Drupal::routeMatch()->getParameter('node');



    return $build;

  }

}
