<?php
/**
 * @file
 * Contains \Drupal\comment_notify\Plugin\Field\FieldType\SubscriptionItem.
 */

namespace Drupal\comment_notify\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Models a single Comment Notify subscription.
 *
 * @FieldType(
 *   id = "comment_notify_subscription",
 *   label = @Translation("Comment subscription"),
 *   description = @Translation("Allows commenters to subscribe to be notified of further comments on the same entity."),
 *   default_widget = "comment_notify_subscription",
 *   default_formatter = "comment_notify_subscription"
 * )
 */
class SubscriptionItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['notify'] = DataDefinition::create('integer')
      ->setLabel(t('Subscription type'))
      ->setDescription(t('Indicates the type of subscription: 0 means not subscribed, 1 means subscribed to all comments, and 2 means only subscribed to replies of this comment.'));

    $properties['notify_hash'] = DataDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setDescription('A hash of unique information about the commenter. Used for unsubscribing users.');

    $properties['notified'] = DataDefinition::create('boolean')
      ->setLabel(t('Notified'))
      ->setDescription('Whether or not a notification for the comment has been sent');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'notify';
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'notify' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
        'notify_hash' => [
          'type' => 'varchar',
          'length' => '128',
        ],
        'notified' => [
          'type' => 'int',
          'size' => 'tiny',
        ],
      ],
      'indexes' => [
        'notify_hash' => ['notify_hash'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $form['cardinality_note'] = [
      '#markup' => '<p>' . $this->t('Please note that this field only supports <var>1</var> as value for the %cardinality_label setting.', ['%cardinality_label' => $this->t('Allowed number of values')]) . '</p>',
    ];
    return parent::storageSettingsForm($form, $form_state, $has_data);
  }

}
