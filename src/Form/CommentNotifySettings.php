<?php

/**
 * @file
 * Contains \Drupal\comment_notify\Form\CommentNotifySettings.
 */

namespace Drupal\comment_notify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('comment_notify.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['comment_notify.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    module_load_include('inc', 'comment_notify', 'comment_notify');

    $form['comment_notify_settings'] = [];

    // Only perform comment_notify for certain node types.
    $enabled_types = comment_notify_variable_registry_get('node_types');
    $anonymous_problems = '';
    foreach (node_type_get_names() as $type => $name) {
      $checkboxes[$type] = \Drupal\Component\Utility\Html::escape($name);
      $default[] = $type;

      // If they don't have the ability to leave contact info, then we make a report
      // @FIXME
      // // @FIXME
      // // The correct configuration object could not be determined. You'll need to
      // // rewrite this call manually.
      // if (isset($enabled_types[$type]) && $enabled_types[$type] && variable_get('comment_anonymous_' . $type, COMMENT_ANONYMOUS_MAYNOT_CONTACT) == COMMENT_ANONYMOUS_MAYNOT_CONTACT) {
      //       $account = drupal_anonymous_user();
      //       if ($account->hasPermission('subscribe to comments')) {
      //         // @FIXME
      // // l() expects a Url object, created from a route name or external URI.
      // // $anonymous_problems[] = l(t('@content-type', array('@content-type' => $name)), 'admin/structure/types/manage/' . $type);
      // 
      //       }
      //     }

    }

    if (!empty($anonymous_problems)) {
      drupal_set_message(t('Anonymous commenters have the permission to subscribe to comments but cannot leave their contact information on the following content types: !types.  You should either disable subscriptions on those types here, revoke the permission for anonymous users, or enable anonymous users to leave their contact information in the comment settings.', [
        '!types' => implode(', ', $anonymous_problems)
        ]), 'status', FALSE);
    }

    $form['comment_notify_settings']['comment_notify_node_types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Content types to enable for comment notification'),
      '#default_value' => $enabled_types,
      '#options' => $checkboxes,
      '#description' => t('Comments on content types enabled here will have the option of comment notification.'),
    ];

    $form['comment_notify_settings']['comment_notify_available_alerts'] = [
      '#type' => 'checkboxes',
      '#title' => t('Available subscription modes'),
      '#return_value' => 1,
      '#default_value' => comment_notify_variable_registry_get('available_alerts'),
      '#description' => t('Choose which notification subscription styles are available for users'),
      '#options' => [
        COMMENT_NOTIFY_NODE => t('All comments'),
        COMMENT_NOTIFY_COMMENT => t('Replies to my comment'),
      ],
    ];

    $available_options[COMMENT_NOTIFY_DISABLED] = t('No notifications');
    $available_options += _comment_notify_options();
    $form['comment_notify_settings']['comment_notify_default_anon_mailalert'] = [
      '#type' => 'select',
      '#title' => t('Default state for the notification selection box for anonymous users'),
      '#return_value' => 1,
      '#default_value' => comment_notify_variable_registry_get('default_anon_mailalert'),
      '#options' => $available_options,
    ];

    $form['comment_notify_settings']['comment_notify_default_registered_mailalert'] = [
      '#type' => 'select',
      '#title' => t('Default state for the notification selection box for registered users'),
      '#return_value' => 1,
      '#default_value' => comment_notify_variable_registry_get('default_registered_mailalert'),
      '#description' => t('This flag presets the flag for the follow-up notification on the form that anon users will see when posting a comment'),
      '#options' => $available_options,
    ];

    $form['comment_notify_settings']['comment_notify_node_notify_default_mailalert'] = [
      '#type' => 'checkbox',
      '#title' => t('Subscribe users to their node follow-up notification emails by default'),
      '#default_value' => comment_notify_variable_registry_get('node_notify_default_mailalert'),
      '#description' => t('If this is checked, new users will receive e-mail notifications for follow-ups on their nodes by default until they individually disable the feature.'),
    ];

    $form['comment_notify_settings']['comment_notify_comment_notify_default_mailtext'] = [
      '#type' => 'textarea',
      '#title' => t('Default mail text for sending out notifications to commenters'),
      '#default_value' => comment_notify_variable_registry_get('comment_notify_default_mailtext'),
      '#return_value' => 1,
      '#cols' => 80,
      '#rows' => 15,
      '#token_types' => [
        'comment'
        ],
      '#element_validate' => ['token_element_validate'],
    ];

    $form['comment_notify_settings']['comment_notify_node_notify_default_mailtext'] = [
      '#type' => 'textarea',
      '#title' => t('Default mail text for sending out the notifications to node authors'),
      '#default_value' => comment_notify_variable_registry_get('node_notify_default_mailtext'),
      '#return_value' => 1,
      '#cols' => 80,
      '#rows' => 15,
      '#token_types' => [
        'comment'
        ],
      '#element_validate' => ['token_element_validate'],
    ];

    $form['comment_notify_settings']['token_help'] = [
      '#theme' => 'token_tree',
      '#token_types' => [
        'comment'
        ],
    ];

    $form['#validate'] = ['comment_notify_settings_validate'];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $sum_enabled = 0;
    foreach ($form_state->getValue(['comment_notify_available_alerts']) as $enabled) {
      $sum_enabled += $enabled;
    }
    if (!$sum_enabled) {
      $form_state->setErrorByName('comment_notify_available_alerts', 'You must enable at least one subscription mode.');
    }
  }

}
