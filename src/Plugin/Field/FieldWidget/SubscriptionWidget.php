<?php
/**
 * @file
 * Contains \Drupal\comment_notify\Plugin\Field\FieldWidget\SubscriptionWidget.
 */

namespace Drupal\comment_notify\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Standard widget for the SubscriptionItem field type.
 *
 * @FieldWidget(
 *   id = "comment_notify_subscription",
 *   label = @Translation("Comment subscription"),
 *   description = @Translation("Standard widget for a comment subscription field."),
 *   field_types = {
 *     "comment_notify_subscription",
 *   }
 * )
 */
class SubscriptionWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    if (!($user->hasPermission('subscribe to comments') || $user->hasPermission('administer comments'))) {
      return $element;
    }

    /** @var \Drupal\comment\CommentInterface $comment */
    $comment = $items->getEntity();

    $available_options = _comment_notify_options();
    // Add the checkbox for anonymous users.
    if ($user->isAnonymous()) {
      // If anonymous users can't enter their e-mail don't tempt them with the checkbox.
      if (empty($element['author']['mail'])) {
        return $element;
      }
      $element['#validate'][] = 'comment_notify_comment_validate';
    }

    module_load_include('inc', 'comment_notify', 'comment_notify');
    $preference = comment_notify_get_user_comment_notify_preference($user->id());

    $element['notify'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify me when new comments are posted'),
      '#default_value' => $comment->isNew() ? (bool) $preference : $items->notify,
    ];

    $element['notify_type'] = [
      '#type' => 'radios',
      '#options' => $available_options,
      '#default_value' => $comment->isNew() ? ($preference ?: 1) : $items->notify,
    ];
    if (count($available_options) == 1) {
      $element['notify_type']['#type']  = 'hidden';
      $element['notify_type']['#value'] = key($available_options);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $item_values) {
      // Reformat the form values to fit the schema.
      $notify_type_int = array_flip(comment_notify_types())[$item_values['notify_type']];
      $values[$key] = [
        'notify' => $item_values['notify'] ? $notify_type_int : COMMENT_NOTIFY_DISABLED,
      ];
    }
  }
}
