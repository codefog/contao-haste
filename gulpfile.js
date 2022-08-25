'use strict';

const gulp = require('gulp');
const uglify = require('gulp-uglify');
const rename = require('gulp-rename');

// Compile scripts
gulp.task('scripts', function () {
    return gulp.src(['public/ajax-reload.js', 'public/haste.js'])
        .pipe(uglify())
        .pipe(rename(function (path) {
            path.extname = '.min.js';
        }))
        .pipe(gulp.dest('public'));
});

// Default task
gulp.task('default', ['scripts']);
