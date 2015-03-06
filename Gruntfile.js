module.exports = function (grunt) {
    'use strict';

    grunt.initConfig({
        copy: {
            dist: {
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
    })

    grunt.loadNpmTasks('grunt-contrib-copy')
    grunt.registerTask('default', [
        'copy',
    ])

}
