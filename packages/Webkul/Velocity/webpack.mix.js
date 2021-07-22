const { mix } = require("laravel-mix");
require("laravel-mix-merge-manifest");

let publicPath = "../../../public/themes/velocity/assets";

if (mix.inProduction()) {
    publicPath = 'publishable/assets';
}

mix.setPublicPath(publicPath).mergeManifest();
mix.disableNotifications();

mix
    .copy(__dirname + "/src/Resources/assets/images", publicPath + "/images")
    
    .js(
        __dirname + "/src/Resources/assets/js/app.js",
        "js/velocity.js"
    )

    .sass(
        __dirname + '/src/Resources/assets/sass/admin.scss',
        __dirname + '/' + publicPath + '/css/velocity-admin.css'
    )
    .sass(
        __dirname + '/src/Resources/assets/sass/app.scss',
        __dirname + '/' + publicPath + '/css/velocity.css', {
            includePaths: ['node_modules/bootstrap-sass/assets/stylesheets/'],
        }
    )
    .sass(
        __dirname + '/src/Resources/assets/sass/media/extra-large-devices.scss',
        __dirname + '/' + publicPath + '/css/velocity-xl-devices.css'
    )
    .sass(
        __dirname + '/src/Resources/assets/sass/media/large-devices.scss',
        __dirname + '/' + publicPath + '/css/velocity-l-devices.css'
    )
    .sass(
        __dirname + '/src/Resources/assets/sass/media/medium-devices.scss',
        __dirname + '/' + publicPath + '/css/velocity-m-devices.css'
    )
    .sass(
        __dirname + '/src/Resources/assets/sass/media/small-devices.scss',
        __dirname + '/' + publicPath + '/css/velocity-sm-devices.css'
    )
    .sass(
        __dirname + '/src/Resources/assets/sass/media/very-small-devices.scss',
        __dirname + '/' + publicPath + '/css/velocity-xm-devices.css'
    )

    .options({
        processCssUrls: false
    });

if (mix.inProduction()) {
    mix.version();
}
