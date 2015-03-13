module.exports = function (grunt) {
    'use strict';

    grunt.initConfig({
        copy: {
            bower: {
                files: [
                    {
                        src: [
                            'bower_components/angular/angular.min.js',
                        ],
                        dest: 'build/',
                    },
                ],
            },
        },
        concat: {
            js: {
                src: ['assets/js/ng-autofocus.js', 'assets/js/setup.js', 'assets/js/network-sites.js'],
                dest: 'build/app.min.js',
            },
            css: {
                src: ['assets/css/app.css'],
                dest: 'build/app.min.css',
            },
        },
        _watch: {
            assets: {
                files: 'assets/**/*',
                tasks: ['default'],
                options: {
                    interrupt: true,
                },
            },
        },
    })

    grunt.loadNpmTasks('grunt-contrib-copy')
    grunt.loadNpmTasks('grunt-contrib-concat')
    grunt.loadNpmTasks('grunt-contrib-watch')
    grunt.registerTask('default', [
        'copy',
        'concat',
    ])

    grunt.renameTask('watch', '_watch')
    grunt.registerTask('watch', [
        'default',
        '_watch',
    ])

}
