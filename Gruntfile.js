module.exports = function(grunt) {

	// Load plugins Just in Time/as needed (increases speed).
	require( 'jit-grunt' )(grunt);

	grunt.initConfig({
		autoprefixer: {
			dev: {
				options: {
					browsers: [
						'Android >= 2.1',
						'Chrome >= 21',
						'Explorer >= 8',
						'Firefox >= 17',
						'Opera >= 12.1',
						'Safari >= 6.0'
					]
				},
				files: {
					'assets/css/drgrpg.min.css': 'assets/css/drgrpg.css'
				}
			}
		},
		cssmin: {
			dev: {
				src: 'assets/css/drgrpg.css',
				dest: 'assets/css/drgrpg.min.css',
				options: {
					keepSpecialComments: 1
				}
			}
		},
		jshint: {
			src: [
				'assets/js/drgrpg.js',
			],
			options: {
				"node": true,
				"esnext": true,
				"curly": false,
				"smarttabs": true,
				"globals": {
					"jQuery": true
				}
			}
		},
		uglify: {
			options: {
				mangle: false
			},
			my_target: {
				files: {
					'assets/js/drgrpg.min.js': 'assets/js/drgrpg.js'
				}
			}
		}
	});

	grunt.registerTask( 'css', ['autoprefixer:dev', 'cssmin:dev'] );
	grunt.registerTask( 'js', ['jshint', 'uglify'] );
}
