module.exports = function( grunt ) {
	'use strict';

	grunt.util.linefeed = '\n';
	grunt.option( 'stack', true );

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			build: {
				src: 'src/assets/js/*.js'
			},
			grunt: {
				options: {
					node: true
				},
				src: 'Gruntfile.js'
			}
		},

		clean: {
			build: [ 'view/assets' ]
		},

		sass: {
			options: {
				sourceMap: true,
				outputStyle: 'expanded'
			},
			build: {
				src: 'src/assets/sass/postlockdown.scss',
				dest: 'view/assets/css/postlockdown.css'
			}
		},

		cssmin: {
			options: {
				sourceMap: true,
				report: 'gzip'
			},
			build: {
				src: 'view/assets/css/postlockdown.css',
				dest: 'view/assets/css/postlockdown.min.css'
			}
		},

		concat: {
			options: {
				sourceMap: true
			},
			build: {
				src: 'src/assets/js/*.js',
				dest: 'view/assets/js/postlockdown.js'
			}
		},

		uglify: {
			options: {
				sourceMap: true,
				compress: true,
				mangle: true,
				report: 'gzip'
			},
			build: {
				files: [ {
					expand: true,
					cwd: 'view/assets/js',
					src: '*.js',
					dest: 'view/assets/js',
					ext: '.min.js',
					extDot: 'last'
				} ]
			}
		},

		watch: {
			options: { livereload: true, spawn: false },
			sass: {
				files: [ 'src/assets/sass/*.scss' ],
				tasks: [ 'build-css' ]
			},
			js: {
				files: [ 'src/assets/js/*.js' ],
				tasks: [ 'build-js' ]
			},
			grunt: {
				options: { livereload: false },
				files: [ 'Gruntfile.js' ],
				tasks: [ 'jshint:grunt' ]
			},
			livereload: {
				files: [ 'view/assets/css/postlockdown.css' ]
			}
		}

	} );

	require( 'load-grunt-tasks' )( grunt );

	grunt.registerTask( 'build-js', [
		'jshint:build',
		'concat:build',
		'uglify:build'
	] );

	grunt.registerTask( 'build-css', [
		'sass:build',
		'cssmin:build'
	] );

	grunt.registerTask( 'default', [
		'jshint:grunt',
		'clean:build',
		'build-js',
		'build-css'
	] );

};
