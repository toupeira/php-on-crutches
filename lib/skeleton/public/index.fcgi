#!/usr/bin/php-cgi
<?
   require '../config/environment.php';
   Dispatcher::run($_GET['path']);
?>
