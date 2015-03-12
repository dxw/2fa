(function() {
    'use strict';

    var urlencoded = function(obj) {
        var str = [];
        for(var p in obj)
            str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
        return str.join("&");
    }

    var setup = angular.module('2fa', [])

    setup.controller('Setup', function ($scope, $http) {

        $scope.$watch('step', function(newValue, oldValue) {
            if (newValue === 2 && $scope.mode === 'totp') {
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
                                    $scope.totp_secret = data.secret
                                }
                            })
                            .error(function (data) {
                                alert('unexpected error. TODO')
                            })
            }
        })

        $scope.verify = function (token) {
            $scope.verification = 'verifying'

            $http({
                method: 'POST',
                url: window.ajaxurl,
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                transformRequest: urlencoded,
                data: {
                    action: '2fa_verify',
                    nonce: document.getElementById('2fa_verify').value,
                    token: token,
                },
            })
                        .success(function (data) {
                            if (data.valid) {
                                $scope.verification = 'valid'
                            } else if (data.error) {
                                alert('unexpected error4. TODO')
                            } else {
                                $scope.verification = 'invalid'
                            }
                        })
                        .error(function (data) {
                            alert('unexpected error3. TODO')
                            $scope.verification = 'invalid'
                        })
        }

        $scope.rand = function () {
            return Math.floor(Math.random()*16777215).toString(16)
        }

        $scope.prettyPrintSecret = function (secret) {
            return secret.replace(/(....)/g, '$1 ').trim()
        }

    })
})()
