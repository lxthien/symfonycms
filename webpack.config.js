const crypto = require("crypto");
const crypto_orig_createHash = crypto.createHash;
crypto.createHash = algorithm => crypto_orig_createHash(algorithm == "md4" ? "sha256" : algorithm);

var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .autoProvidejQuery()
    .autoProvideVariables({
        "window.Bloodhound": require.resolve('bloodhound-js'),
        "jQuery.tagsinput": "bootstrap-tagsinput"
    })
    .enableSassLoader(function(options) {
        options.sourceMap = true;
        options.sassOptions = {
            quietDeps: true,
            silenceDeprecations: ['legacy-js-api', 'import']
        };
    }, {
        resolveUrlLoader: true
    })
    .cleanupOutputBeforeBuild()
    .enableVersioning(Encore.isProduction())
    .enableSingleRuntimeChunk()
    .createSharedEntry('js/common', './public/assets/js/common.js')
    .addEntry('js/app', './public/assets/js/front/app.js')
    .addEntry('js/admin', './public/assets/js/admin/admin.js')
    .addEntry('js/search', './public/assets/js/admin/search.js')
    .addEntry('js/login', './public/assets/js/admin/login.js')
    .addStyleEntry('css/app', ['./public/assets/scss/front/app.scss'])
    .addStyleEntry('css/first', ['./public/assets/scss/front/first.scss'])
    .addStyleEntry('css/list', ['./public/assets/scss/front/list.scss'])
    .addStyleEntry('css/show', ['./public/assets/scss/front/show.scss'])
    .addStyleEntry('css/admin', ['./public/assets/scss/admin/admin.scss'])
    .addStyleEntry('css/ckeditor-content', ['./public/assets/scss/admin/ckeditor-content.scss'])
;

var config = Encore.getWebpackConfig();

// Use polling to avoid Node 24 libuv fs-event crash on Windows
config.watchOptions = {
    poll: 250,
    ignored: /node_modules/
};

module.exports = config;
