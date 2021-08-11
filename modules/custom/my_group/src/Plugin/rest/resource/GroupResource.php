<?php


namespace Drupal\my_group\Plugin\rest\resource;

use Drupal\Component\Utility\Random;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides a ACE Content Resource
 *
 * @RestResource(
 *   id = "my_group_api_index",
 *   label = @Translation("MyGroupList"),
 *   uri_paths = {
 *     "create" = "/my_group_api/index"
 *   }
 * )
 */
class GroupResource extends ResourceBase {
  /**
   * A current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The group we will use to test methods on.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;
  protected $group_type;
  protected $randomGenerator;
  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase HtmlResource.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   *   A render instance.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    Request $request,
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $current_user
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->request = $request;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $current_user;
    $this->group = $this->groupEntity();
    $this->groupContent = $this->groupContentEntity();
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
      $container->get('logger.factory')->get('ace_content_log'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  protected function groupEntity() {
    $group = $this->entityTypeManager->getStorage('group');
    return $group;
  }

  protected function groupContentEntity() {
    $group = $this->entityTypeManager->getStorage('group_content');
    return $group;
  }

  protected function createAGroup(array $values = []) {
    $storage = $this->groupEntity();
    $group = $storage->create($values + [
            'type' => 'group2',
            'label' => 'aaac',
        ]);
    $group->enforceIsNew();
    $storage->save($group);
    return $group;
  }

  public function post($data) {
    if (empty($data['uid'])) {
      $message = $this->t('ID must be not empty');
      return new ResourceResponse($message, 500);
    }
//    $query = $this->groupEntity()->getQuery()
//        ->sort('id', 'asc')
//
//        ->range(1,2);
//    $entity_ids = $query->execute();
//
//    $nodes = $this->groupEntity()->loadMultiple($entity_ids);
//    dd($nodes);

//    $this->createAGroup(); //Create group.
//    $group=$this->groupEntity()->load(1);//Load group.
//    $group->label='abc';
//    $group->save(); //Update group.
//    $res=$this->groupContentEntity()->load($data['uid'])->delete();//Delete group.

//    $user=User::load(1);
//    $group = $this->entityTypeManager->getStorage('group')->load(8);
//    $group->addMember($user); //Add member.
    $group = $this->group->load(1);
    $nodes=$group->getContent('group_membership');
    dd($nodes);
    $response = ['message' => 'successfully!'];
    return new ResourceResponse($response, 200);
  }
}
