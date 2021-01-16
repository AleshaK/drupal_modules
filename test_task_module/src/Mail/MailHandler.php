<?php

namespace Drupal\test_task_module\Mail;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Handles the assembly and dispatch of HTML emails.
 */
final class MailHandler {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new MailHandler object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   */
  public function __construct(MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * Composes and send email message.
   *
   * @param string $to
   *   The email address or addresses where the message will be sent to.
   * @param TranslatableMarkup $subject
   *   The message subject. To be properly translated with body, it must be
   *   TranslatableMarkup when we switch language.
   * @param array $body
   *   A render array representing message body.
   * @param array $params
   *   Parameters to build the email.
   *
   * @return bool
   *   TRUE if the email was sent successfully, FALSE otherwise.
   *
   * @see \Drupal\Core\Mail\MailManagerInterface::mail()
   */
  public function sendMail(string $to, TranslatableMarkup $subject, array $body, array $params = []): bool {
    $default_params = [
      'headers' => [
        'Content-Type' => 'text/html; charset=UTF-8;',
        'Content-Transfer-Encoding' => '8Bit',
      ],
      'id' => 'mail',
      'reply-to' => NULL,
      'subject' => $subject,
      'langcode' => $this->languageManager->getCurrentLanguage()->getId(),
      // The body will be rendered in test_task_module_mail().
      'body' => $body,
    ];
    $params = array_replace($default_params, $params);

    $message = $this->mailManager->mail('test_task_module', $params['id'], $to, $params['langcode'], $params, $params['reply-to']);

    return (bool) $message['result'];
  }
}