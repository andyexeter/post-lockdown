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
				src: 'src/assets/*.js'
			},
			grunt: {
				options: {
					node: true
				},
				src: 'Gruntfile.js'
			}
		},
		cssmin: {
			build: {
				files: {
					"view/assets/postlockdown.min.css": "view/assets/postlockdown.css"
				}
			}
		},
		less: {
			build: {
				files: {
					"view/assets/postlockdown.css": "src/assets/postlockdown.less"
				}
			}
		},
		uglify: {
			build: {
				files: [ {
						expand: true,
						cwd: 'src/assets/',
						src: '*.js',
						dest: 'view/assets',
						ext: '.min.js',
						extDot: 'last'
					} ]
			}
		},
		watch: {
			options: { livereload: true, spawn: false },
			less: {
				files: [ 'src/assets/*.less' ],
				tasks: [ 'less' ]
			},
			js: {
				files: [ 'src/assets/*.js' ],
				tasks: [ 'jshint:build', 'concat', 'uglify' ]
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

	grunt.registerTask( 'default', [ 'jshint:build', 'uglify:build', 'less:build', 'cssmin:build' ] );

};
