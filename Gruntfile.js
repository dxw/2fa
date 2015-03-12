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
                src: ['assets/js/ng-autofocus.js', 'assets/js/app.js'],
                dest: 'build/app.min.js',
            },
            css: {
                src: ['assets/css/app.css'],
                dest: 'build/app.min.css',
            },
        },
    })

    grunt.loadNpmTasks('grunt-contrib-copy')
    grunt.loadNpmTasks('grunt-contrib-concat')
    grunt.registerTask('default', [
        'copy',
        'concat',
    ])

}
