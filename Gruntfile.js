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
        browserify: {
            js: {
                files: {
                    'build/app.min.js': ['assets/js/app.js'],
                },
            },
        },
        concat: {
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

    grunt.loadNpmTasks('grunt-contrib-concat')
    grunt.loadNpmTasks('grunt-contrib-copy')
    grunt.loadNpmTasks('grunt-contrib-watch')
    grunt.loadNpmTasks('grunt-browserify')

    grunt.registerTask('default', [
        'copy',
        'concat',
        'browserify',
    ])

    grunt.renameTask('watch', '_watch')
    grunt.registerTask('watch', [
        'default',
        '_watch',
    ])

}
