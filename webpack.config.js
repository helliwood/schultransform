var Encore = require('@symfony/webpack-encore');
var path = require("path");

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build_frontend/')
    // public path used by the web server to access the output path
    .setPublicPath('/build_frontend')
    // only needed for CDN's or sub-directory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Add 1 entry for each "page" of your app
     * (including one that's included on every page - e.g. "app")
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if you JavaScript imports CSS.
     */
    .addEntry('frontend', './assets/js/app.js')
    .addEntry('forBackend', './assets/js/backend.js')
    //.addEntry('content-tree-bundle', './assets/bundle_assets/ContentTreeBundle/js/main.js')
    //.addEntry('QuestionType', './assets/js/form/QuestionType.js')
    //.addEntry('page1', './assets/js/page1.js')
    //.addEntry('page2', './assets/js/page2.js')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()


    .copyFiles({
        from: './assets/images',
        to: 'images_frontend/[path][name].[ext]',
        pattern: /\.(png|jpg|jpeg|svg)$/
    })
    .copyFiles({
        from: './assets/misc',
        to: 'misc/[path][name].[ext]',
        pattern: /\.(xsl|jpg|jpeg|svg)$/
    })


    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    .enableVueLoader(() => {
    }, {runtimeCompilerBuild: true})
    .enableTypeScriptLoader(function (tsConfig) {
        // You can use this callback function to adjust ts-loader settings
        // https://github.com/TypeStrong/ts-loader/blob/master/README.md#loader-options
        // For example:
        // tsConfig.silent = false
    })

    // enables Sass/SCSS support
    .enableSassLoader()

// uncomment if you use TypeScript
//.enableTypeScriptLoader()

// uncomment if you're having problems with a jQuery plugin
// .autoProvidejQuery()

;
let config = Encore.getWebpackConfig();
config.name = 'build_frontend';

config.resolve.alias["~"] = path.resolve(__dirname, './node_modules/');
// config.resolve.alias["contenttreebundle"] = path.resolve(__dirname, '../Bundle/ContentTreeBundle/Resources/public/');
config.resolve.symlinks = false;
//config.resolve.alias["@core"] = path.resolve(__dirname, './assets');
module.exports = config;
