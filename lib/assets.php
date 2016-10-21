<?php

// Adds JS/CSS to the admin header
add_action('admin_enqueue_scripts', function () {
    // using v1.2 because v1.3 drops support for IE8
  wp_register_script('angularjs', plugin_dir_url(__DIR__).'build/bower_components/angular/angular.min.js');
    wp_enqueue_script('2fa', plugin_dir_url(__DIR__).'build/app.min.js', ['angularjs']);
    wp_enqueue_style('2fa', plugin_dir_url(__DIR__).'build/app.min.css');
});

// Adds JS/CSS to the login page
add_action('login_enqueue_scripts', function () {
    // using v1.2 because v1.3 drops support for IE8
  wp_enqueue_style('2fa', plugin_dir_url(__DIR__).'build/app.min.css');
});
