'use strict';

const gulp = require('gulp');
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');

// Compile scripts
gulp.task('scripts', function () {
    return gulp.src('assets/ajax-reload.js')
        .pipe(uglify())
        .pipe(rename('ajax-reload.min.js'))
        .pipe(gulp.dest('assets'));
});

// Default task
gulp.task('default', ['scripts']);
