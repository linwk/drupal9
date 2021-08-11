<?php

namespace Drupal\my_group\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManager;

/**
 * Class MyGroupService for my_group.
 *
 * @package Drupal\my_group
 */
class MyGroupService {
  use StringTranslationTrait;
  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Config\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Language Manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $group;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $groupType;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $groupContent;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $user;

  /**
   * WebhookService constructor.
   */
  public function __construct(
      LoggerChannelFactoryInterface $logger_factory,
      EntityTypeManagerInterface $entity_type_manager,
      ConfigFactoryInterface $config_factory,
      LanguageManager $language_manager) {
    $this->loggerFactory = $logger_factory;
    $database_name = 'default';
    $this->database = Database::getConnection('default', $database_name);
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->group = $this->groupEntity();
    $this->groupType = $this->groupTypeEntity();
    $this->groupContent = $this->groupContentEntity();
    $this->user = $this->userEntity();
  }

  /**
   * Group entity.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function groupEntity() {
    return $this->entityTypeManager->getStorage('group');
  }

  /**
   * GroupType entity.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function groupTypeEntity(){
    return $this->entityTypeManager->getStorage('group_type');
  }

  /**
   * GroupContent entity.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function groupContentEntity() {
    return $this->entityTypeManager->getStorage('group_content');
  }

  /**
   * @return \Drupal\Core\Entity\EntityStorageInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function userEntity() {
    return $this->entityTypeManager->getStorage('user');
  }

  /**
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupList($page=1,$limit=10){
    $query = $this->group->getQuery()
        ->sort('id', 'asc')
        ->range(($page-1)*$limit,$limit)
        ->execute();
    $groups = $this->group->loadMultiple($query);
    return $groups;
  }

  public function groupDataFac($groups){
    $res=[];
    if(!empty($groups)){
      foreach ($groups as $group){
        $row['id']=$group->id();
        $row['name']=$group->label->value;
        $row['type']=$group->getGroupType()->label();;
        $row['type'] = $group->getGroupType()->label();
        $row['status'] = $group->isPublished() ? $this->t('Published') : $this->t('Unpublished');
        $row['uid'] = $group->getOwner()->label();
        $res[]=$row;
      }
    }
    return $res;
  }

  public function loadGroupType($entity_id){
    $entity=$this->groupType->load($entity_id);
    return $entity;
  }

  public function createGroup($groupType,$label){
    $storage = $this->group;
    $group = $storage->create([
            'type' => $groupType,
            'label' => $label,
        ]);
    $group->enforceIsNew();
    $storage->save($group);
    return $group;
  }

  public function editGroup($id,$groupType,$label,$status=1){
    $group=$this->group->load($id);
    $group->type=$groupType;
    $group->label=$label;
//    $group->path='/aaaa';
    $group->status=$status;
    $group->save();
    return $group;
  }

  public function deleteGroup($id){
    $group=$this->group->load($id);
    $group->delete();
  }

  public function userList(){
    $users=$this->user->loadMultiple();
    return $users;
  }

  public function userDataFac($users){
    $res=[];
    if(!empty($users)){
      foreach ($users as $user){
        $row['id']=$user->id();
        $row['name']=$user->name->value;
        $res[]=$row;
      }
    }
    return $res;
  }
}
