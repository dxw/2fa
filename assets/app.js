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
    })
})()
