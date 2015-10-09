<?php /**
 * @file
 * Contains \Drupal\comment_notify\Controller\DefaultController.
 */

namespace Drupal\comment_notify\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the comment_notify module.
 */
class DefaultController extends ControllerBase {

  public function comment_notify_disable_page($hash) {
    module_load_include('inc', 'comment_notify', 'comment_notify');
    if (comment_notify_unsubscribe_by_hash($hash)) {
      return(t('Your comment follow-up notification for this post was disabled. Thanks.'));
    }
    else {
      return(t('Sorry, there was a problem unsubscribing from notifications.'));
    }
  }

}
