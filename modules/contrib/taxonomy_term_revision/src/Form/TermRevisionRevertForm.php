<?php

namespace Drupal\taxonomy_term_revision\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Datetime\Time;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Datetime\DateFormatter;

/**
 * Defines a confirmation form to confirm reverting to a term revision by id.
 */
class TermRevisionRevertForm extends ConfirmFormBase {

  /**
   * The revision id.
   *
   * @var string
   */
  protected $id;

  /**
   * The entity id.
   *
   * @var string
   */
  protected $entityId;

  /**
   * The database connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The logger instance.
   *
   * @var Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The time details.
   *
   * @var Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * The entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * User definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructor.
   */
  public function __construct(Connection $database, LoggerChannelFactoryInterface $loggerFactory, Time $time, EntityTypeManager $entityTypeManager, AccountProxy $current_user, DateFormatter $date_formatter) {
    $this->database = $database;
    $this->loggerFactory = $loggerFactory;
    $this->time = $time;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $current_user;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('logger.factory'),
      $container->get('datetime.time'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "term_revision_revert_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $taxonomy_term = NULL, $id = NULL) {
    $this->id = $id;
    $this->entityId = $taxonomy_term;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $term = $this->entityTypeManager->getStorage('taxonomy_term')->loadRevision($this->id);
    if (!empty($term)) {
      $original_revision_timestamp = $term->getRevisionCreationTime();
      $term->setRevisionLogMessage("Copy of the revision from " . $this->dateFormatter->format($original_revision_timestamp));
      $term->setRevisionUserId($this->currentUser->id());
      $term->setRevisionCreationTime($this->time->getRequestTime());
      $term->setChangedTime($this->time->getRequestTime());
      $term->setNewRevision();
      $term->isDefaultRevision(TRUE);

      $term->save();

      $this->loggerFactory->get('term_revision')->info('Term reverted tid %tid revision_id %trid', ['%tid' => $this->entityId, '%trid' => $this->id]);
      $this->messenger()->addStatus($this->t('This term has been reverted'));
    }
    else {
      $this->messenger()->addError($this->t('Error! Revision Id does not exist for given Term Id'));
    }

    // Redirect to Revision Page of the term.
    $response = new RedirectResponse(Url::fromRoute('taxonomy_term_revision.all', ['taxonomy_term' => $this->entityId])->toString());
    $response->send();
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('taxonomy_term_revision.all', ['taxonomy_term' => $this->entityId]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to revert to this revision?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('You can always revert to current revision.');
  }

}
