var gulp = require('gulp'),
	jshint = require('gulp-jshint'),
	concat = require('gulp-concat'),
	uglify = require('gulp-uglify'),
	rename = require('gulp-rename'),
	sourcemaps = require('gulp-sourcemaps'),
	cssmin = require('gulp-minify-css'),
	imagemin = require('gulp-imagemin');

var paths = {
	js: {
		src: 'src/assets/js/',
		dest: 'build/assets/js/'
	},
	css: {
		src: 'src/assets/sass/',
		dest: 'build/assets/css/'
	},
	img: {
		src: 'src/assets/img/',
		dest: 'build/assets/img/'
	}
};


gulp.task( 'default', [ 'js', 'css', 'img' ], function() {} );

gulp.task( 'js', function() {
	return gulp.src( paths.js.src + '*.js' )
		// Lint all matching files with jshint
		.pipe( jshint() )
		// Init source mapping plugin
		.pipe( sourcemaps.init() )
		// Concatenate the stream
		.pipe( concat( 'main.js' ) )
		// Write main.js to the js destination path
		.pipe( gulp.dest( paths.js.dest ) )
		// Rename the stream to main.min.js
		.pipe( rename( 'main.min.js' ) )
		// Uglify the stream
		.pipe( uglify() )
		// Write the source map files
		.pipe( sourcemaps.write() )
		// Write main.min.js to the js destination path
		.pipe( gulp.dest( paths.js.dest ) );
} );

gulp.task( 'css', function() {
	return gulp.src( paths.css.src + '*.sass' )
		// Init source mapping plugin
		.pipe( sourcemaps.init() )
		// Concatenate the stream
		.pipe( concat( 'style.css' ) )
		// Write style.js to the css destination path
		.pipe( gulp.dest( paths.css.dest ) )
		// Rename the steam to style.min.css
		.pipe( rename( 'style.min.css' ) )
		// Minify the stream
		.pipe( cssmin() )
		// Write the source maps
		.pipe( sourcemaps.write() )
		// Write style.min.css to the css destination path
		.pipe( gulp.dest( paths.css.dest ) );
} );

gulp.task( 'img', function() {
	return gulp.src( paths.img.src + '*.*' )
		// Minify all matching images
		.pipe( imagemin() )
		// Write the minified images to the img destination path
		.pipe( gulp.dest( paths.img.dest ) );
} );

gulp.task( 'watch', function() {
	return gulp.watch();
});
