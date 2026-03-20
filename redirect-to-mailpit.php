<?php

/**
 * Plugin Name: Redirect to Mailpit
 * Description: Redirects all outgoing emails to Mailpit, a local email testing tool. This plugin is ideal for development environments where you want to capture and view emails without sending them to real recipients.
 * Author: Jason Gerber
 * Version: 1.0.0
 * Author URI: https://jasongerber.ch
 * GitHub Plugin URI: jsongerber/redirect-to-mailpit
 * Release Asset: true
 * Network: True
 */

use PHPMailer\PHPMailer\PHPMailer;

// Disable emails in local development
if (defined('REDIRECT_EMAILS_TO')) {

  // Force use of phpmailer to be able to intercept emails with phpmailer_init action
  add_filter('option_postman_options', function ($options) {
    error_log(print_r('salut', true));
    // error_log(print_r($options, true));
    $options['smtp_mailers'] = 'phpmailer';
    // error_log(print_r($options, true));
    return $options;
  }, 99);

  /**
   * @param array<string, string> $args
   * @return array<string, string>
   */
  function jason_disable_emails($args): array
  {
    try {
      /** @var list<string>|string $redirect_emails_to */
      $redirect_emails_to = constant('REDIRECT_EMAILS_TO');
    } catch (Error) {
      return $args;
    }

    if ($redirect_emails_to !== $args['to']) {
      unset($args['to']);
      $args['to'] = is_array($redirect_emails_to) ? implode(',', $redirect_emails_to) : $redirect_emails_to;
    }

    return $args;
  }
  add_filter('wp_mail', 'jason_disable_emails', 10, 1);

  add_action('phpmailer_init', 'jason_clear_recipients', 9999);
  function jason_clear_recipients(PHPMailer $phpmailer): void
  {
    error_log(print_r('should', true));
    try {
      /** @var list<string>|string $redirect_emails_to */
      $redirect_emails_to = constant('REDIRECT_EMAILS_TO');
    } catch (Error) {
      return;
    }

    $phpmailer->clearAllRecipients();

    try {
      if (is_array($redirect_emails_to))
        foreach ($redirect_emails_to as $email)
          $phpmailer->addAddress($email);
      else
        $phpmailer->addAddress($redirect_emails_to, 'Jason Gerber');
    } catch (Exception) {
    }

    /** @phpstan-ignore constant.notFound */
    if ('development' === WP_ENV) {
      $phpmailer->Host = '127.0.0.1';
      $phpmailer->SMTPAuth = false;
      $phpmailer->SMTPAutoTLS = false;
      $phpmailer->SMTPSecure = '';
      $phpmailer->Port = 1025;
      $phpmailer->isSMTP();
    }
  }
}
