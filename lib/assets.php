<?php


add_action('admin_enqueue_scripts', function () {
  // using v1.2 because v1.3 drops support for IE8
  // TODO: use a local copy
  wp_register_script('angularjs', 'https://ajax.googleapis.com/ajax/libs/angularjs/1.2.27/angular.min.js');
  wp_enqueue_script('2fa', plugin_dir_url(__DIR__).'assets/app.js', ['angularjs']);
});
