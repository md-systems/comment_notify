<?php /**
 * @file
 * Contains \Drupal\comment_notify\EventSubscriber\InitSubscriber.
 */

namespace Drupal\comment_notify\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InitSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onEvent', 0]];
  }

  public function onEvent() {
    // Add on every page - they are both very small so it's better to add
  // everywhere than force a second file on some pages.
    $options = [
      'every_page' => TRUE
      ];
    $path = drupal_get_path('module', 'comment_notify');
    // @FIXME
    // The Assets API has totally changed. CSS, JavaScript, and libraries are now
    // attached directly to render arrays using the #attached property.
    // 
    // 
    // @see https://www.drupal.org/node/2169605
    // @see https://www.drupal.org/node/2408597
    // drupal_add_css($path . '/comment_notify.css', $options);


    // We only add the JS if more than one subscription mode is enabled.
    $available_options = _comment_notify_options();
    if (count($available_options) > 1) {
      // @FIXME
// The Assets API has totally changed. CSS, JavaScript, and libraries are now
// attached directly to render arrays using the #attached property.
// 
// 
// @see https://www.drupal.org/node/2169605
// @see https://www.drupal.org/node/2408597
// drupal_add_js($path . '/comment_notify.js', $options);

    }
  }

}
