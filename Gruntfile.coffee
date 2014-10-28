module.exports = (grunt) ->
  
  # Project configuration.
  grunt.initConfig
    pkg: grunt.file.readJSON("package.json")
   #  uglify:
#       build:
#         src: "**/*.js"
#         cwd: "application/public/js/compiled/"
#         dest: "application/public/js/compiled/"
#         expand: true
#         options:
#           mangle: false
#         ext: ".js"
    coffee:
      glob_to_multiple:
        expand: true
#         sourceMap: true
#         flatten: true
        cwd: "application/public/js/source/"
        src: "**/*.coffee"
        dest: "application/public/js/source"
        ext: ".js"
        options:
          bare: false
          join: true
#     concat:
#       options:
#         separator: ";"
#       dist:
#         src:  "build/*.js"
#         dest: "application/public/css/compiled"
    compass:
      dev:
        options:
          sassDir: 'application/public/css/source'
          cssDir:  'application/public/css/compiled'
          outputStyle: 'expanded'
          relativeAssets: true
#           debugInfo: true
#           clean: true
      build:
        options:
          sassDir: 'application/public/css/source'
          cssDir:  'application/public/css/compiled'
          outputStyle: 'compressed'
          relativeAssets: true
          noLineComments: true
#           force: true
          #clean: true
    watch:
      scripts: 
        files: ["application/public/js/source/**/*"]
        tasks: ["dev:js"]
      styles: 
        files: ["application/public/css/source/**"]
        tasks: ["dev:compass"]
    clean: 
      all: [
        "application/public/css/compiled/*"
        "application/public/js/compiled/*"
      ]
    copy:
      js:
        files: [
          expand: true # includes files in path
          cwd:  "application/public/js/source/"
          src:  "**"
          dest: "application/public/js/compiled/"
        ]
  # Load the plugin that provides the "uglify" task.
  grunt.loadNpmTasks "grunt-contrib-uglify"
  #grunt.loadNpmTasks "grunt-contrib-jshint"
  #grunt.loadNpmTasks "grunt-contrib-qunit"
  grunt.loadNpmTasks "grunt-contrib-watch"
#   grunt.loadNpmTasks "grunt-contrib-concat"
  grunt.loadNpmTasks "grunt-contrib-coffee"
  grunt.loadNpmTasks "grunt-contrib-compass"
  grunt.loadNpmTasks "grunt-contrib-copy"
  grunt.loadNpmTasks 'grunt-contrib-clean'
  
  #grunt.loadNpmTasks "grunt-contrib-sass"
  
  # Default task(s).
  grunt.registerTask "default",     ["clean", "coffee","uglify:build","compass"]
  grunt.registerTask "dev:compass", ["compass"]
  grunt.registerTask "dev:js",      ["coffee","copy:js"]