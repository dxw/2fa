<?php


add_action('admin_enqueue_scripts', function () {
  // using v1.2 because v1.3 drops support for IE8
  wp_register_script('angularjs', plugin_dir_url(__DIR__).'build/bower_components/angular/angular.min.js');
  wp_enqueue_script('2fa', plugin_dir_url(__DIR__).'assets/js/app.js', ['angularjs']);
  wp_enqueue_style('2fa', plugin_dir_url(__DIR__).'assets/css/app.css');
});
