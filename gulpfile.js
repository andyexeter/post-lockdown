(function() {
	'use strict';

	var gulp = require( 'gulp' ),
		jshint = require( 'gulp-jshint' ),
		concat = require( 'gulp-concat' ),
		uglify = require( 'gulp-uglify' ),
		rename = require( 'gulp-rename' ),
		sourcemaps = require( 'gulp-sourcemaps' ),
		sass = require( 'gulp-sass' ),
		cssmin = require( 'gulp-minify-css' );

	var options = {
		jsBuildFile: 'postlockdown',
		cssBuildFile: 'postlockdown'
	};

	var paths = {
		assets: {
			src: 'src/assets/',
			build: 'view/assets/'
		}
	};

	addPaths( 'js' );
	addPaths( 'css', 'sass' );

	function addPaths( dest, src ) {
		if ( arguments.length === 1 ) {
			src = dest;
		}

		paths[ dest ] = {
			get src() {
				return paths.assets.src + src + '/';
			},
			get dest() {
				return paths.assets.build + dest + '/';
			}
		};
	}

	gulp.task( 'default', [ 'jshint-gulpfile', 'js', 'css' ], function() {
	} );

	gulp.task( 'jshint-gulpfile', function() {
		return gulp.src( 'gulpfile.js' )
				   .pipe( jshint() )
				   .pipe( jshint.reporter( 'default' ) )
				   .pipe( jshint.reporter( 'fail' ) );
	} );

	gulp.task( 'js', function() {
		return gulp
			.src( paths.js.src + '*.js' )
			.pipe( jshint() )
			.pipe( jshint.reporter( 'default' ) )
			.pipe( jshint.reporter( 'fail' ) )
			.pipe( sourcemaps.init() )
			// Write the files to the destination path.
			.pipe( concat( options.jsBuildFile + '.js' ) )
			.pipe( gulp.dest( paths.js.dest ) )
			.pipe( uglify() )
			.pipe( rename( function( path ) {
				path.extname = '.min.js';
			} ) )
			// Write the minified files to the destination path.
			.pipe( gulp.dest( paths.js.dest ) )
			.pipe( sourcemaps.write( '.', {
				sourceRoot: paths.js.src
			} ) )
			// Write the source maps to the destination path.
			.pipe( gulp.dest( paths.js.dest ) );
	} );

	gulp.task( 'css', function() {
		return gulp
			.src( paths.css.src + '*.scss' )
			.pipe( sourcemaps.init() )
			.pipe( sass( {
				outputStyle: 'expanded'
			} ).on( 'error', sass.logError ) )
			.pipe( rename( options.cssBuildFile + '.css' ) )
			// Write style.css to the css destination path.
			.pipe( gulp.dest( paths.css.dest ) )
			.pipe( rename( options.cssBuildFile + '.min.css' ) )
			.pipe( cssmin() )
			.pipe( sourcemaps.write( '.', {
				sourceRoot: paths.css.src
			} ) )
			// Write style.min.css to the css destination path.
			.pipe( gulp.dest( paths.css.dest ) );
	} );

	gulp.task( 'watch', function() {
		gulp.watch( paths.js.src + '*.js', [ 'js' ] );
		gulp.watch( paths.css.src + '*.scss', [ 'css' ] );
		gulp.watch( paths.css.img + '*.scss', [ 'img' ] );
	} );
})();
