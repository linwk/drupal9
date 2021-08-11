<?php
namespace Drupal\my_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\my_group\Service\MyGroupService;
use Laminas\Diactoros\Response\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

Class MyGroupController extends ControllerBase{
  use StringTranslationTrait;
  /**
   * @var MyGroupService
   */
  protected $myGroupService;

  /**
   * Contract function.
   */
  public function __construct(MyGroupService $myGroupService) {
    $this->myGroupService = $myGroupService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('my_group.service')
    );
  }

  /**
   * @param array $data
   * @param string $message
   * @return JsonResponse
   */
  public function success($data=[],$message='Success.'){
    return new JsonResponse(['message'=>$this->t($message),'data'=>$data]);
  }

  /**
   * @param int $code
   * @param string $message
   * @return JsonResponse
   */
  public function error($code=201,$message='Error.'){
    return new JsonResponse(['message'=>$this->t($message)],$code);
  }

  /**
   * Group list.
   *
   * @param Request $request
   * @return JsonResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function index(Request $request){
    $page=$request->get('page',1);
    $limit=$request->get('limit',10);
    $groups=$this->myGroupService->getGroupList($page,$limit);
    if(empty($groups)) return $this->error(201,'Empty data.');
    $data=$this->myGroupService->groupDataFac($groups);
    return $this->success($data);
  }

  /**
   * Function for create a group.
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function createGroup(Request $request){
    $type=$request->get('type');
    $label=$request->get('label');
    $groupTypeEntity=$this->myGroupService->loadGroupType($type);
    if(empty($groupTypeEntity)) return $this->error(201,'Group type not exist.');
    $group=$this->myGroupService->createGroup($type,$label);
    return $this->success($group);
  }

  /**
   * Function for edit group.
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function editGroup($id,Request $request){
    $type=$request->get('type');
    $label=$request->get('label');
    $groupTypeEntity=$this->myGroupService->loadGroupType($type);
    if(empty($groupTypeEntity)) return $this->error(201,'Group type not exist.');
    $group=$this->myGroupService->editGroup($id,$type,$label);
    return $this->success($group);
  }

  /**
   * Function for delete group.
   *
   * @param $id
   * @return JsonResponse
   */
  public function deleteGroup($id){
    $group=$this->myGroupService->deleteGroup($id);
    return $this->success($group);
  }

  /**
   * @return JsonResponse
   */
  public function userList(){
    $users=$this->myGroupService->userList();
    $data=$this->myGroupService->userDataFac($users);
    return $this->success($data);
  }
}