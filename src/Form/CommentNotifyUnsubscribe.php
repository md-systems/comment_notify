<?php

/**
 * @file
 * Contains \Drupal\comment_notify\Form\CommentNotifyUnsubscribe.
 */

namespace Drupal\comment_notify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Render\Element;

/**
 * Unsubscribe form for Comment Notify.
 */
class CommentNotifyUnsubscribe extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'comment_notify_unsubscribe';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['comment_notify_unsubscribe'] = [];

    $form['comment_notify_unsubscribe']['email'] = [
      '#type' => 'textfield',
      '#title' => t('Email to unsubscribe'),
      '#description' => $this->t('All comment notification requests associated with this email will be revoked.'),
      '#required' => TRUE,
    ];
    $form['comment_notify_unsubscribe']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Unsubscribe this e-mail'),
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    module_load_include('inc', 'comment_notify', 'comment_notify');
    $email = trim($form_state->getValue(['email']));
    $comments = comment_notify_unsubscribe_by_email($email);
    // Update the admin about the state of this comment notification subscription.
    if ($comments == 0) {
      drupal_set_message(t("There were no active comment notifications for that email."));
    }
    else {
      drupal_set_message($this->formatPlural($comments, "Email unsubscribed from 1 comment notification.", "Email unsubscribed from @count comment notifications."));
    }
  }

}
