<?php

/**
 * @file
 * Contains \Drupal\comment_notify\Form\CommentNotifyUnsubscribe.
 */

namespace Drupal\comment_notify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class CommentNotifyUnsubscribe extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'comment_notify_unsubscribe';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form['comment_notify_unsubscribe'] = [];
    $form['comment_notify_unsubscribe']['email_to_unsubscribe'] = [
      '#type' => 'textfield',
      '#title' => t('Email to unsubscribe'),
    ];
    $form['comment_notify_unsubscribe']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Unsubscribe this e-mail'),
    ];
    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    module_load_include('inc', 'comment_notify', 'comment_notify');
    $email = trim($form_state->getValue(['email_to_unsubscribe']));
    $comments = comment_notify_unsubscribe_by_email($email);
    // Update the admin about the state of this comment notification subscription.
    if ($comments == 0) {
      drupal_set_message(t("There were no active comment notifications for that email."));
    }
    else {
      drupal_set_message(\Drupal::translation()->formatPlural($comments, "Email unsubscribed from 1 comment notification.", "Email unsubscribed from @count comment notifications."));
    }
  }

}
