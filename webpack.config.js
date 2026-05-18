const crypto = require("crypto");
const crypto_orig_createHash = crypto.createHash;
crypto.createHash = algorithm => crypto_orig_createHash(algorithm == "md4" ? "sha256" : algorithm);

var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('web/build/')
    .setPublicPath('/build')
    .autoProvidejQuery()
    .autoProvideVariables({
        "window.Bloodhound": require.resolve('bloodhound-js'),
        "jQuery.tagsinput": "bootstrap-tagsinput"
    })
    .enableSassLoader(function(options) {
        options.sourceMap = true;
    }, {
        resolveUrlLoader: true
    })
    .cleanupOutputBeforeBuild()
    .enableVersioning(Encore.isProduction())
    .disableSingleRuntimeChunk()
    .createSharedEntry('js/common', './web/assets/js/common.js')
    .addEntry('js/app', './web/assets/js/front/app.js')
    .addEntry('js/admin', './web/assets/js/admin/admin.js')
    .addEntry('js/search', './web/assets/js/admin/search.js')
    .addEntry('js/login', './web/assets/js/admin/login.js')
    .addStyleEntry('css/app', ['./web/assets/scss/front/app.scss'])
    .addStyleEntry('css/first', ['./web/assets/scss/front/first.scss'])
    .addStyleEntry('css/admin', ['./web/assets/scss/admin/admin.scss'])
    .addStyleEntry('css/ckeditor-content', ['./web/assets/scss/admin/ckeditor-content.scss'])
;

module.exports = Encore.getWebpackConfig();
