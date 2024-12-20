(function() {
  'use strict';

  require('./ng-autofocus.js')

  var urlencoded = function(obj) {
    var str = [];
    for(var p in obj)
      str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
    return str.join("&");
  }

  var setup = angular.module('2fa', ['utils.autofocus'])

  setup.controller('Setup', function ($rootScope, $http) {

    $rootScope.$watch('step', function(newValue, oldValue) {
      if (newValue === 'totp-2') {
        $http({
          method: 'POST',
          url: window.ajaxurl,
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          transformRequest: urlencoded,
          data: {
            action: '2fa_generate_secret',
            nonce: document.getElementById('2fa_generate_secret').value,
          },
        })
              .success(function (data) {
                if (data.error) {
                  alert('unexpected error2. TODO')
                } else {
                  $rootScope.totp_secret = data.secret
                }
              })
              .error(function (data) {
                alert('unexpected error1. TODO')
              })
      } else if (newValue === 'sms-2') {
        $http({
          method: 'POST',
          url: window.ajaxurl,
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          transformRequest: urlencoded,
          data: {
            action: '2fa_sms_send_verification',
            nonce: document.getElementById('2fa_sms_send_verification').value,
            number: $rootScope.sms_number,
          },
        })
              .success(function (data) {
                if (data.error) {
                  $rootScope.sms_sent = 'error'
                } else {
                  $rootScope.sms_sent = 'sent'
                }
              })
              .error(function (data) {
                $rootScope.sms_sent = 'error'
              })
      } else if (newValue === 'email-2') {
        $http({
          method: 'POST',
          url: window.ajaxurl,
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          transformRequest: urlencoded,
          data: {
            action: '2fa_email_send_verification',
            nonce: document.getElementById('2fa_email_send_verification').value
          },
        })
              .success(function (data) {
                if (data.error) {
                  $rootScope.email_sent = 'error'
                } else {
                  $rootScope.email_sent = 'sent'
                }
              })
              .error(function (data) {
                $rootScope.email_sent = 'error'
              })
      }
    })

    $rootScope.verify = function (token, deviceName) {
      $rootScope.verification = 'verifying'

      $http({
        method: 'POST',
        url: window.ajaxurl,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        transformRequest: urlencoded,
        data: {
          action: '2fa_verify',
          nonce: document.getElementById('2fa_verify').value,
          token: token,
          deviceName: deviceName,
        },
      })
            .success(function (data) {
              if (data.valid) {
                $rootScope.verification = 'valid'
              } else if (data.error) {
                alert('unexpected error4. TODO')
              } else {
                $rootScope.verification = 'invalid'
              }
            })
            .error(function (data) {
              alert('unexpected error3. TODO')
              $rootScope.verification = 'invalid'
            })
    }

    $rootScope.sms_verify = function (token, deviceName) {
      $rootScope.verification = 'verifying'

      $http({
        method: 'POST',
        url: window.ajaxurl,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        transformRequest: urlencoded,
        data: {
          action: '2fa_sms_verify',
          nonce: document.getElementById('2fa_sms_verify').value,
          token: token,
          deviceName: deviceName,
        },
      })
            .success(function (data) {
              if (data.error) {
                alert('unexpected error5. TODO')
                $rootScope.verification = 'invalid'
              } else if (data.valid) {
                $rootScope.verification = 'valid'
              } else {
                $rootScope.verification = 'invalid'
              }
            })
            .error(function (data) {
              alert('unexpected error6. TODO')
              $rootScope.verification = 'invalid'
            })
    }

    $rootScope.email_verify = function (token) {
      $rootScope.verification = 'verifying'

      $http({
        method: 'POST',
        url: window.ajaxurl,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        transformRequest: urlencoded,
        data: {
          action: '2fa_email_verify',
          nonce: document.getElementById('2fa_email_verify').value,
          token: token
        },
      })
            .success(function (data) {
              if (data.error) {
                alert('unexpected error5. TODO')
                $rootScope.verification = 'invalid'
              } else if (data.valid) {
                $rootScope.verification = 'valid'
              } else {
                $rootScope.verification = 'invalid'
              }
            })
            .error(function (data) {
              alert('unexpected error6. TODO')
              $rootScope.verification = 'invalid'
            })
    }

    $rootScope.rand = function () {
      return Math.floor(Math.random()*16777215).toString(16)
    }

    $rootScope.prettyPrintSecret = function (secret) {
      if (typeof secret === 'string') {
        return secret.replace(/(....)/g, '$1 ').trim()
      }
    }

  })
})()
