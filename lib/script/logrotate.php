#!/usr/bin/php5
<? # vim: ft=php
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   if (!isset($_SERVER['ENVIRONMENT'])) {
      $_SERVER['ENVIRONMENT'] = 'production';
   }

   require_once dirname(__FILE__).'/../script.php';

   $hostname = strtoupper(trim(`hostname -s`));
   $recipients = config('application_default', 'notify_errors');
   $limit = 512 * KB;

   foreach (glob(LOG.'*.log') as $log) {
      if ($log == config('application_default', 'log_file') and $recipients) {
         if (blank($content = file_get_contents($log, false, null, -1, $limit))) {
            continue;
         }

         $content = content_tag('pre', colorize(htmlspecialchars($content)));

         if (filesize($log) > $limit) {
            $content = "<code><strong>Warning: Logfile $log was too big, only showing the first ".format_size($limit)."</strong></code>\n"
                     . "<br><br>\n$content";
         }

         $mail = new Mail();
         $mail->subject = "$hostname: Errors in $log";
         $mail->is_html(true);
         $mail->body = $content;
         $mail->alt_body = strip_html($content);

         $mail->from = "root";
         $mail->send($recipients);
      }

      unlink($log);
   }

?>
