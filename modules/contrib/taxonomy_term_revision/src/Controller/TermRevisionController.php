<?php

namespace Drupal\taxonomy_term_revision\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxy;
use Drupal\user\UserStorageInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Component\Utility\Xss;

/**
 * TermRevisionController.
 */
class TermRevisionController extends ControllerBase {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The user storage details connection.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The entity term manager.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termManager;

  /**
   * The entity repository.
   *
   * @var Drupal\Core\Entity\EntityRepository
   */
  protected $entityRepository;

  /**
   * The term view builder.
   *
   * @var Drupal\Core\Entity\EntityViewBuilder
   */
  protected $termViewBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, AccountProxy $current_user, UserStorageInterface $user_storage, TermStorageInterface $term_storage, EntityRepository $entity_repository, EntityViewBuilder $term_view_builder) {
    $this->database = $database;
    $this->currentUser = $current_user;
    $this->userStorage = $user_storage;
    $this->termManager = $term_storage;
    $this->entityRepository = $entity_repository;
    $this->termViewBuilder = $term_view_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('entity_type.manager')->getStorage('taxonomy_term'),
      $container->get('entity.repository'),
      $container->get('entity_type.manager')->getViewBuilder('taxonomy_term')
    );
  }

  /**
   * Getting all revisions.
   *
   * {@inheritdoc}
   */
  public function getRevisions(RouteMatchInterface $route_match) {
    $taxonomy_term = $route_match->getParameter('taxonomy_term');

    $schema = $this->database->schema();
    if ($schema->tableExists('taxonomy_term_revision')) {
      // Query for fetching term revision data.
      $results = $this->database->select('taxonomy_term_revision', 'tr')
        ->fields('tr', [
          'revision_id',
          'revision_created',
          'revision_default',
          'revision_user',
          'revision_log_message',
        ])
        ->condition('tr.tid', intval($taxonomy_term))
        ->orderBy('revision_id', 'DESC')
        ->execute()
        ->fetchAll();

      // Header for revision table.
      $header = [
        $this->t('CHANGED'),
        $this->t('USER'),
        $this->t('OPERATIONS'),
        $this->t('LOG MESSAGE'),
      ];

      // Data to be rendered on revisions page of a term.
      $data = [];
      if (!empty($results)) {
        foreach ($results as $index => $result) {
          $revision_user = $this->t('Anonymous');
          if (isset($result->revision_user) && $result->revision_user != -1) {
            $user = $this->userStorage->load($result->revision_user);
            $user_name = empty($user) ? $revision_user : $user->getUsername();
            $revision_user = Link::fromTextAndUrl($user_name, Url::fromUri('internal:/user/' . $result->revision_user));
          }

          // Check if Current revision.
          $term = $this->termManager->loadRevision($result->revision_id);
          if ($term->isDefaultRevision()) {
            $data[$index] = [
              Link::fromTextAndUrl(date('m/d/Y H:i:s', $result->revision_created), Url::fromUri('internal:/taxonomy/term/' . $taxonomy_term)),
              $revision_user,
              $this->t('Current Revision'),
              $result->revision_log_message,
            ];
          }
          else {
            // Checking current user permissions.
            $account = $this->currentUser;
            $revert_permission = $account->hasPermission("revert term revision");
            $delete_permission = $account->hasPermission("delete term revision");

            $revert_link = $revert_permission ? Link::fromTextAndUrl($this->t('Revert'), Url::fromRoute('taxonomy_term_revision.revert', ['taxonomy_term' => $taxonomy_term, 'id' => $result->revision_id]))->toString() : '';
            $delete_link = $delete_permission ? Link::fromTextAndUrl($this->t('Delete'), Url::fromRoute('taxonomy_term_revision.delete', ['taxonomy_term' => $taxonomy_term, 'id' => $result->revision_id]))->toString() : '';
            $data[$index] = [
              Link::fromTextAndUrl(date('m/d/Y H:i:s', $result->revision_created), Url::fromRoute('taxonomy_term_revision.view', ['taxonomy_term' => $taxonomy_term, 'revision_id' => $result->revision_id])),
              $revision_user,
              $this->t('@revert   @delete', ['@revert' => $revert_link, '@delete' => $delete_link]),
              $result->revision_log_message,
            ];
          }
        }
      }
    }
    return ['#type' => 'table', '#header' => $header, '#rows' => $data];
  }

  /**
   * Getting revision data.
   *
   * @param Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The taxonomy term.
   *
   * @return array
   *   The term page.
   */
  public function revisionShow(RouteMatchInterface $route_match) {
    $revision_id = $route_match->getParameter('revision_id');
    $term = $this->termManager->loadRevision($revision_id);
    $term = $this->entityRepository->getTranslationFromContext($term);
    $page = $this->termViewBuilder->view($term);
    unset($page['#cache']);
    return $page;
  }

  /**
   * Route title callback.
   *
   * @param Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The taxonomy term.
   *
   * @return array
   *   The term label as a render array.
   */
  public function revisionPageTitle(RouteMatchInterface $route_match) {
    $revision_id = $route_match->getParameter('revision_id');
    $taxonomy_term_revision = $this->termManager->loadRevision($revision_id);
    return ['#markup' => $taxonomy_term_revision->getName(), '#allowed_tags' => Xss::getHtmlTagList()];
  }

}
