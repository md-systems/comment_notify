<?php

/**
 * @file
 * Contains \Drupal\comment_notify\Form\CommentNotifySettings.
 */

namespace Drupal\comment_notify\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * Settings form for the Comment Notify module.
 */
class CommentNotifySettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'comment_notify_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['comment_notify.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('comment_notify.settings');

    // Only perform comment_notify for certain node types.
    $enabled_types = $config->get('node_types');
    $anonymous_problems = '';
    $checkboxes = [];
    foreach (NodeType::loadMultiple() as $type_id => $type) {
      $checkboxes[$type_id] = Html::escape($type->label());
      $default[] = $type_id;

      // If they don't have the ability to leave contact info, then we make a report
      $comment_field = FieldConfig::loadByName('node', $type_id, 'comment');
      if (in_array($type_id, $enabled_types) && $comment_field && $comment_field->getSetting('anonymous') == COMMENT_ANONYMOUS_MAYNOT_CONTACT) {
        if (User::getAnonymousUser()->hasPermission('subscribe to comments')) {
          $anonymous_problems[] = $type->link($type->label());
        }
      }
    }

    if (!empty($anonymous_problems)) {
      drupal_set_message($this->t('Anonymous commenters have the permission to subscribe to comments but cannot leave their contact information on the following content types: !types.  You should either disable subscriptions on those types here, revoke the permission for anonymous users, or enable anonymous users to leave their contact information in the comment settings.', [
        '!types' => implode(', ', $anonymous_problems),
      ]), 'status', FALSE);
    }

    $form['node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types to enable for comment notification'),
      '#default_value' => $enabled_types,
      '#options' => $checkboxes,
      '#description' => $this->t('Comments on content types enabled here will have the option of comment notification.'),
    ];

    $form['available_alerts'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Available subscription modes'),
      '#return_value' => 1,
      '#default_value' => array_keys(array_filter($config->get('available_alerts'))),
      '#description' => $this->t('Choose which notification subscription styles are available for users'),
      '#options' => [
        COMMENT_NOTIFY_NODE => $this->t('All comments'),
        COMMENT_NOTIFY_COMMENT => $this->t('Replies to my comment'),
      ],
    ];

    $available_options[COMMENT_NOTIFY_DISABLED] = $this->t('No notifications');
    $available_options += _comment_notify_options();
    $form['default_anon_mailalert'] = [
      '#type' => 'select',
      '#title' => $this->t('Default state for the notification selection box for anonymous users'),
      '#return_value' => 1,
      '#default_value' => $config->get('default_anon_mailalert'),
      '#options' => $available_options,
    ];

    $form['default_registered_mailalert'] = [
      '#type' => 'select',
      '#title' => $this->t('Default state for the notification selection box for registered users'),
      '#return_value' => 1,
      '#default_value' => $config->get('default_registered_mailalert'),
      '#description' => $this->t('This flag presets the flag for the follow-up notification on the form that anon users will see when posting a comment'),
      '#options' => $available_options,
    ];

    $form['node_notify_default_mailalert'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Subscribe users to their node follow-up notification emails by default'),
      '#default_value' => $config->get('node_notify_default_mailalert'),
      '#description' => $this->t('If this is checked, new users will receive e-mail notifications for follow-ups on their nodes by default until they individually disable the feature.'),
    ];

    $form['comment_notify_default_mailtext'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default mail text for sending out notifications to commenters'),
      '#default_value' => $config->get('comment_notify_default_mailtext'),
      '#cols' => 80,
      '#rows' => 15,
      '#token_types' => [
        'comment'
      ],
      '#element_validate' => ['token_element_validate'],
    ];

    $form['node_notify_default_mailtext'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default mail text for sending out the notifications to node authors'),
      '#default_value' => $config->get('node_notify_default_mailtext'),
      '#cols' => 80,
      '#rows' => 15,
      '#token_types' => [
        'comment'
      ],
      '#element_validate' => ['token_element_validate'],
    ];

    $form['token_help'] = [
      '#theme' => 'token_tree',
      '#token_types' => [
        'comment'
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!array_filter($form_state->getValue('available_alerts'))) {
      $form_state->setErrorByName('available_alerts', 'You must enable at least one subscription mode.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('comment_notify.settings')
      ->setData($form_state->getValues())
      ->save();
    parent::submitForm($form, $form_state);
  }

}
