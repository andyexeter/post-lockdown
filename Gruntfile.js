'use strict';
module.exports = function ( grunt ) {

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
		copy: {
			js: {
				files: [ {
						expand: true,
						cwd: 'src/assets/js',
						src: '*.js',
						dest: 'view/assets/js'
					} ]
			}
		},
		cssmin: {
			build: {
				files: {
					"view/assets/css/postlockdown.min.css": "view/assets/css/postlockdown.css"
				}
			}
		},
		less: {
			build: {
				files: {
					"view/assets/css/postlockdown.css": "src/assets/less/postlockdown.less"
				}
			}
		},
		uglify: {
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
			less: {
				files: [ 'src/assets/less/*.less' ],
				tasks: [ 'less:build' ]
			},
			js: {
				files: [ 'src/assets/js/*.js' ],
				tasks: [ 'jshint:build', 'copy:js', 'uglify' ]
			},
			grunt: {
				options: { livereload: false },
				files: [ 'Gruntfile.js' ],
				tasks: [ 'jshint:grunt' ]
			},
			livereload: {
				files: [ 'build/assets/css/style.css' ]
			}
		}

	} );

	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-contrib-concat' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-contrib-less' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );

	grunt.registerTask( 'default', [ 'jshint:build', 'clean:build', 'copy:js', 'uglify:build', 'less:build', 'cssmin:build' ] );

};
