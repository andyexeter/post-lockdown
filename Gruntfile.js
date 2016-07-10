module.exports = function( grunt ) {
	'use strict';

	require( 'load-grunt-tasks' )( grunt );

	grunt.util.linefeed = '\n';
	grunt.option( 'stack', true );

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		paths: {
			src: {
				js: 'src/assets/js',
				css: 'src/assets/sass'
			},
			build: {
				js: 'view/assets/js',
				css: 'view/assets/css'
			}
		},

		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			build: {
				src: '<%= paths.src.js %>/*.js'
			},
			grunt: {
				options: {
					node: true
				},
				src: 'Gruntfile.js'
			}
		},

		clean: {
			buildJs: [ '<%= paths.build.js %>' ],
			buildCss: [ '<%= paths.build.css %>' ]
		},

		sasslint: {
			options: {},
			target: [ '<%= paths.src.css %>/*.scss' ]
		},

		sass: {
			options: {
				sourceMap: true,
				outputStyle: 'expanded'
			},
			build: {
				src: '<%= paths.src.css %>/postlockdown.scss',
				dest: '<%= paths.build.css %>/postlockdown.css'
			}
		},

		cssmin: {
			options: {
				sourceMap: true,
				report: 'gzip'
			},
			build: {
				src: '<%= paths.build.css %>/postlockdown.css',
				dest: '<%= paths.build.css %>/postlockdown.min.css'
			}
		},

		concat: {
			options: {
				sourceMap: true
			},
			build: {
				src: '<%= paths.src.js %>/*.js',
				dest: '<%= paths.build.js %>/postlockdown.js'
			}
		},

		uglify: {
			options: {
				sourceMap: true,
				report: 'gzip'
			},
			build: {
				files: [ {
					expand: true,
					cwd: '<%= paths.build.js %>',
					src: '*.js',
					dest: '<%= paths.build.js %>',
					ext: '.min.js',
					extDot: 'last'
				} ]
			}
		},

		watch: {
			options: { livereload: true, spawn: false },
			sass: {
				files: [ '<%= paths.src.css %>/*.scss' ],
				tasks: [ 'buildCss' ]
			},
			js: {
				files: [ '<%= paths.src.js %>/*.js' ],
				tasks: [ 'buildJs' ]
			},
			grunt: {
				options: { livereload: false },
				files: [ 'Gruntfile.js' ],
				tasks: [ 'jshint:grunt' ]
			},
			livereload: {
				files: [ '<%= paths.build.css %>/postlockdown.css' ]
			}
		}

	} );

	grunt.registerTask( 'buildJs', [
		'jshint:build',
		'clean:buildJs',
		'concat:build',
		'uglify:build'
	] );

	grunt.registerTask( 'buildCss', [
		'clean:buildCss',
		'sass:build',
		'cssmin:build'
	] );

	grunt.registerTask( 'default', [
		'jshint:grunt',
		'buildJs',
		'buildCss'
	] );
};
