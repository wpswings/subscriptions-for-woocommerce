var gulp          = require('gulp');
var gutil         = require('gulp-util');
var newer         = require('gulp-newer');
var imagemin      = require('gulp-imagemin');
var sass          = require('gulp-sass');
var postcss       = require('gulp-postcss');
var deporder      = require('gulp-deporder');
var concat        = require('gulp-concat');
var stripdebug    = require('gulp-strip-debug');
var uglify        = require('gulp-uglify');
var wpPot         = require('gulp-wp-pot');
var browsersync   = require('browser-sync');

var admin_config = {
  src: './admin/src/*',
  dist: './admin/dist/*',
  js_src : './admin/src/js/*.js',
  js_dist: './admin/dist/js/',
  filename : 'admin_script.js',
  css_src : './admin/src/scss/*.scss',
  css_dist: './admin/dist/css/',
  image_src : './admin/src/image/*',
  image_dist: './admin/dist/image/'
}
var public_config = {
  src: './public/src/*',
  dist: './public/dist/*',
  js_src : './public/src/js/*.js',
  js_dist: './public/dist/js/',
  filename : 'public_script.js',
  css_src : './public/src/scss/*.scss',
  css_dist: './public/dist/css/',
  image_src : './public/src/image/*',
  image_dist: './public/dist/image/'
}

// CSS admin settings
var css_admin = {
  src         : admin_config.css_src,
  watch       : admin_config.css_src,
  build       : admin_config.css_dist,
  sassOpts: {
    outputStyle     : 'nested',
    imagePath       : admin_config.image_dist,
    precision       : 3,
    errLogToConsole : true
  },
  processors: [
    require('autoprefixer')({
      browsers: ['last 2 versions', '> 2%']
    }),
    require('css-mqpacker'),
    require('cssnano')
  ]
};
// CSS admin processing
gulp.task('admin-css', async () => {
  return gulp.src(css_admin.src)
    .pipe(sass(css_admin.sassOpts))
    .pipe(postcss(css_admin.processors))
    .pipe(gulp.dest(css_admin.build))
    .pipe(browsersync ? browsersync.reload({ stream: true }) : gutil.noop());
});

// CSS public settings
var css_public = {
  src         : public_config.css_src,
  watch       : public_config.css_src,
  build       : public_config.css_dist,
  sassOpts: {
    outputStyle     : 'nested',
    imagePath       : public_config.image_dist,
    precision       : 3,
    errLogToConsole : true
  },
  processors: [
    require('autoprefixer')({
      browsers: ['last 2 versions', '> 2%']
    }),
    require('css-mqpacker'),
    require('cssnano')
  ]
};
// CSS public processing
gulp.task('public-css', async () => {
  return gulp.src(css_public.src)
    .pipe(sass(css_public.sassOpts))
    .pipe(postcss(css_public.processors))
    .pipe(gulp.dest(css_public.build))
    .pipe(browsersync ? browsersync.reload({ stream: true }) : gutil.noop());
});

// JavaScript admin processing
gulp.task('admin-js', () => {
  return gulp.src(admin_config.js_src)
    .pipe(deporder())
    .pipe(concat(admin_config.filename))
    .pipe(stripdebug())
    .pipe(uglify())
    .pipe(gulp.dest(admin_config.js_dist))
    .pipe(browsersync ? browsersync.reload({ stream: true }) : gutil.noop());
});

// JavaScript public processing
gulp.task('public-js', () => {
  return gulp.src(public_config.js_src)
    .pipe(deporder())
    .pipe(concat(public_config.filename))
    .pipe(stripdebug())
    .pipe(uglify())
    .pipe(gulp.dest(public_config.js_dist))
    .pipe(browsersync ? browsersync.reload({ stream: true }) : gutil.noop());
});

//Optimise images
gulp.task('admin-imagemin', async () => {
    gulp.src(admin_config.image_src)
    .pipe(newer(admin_config.image_dist))
    .pipe(imagemin())
    .pipe(gulp.dest(admin_config.image_dist))
});
gulp.task('public-imagemin', async () => {
    gulp.src(public_config.image_src)
    .pipe(newer(public_config.image_dist))
    .pipe(imagemin())
    .pipe(gulp.dest(public_config.image_dist))
});

//create pot file
var info = require('./package.json');
gulp.task('pot-task', function () {

    return gulp.src('**/*.php')
        .pipe(wpPot( {
            domain: info.name,
            package: info.name
       } ))
        .pipe(gulp.dest('languages/'+info.name+'.pot'));
});

//browser sync
var reload = browsersync.reload;
gulp.task('browser-sync', function() {
  browsersync.init({
    proxy: 'http://makewebbetter.local/', //change it to your local environment base url,
    files: [admin_config.dist, public_config.dist]
  });
});

gulp.task('reload', function () {
  browsersync.reload();
});
gulp.task('build', gulp.series(['admin-css', 'admin-js', 'admin-imagemin','public-css', 'public-js', 'public-imagemin', 'pot-task']));

//watch for file changes
gulp.task('watch', gulp.series(['browser-sync']), function() {
  // check changes
  gulp.watch(admin_config.src, ['admin-imagemin', 'admin-css', 'admin-js']);
  gulp.watch(public_config.src, ['public-imagemin', 'public-css', 'public-js']);
});

// default task
gulp.task('default', gulp.series(['build', 'watch']));
