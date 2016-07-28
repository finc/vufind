module.exports = function(grunt) {
  require('jit-grunt')(grunt); // Just in time library loading

  grunt.initConfig({
    // LESS compilation
    less: {
      compile: {
        options: {
          paths: ["themes/bootprint3/less", "themes/bootstrap3/less"],
          compress: true,
          modifyVars: {
            'fa-font-path': '"fonts"',
            'img-path': '"../images"',
          }
        },
        files: [{
          expand: true,
          src: "themes/*/less/compiled.less",
          rename: function (dest, src) {
            return src.replace('/less/', '/css/').replace('.less', '.css');
          }
        }]
      }
    },
    // SASS compilation
    sass: {
      compile: {
        options: {
          loadPath: ["themes/bootprint3/sass", "themes/bootstrap3/sass"],
          style: 'compress'
        },
        files: {
          "themes/bootstrap3/css/compiled.css": "themes/bootstrap3/sass/bootstrap.scss",
          "themes/bootprint3/css/compiled.css": "themes/bootprint3/sass/bootprint.scss"
        }
      }
    },
    // Convert LESS to SASS
    lessToSass: {
      convert: {
        files: [
          {
            expand: true,
            cwd: 'themes/bootstrap3/less',
            src: ['*.less', 'components/*.less'],
            ext: '.scss',
            dest: 'themes/bootstrap3/sass'
          },
          {
            expand: true,
            cwd: 'themes/bootprint3/less',
            src: ['*.less'],
            ext: '.scss',
            dest: 'themes/bootprint3/sass'
          }
        ],
        options: {
          replacements: [
            { // Replace ; in include with ,
              pattern: /(\s+)@include ([^\(]+)\(([^\)]+)\);/gi,
              replacement: function (match, space, $1, $2) {
                return space+'@include '+$1+'('+$2.replace(/;/g, ',')+');';
              },
              order: 3
            },
            { // Inline &:extends converted
              pattern: /&:extend\(([^\)]+)\)/gi,
              replacement: '@extend $1',
              order: 3
            },
            { // Inline variables not default
              pattern: / !default; }/gi,
              replacement: '; }',
              order: 3
            },
            {  // VuFind: Correct paths
              pattern: 'vendor/bootstrap/bootstrap',
              replacement: 'vendor/bootstrap',
              order: 4
            },
            {
              pattern: '$fa-font-path: "../../../fonts" !default;',
              replacement: '$fa-font-path: "fonts";',
              order: 4
            },
            {
              pattern: '$img-path: "../../images" !default;',
              replacement: '$img-path: "../images";',
              order: 4
            },
            { // VuFind: Bootprint fixes
              pattern: '@import "bootstrap";\n@import "variables";',
              replacement: '@import "variables", "bootstrap";',
              order: 4
            },
            {
              pattern: '$brand-primary: #619144 !default;',
              replacement: '$brand-primary: #619144;',
              order: 4
            }
          ]
        }
      }
    }
  });
};
