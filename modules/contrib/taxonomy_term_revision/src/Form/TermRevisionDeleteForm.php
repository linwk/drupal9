<?php

namespace Drupal\taxonomy_term_revision\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Defines a confirmation form to confirm deletion of term revision by id.
 */
class TermRevisionDeleteForm extends ConfirmFormBase {

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
   * Constructor.
   */
  public function __construct(Connection $database, LoggerChannelFactoryInterface $loggerFactory) {
    $this->database = $database;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
            $container->get('database'), $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "term_revision_delete_form";
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

    \Drupal::entityTypeManager()->getStorage('taxonomy_term')->deleteRevision($this->id);
    $result = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadRevision($this->id);

    if ($result == NULL) {
      $this->loggerFactory->get('term_revision')->info('Term revision deleted tid %tid revision_id %trid', ['%tid' => $this->entityId, '%trid' => $this->id]);
      $this->messenger()->addStatus($this->t('Revision has been deleted'));
    }
    else {
      $this->messenger()->addError($this->t('Error! Revision Id does not exist for given Term Id'));
    }
    // Redirect to Revision page of the term.
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
    return $this->t('Do you want to delete this revision?');
  }

}
