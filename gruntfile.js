module.exports = function (grunt) {
    grunt.initConfig({
        pkg  : grunt.file.readJSON('package.json'),
        // ADAPT THIS FOR FOUNDATION BASE THEME
        sass : {
            dist: {
                options: {
                    outputStyle: 'expanded' // specify style here
                },
                files: [{
                    expand: true, // allows you to specify directory instead of indiv. files
                    cwd: 'themes/foundation5/scss', // current working directory
                    src: ['**/*.scss'],
                    dest: 'themes/foundation5/css',
                    ext: '.css'
                }]
            },
            // ADAPT THIS FOR FINC THEME
            distfinc: {
                options: {
                    outputStyle: 'expanded' // specify style here
                },
                files: [{
                    expand: true, // allows you to specify directory instead of indiv. files
                    cwd: 'themes/finc/scss', // current working directory
                    src: ['**/*.scss'],
                    dest: 'themes/finc/css',
                    ext: '.css'
                }]
            },
            // ADAPT THIS FOR HOUSE-specific THEMES
            distDE_15: {
                options: {
                    outputStyle: 'expanded' // specify style here
                },
                files: [{
                    expand: true, // allows you to specify directory instead of indiv. files
                    cwd: 'themes/de_15/scss', // current working directory
                    src: ['**/*.scss'],
                    dest: 'themes/de_15/css',
                    ext: '.css'
                }]
            },
            // to here
            // ADAPT THIS FOR HOUSE-specific THEMES
            distDE_GLA1: {
                options: {
                    outputStyle: 'expanded' // specify style here
                },
                files: [{
                    expand: true, // allows you to specify directory instead of indiv. files
                    cwd: 'themes/de_gla1/scss', // current working directory
                    src: ['**/*.scss'],
                    dest: 'themes/de_gla1/css',
                    ext: '.css'
                }]
            },
            // to here
            // ADAPT THIS FOR HOUSE-specific THEMES
            distDE_BN3: {
                options: {
                    outputStyle: 'expanded' // specify style here
                },
                files: [{
                    expand: true, // allows you to specify directory instead of indiv. files
                    cwd: 'themes/de_bn3/scss', // current working directory
                    src: ['**/*.scss'],
                    dest: 'themes/de_bn3/css',
                    ext: '.css'
                }]
            },
            // to here
            // ADAPT THIS FOR HOUSE-specific THEMES
            distDE_J59: {
                options: {
                    outputStyle: 'expanded' // specify style here
                },
                files: [{
                    expand: true, // allows you to specify directory instead of indiv. files
                    cwd: 'themes/de_j59/scss', // current working directory
                    src: ['**/*.scss'],
                    dest: 'themes/de_j59/css',
                    ext: '.css'
                }]
            },
            // to here
            // ADAPT THIS FOR HOUSE-specific THEMES
            distADLR_LINK: {
                options: {
                    outputStyle: 'expanded' // specify style here
                },
                files: [{
                    expand: true, // allows you to specify directory instead of indiv. files
                    cwd: 'themes/adlr_link/scss', // current working directory
                    src: ['**/*.scss'],
                    dest: 'themes/adlr_link/css',
                    ext: '.css'
                }]
            }
            // to here - don't forget comma after brace above  when adding new house
        },
        watch: {
            css: {
                files: '**/*.scss',
                tasks: ['sass']
            }
        }
    });
    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.registerTask('default', ['watch']);
};