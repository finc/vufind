module.exports = function(grunt) {
  require('jit-grunt')(grunt); // Just in time library loading

  grunt.initConfig({
    // less
    less: {
      development: {
        options: {
          paths: ["themes/bootprint3/less", "themes/bootstrap3/less"],
          compress: true
        },
        files: {
          "themes/bootstrap3/css/compiled.css": "themes/bootstrap3/less/bootstrap.less",
          "themes/bootprint3/css/compiled.css": "themes/bootprint3/less/bootprint.less",
        }
      }
    },
    // JS compression
    uglify: {
      options: {
        mangle: false
      },
      vendor_min: {
        files: {
          'themes/bootstrap3/js/vendor.min.js': [
            'themes/bootstrap3/js/vendor/jquery.min.js',
            'themes/bootstrap3/js/vendor/bootstrap.min.js',
            'themes/bootstrap3/js/vendor/*.js',
            '!themes/bootstrap3/js/vendor/bootlint.min.js',
            'themes/bootstrap3/js/autocomplete.js'
          ]
        }
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-less');
  grunt.loadNpmTasks('grunt-contrib-uglify');

  grunt.registerTask('default', ['less']);
  grunt.registerTask('dev', ['less', 'uglify']);
  grunt.registerTask('css', ['less']);
  grunt.registerTask('js', ['uglify']);
};