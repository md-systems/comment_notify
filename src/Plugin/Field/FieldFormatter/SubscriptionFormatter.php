<?php
/**
 * @file
 * Contains \Drupal\comment_notify\Plugin\Field\FieldFormatter\SubscriptionFormatter.
 */

namespace Drupal\comment_notify\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 *
 *
 * @FieldFormatter(
 *   id = "comment_notify_subscription"
 * )
 */
class SubscriptionFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // TODO: Implement viewElements() method.
  }

}
