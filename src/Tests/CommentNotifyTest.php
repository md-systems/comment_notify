<?php
/**
 * @file
 * Contains \Drupal\comment_notify\Tests\CommentNotifyTest.
 */
namespace Drupal\comment_notify\Tests;

use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Comment notify general tests.
 *
 * @group comment_notify
 */
class CommentNotifyTest extends WebTestBase {
  use CommentTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'comment_notify',
    'node',
    'comment',
    'token',
  ];

  /**
   * Test various behaviors for anonymous users.
   */
  function testCommentNotifyAnonymousUserFunctionalTest() {
    // Code that does something to be tested.
    // Create and login administrative user.
    $admin_user = $this->drupalCreateUser(array('administer comment notify', 'administer permissions', 'administer comments'));
    $this->drupalLogin($admin_user);

    // Enable comment notify on this node and allow anonymous information in comments.
    $this->drupalCreateContentType([
      'type' => 'article',
    ]);
    $this->addDefaultCommentField('node', 'article');
    $comment_field = FieldConfig::loadByName('node', 'article', 'comment');
    $comment_field->setSetting('anonymous', COMMENT_ANONYMOUS_MAY_CONTACT);
    $comment_field->save();

    // Create a node with comments allowed.
    $node = $this->drupalCreateNode(array('type' => 'article', 'promote' => NODE_PROMOTED));

    // Allow anonymous users to post comments and get notifications.
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, array('access comments', 'access content', 'post comments', 'skip comment approval', 'subscribe to comments'));
    $this->drupalLogout();

    // Notify type 1 - All comments on the node.
    // First confirm that we properly require an e-mail address.
    $subscribe_1 = array('notify' => TRUE, 'notify_type' => COMMENT_NOTIFY_NODE);
    $this->drupalGet($node->url());
    $this->postCommentNotifyComment($node, $this->randomMachineName(), $this->randomMachineName(), $subscribe_1);
    $this->assertText(t('If you want to subscribe to comments you must supply a valid e-mail address.'));

    // Try again with an e-mail address.
    $contact_1 = array('name' => $this->randomMachineName(), 'mail' => $this->getRandomEmailAddress());
    $anonymous_comment_1 = $this->postCommentNotifyComment($node, $this->randomMachineName(), $this->randomMachineName(), $subscribe_1, $contact_1);

    // Confirm that the notification is saved.
    $result = comment_notify_get_notification_type($anonymous_comment_1['id']);
    $this->assertEqual($result, $subscribe_1['notify_type'], 'Notify selection option 1 is saved properly.');

    // Notify type 2 - replies to a comment.
    $subscribe_2 = array('notify' => TRUE, 'notify_type' => COMMENT_NOTIFY_COMMENT);
    $contact_2 = array('name' => $this->randomMachineName(), 'mail' => $this->getRandomEmailAddress());
    $anonymous_comment_2 = $this->postCommentNotifyComment($node, $this->randomMachineName(), $this->randomMachineName(), $subscribe_2, $contact_2);

    // Confirm that the notification is saved.
    $result = comment_notify_get_notification_type($anonymous_comment_2['id']);
    $this->assertEqual($result, $subscribe_2['notify_type'], 'Notify selection option 2 is saved properly.');

    // Confirm that the original subscriber with all comments on this node got their mail.
    $this->assertMail('to', $contact_1['mail'], t('Message was sent to the proper anonymous user.'));

    // Notify type 0 (i.e. only one mode is enabled).
    \Drupal::configFactory()->getEditable('comment_notify.settings')->set('available_alerts', [1 => FALSE, 2 => TRUE])->save();
    $subscribe_0 = array('notify' => TRUE);
    $contact_0 = array('mail' => $this->getRandomEmailAddress());
    $anonymous_comment_0 = $this->postCommentNotifyComment($node, $this->randomMachineName(), $this->randomMachineName(), $subscribe_0, $contact_0);

    // Confirm that the notification is saved.
    $result = comment_notify_get_notification_type($anonymous_comment_0['id']);
    $this->assertEqual($result, 2, 'Notify selection option 0 is saved properly.');

    // TODO yet more tests.
  }

  /**
   * Post comment.
   *
   * @param object $node Node to post comment on.
   * @param string $subject Comment subject.
   * @param string $comment Comment body.
   * @param boolean $preview Should preview be required.
   * @param mixed $contact Set to NULL for no contact info, TRUE to ignore success checking, and array of values to set contact info.
   */
  protected function postCommentNotifyComment(NodeInterface $node, $subject, $comment, $notify, $contact = NULL) {
    $edit = array();
    $edit['subject[0][value]'] = $subject;
    $edit['comment_body[0][value]'] = $comment;

    if ($notify !== NULL && is_array($notify)) {
      $edit += $notify;
    }

    if ($contact !== NULL && is_array($contact)) {
      $edit += $contact;
    }

    $this->drupalPostForm($node->url(), $edit, t('Save'));

    $match = array();
    // Get comment ID
    preg_match('/#comment-([^"]+)/', $this->getURL(), $match);

    // Get comment.
    if (!empty($match[1])) { // If true then attempting to find error message.
      if ($subject) {
        $this->assertText($subject, 'Comment subject posted.');
      }
      $this->assertText($comment, 'Comment body posted.');
      $this->assertTrue((!empty($match) && !empty($match[1])), t('Comment id found.'));
    }

    if (isset($match[1])) {
      return array('id' => $match[1], 'subject' => $subject, 'comment' => $comment);
    }
  }

  /**
   * Checks current page for specified comment.
   *
   * @param object $comment Comment object.
   * @param boolean $reply The comment is a reply to another comment.
   * @return boolean Comment found.
   */
  function commentExists($comment, $reply = FALSE) {
    if ($comment && is_object($comment)) {
      $regex = '/' . ($reply ? '<div class="indented">(.*?)' : '');
      $regex .= '<a id="comment-' . $comment->id . '"(.*?)'; // Comment anchor.
      $regex .= '<div(.*?)'; // Begin in comment div.
      $regex .= $comment->subject . '(.*?)'; // Match subject.
      $regex .= $comment->comment . '(.*?)'; // Match comment.
      $regex .= '<\/div>/s'; // Dot matches newlines and ensure that match doesn't bleed outside comment div.

      return (boolean)preg_match($regex, $this->getRawContent());
    }
    else {
      return FALSE;
    }
  }

  /**
   * Returns a randomly generated valid email address.
   * 
   * @return string.
   */
  function getRandomEmailAddress() {
    return $this->randomMachineName() . '@example.com';
  }
}
