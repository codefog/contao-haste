const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/')
    .setPublicPath('/bundles/codefoghaste')
    .setManifestKeyPrefix('')
    .disableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps()
    .enableVersioning()

    .addEntry('ajax-reload', './assets/ajax-reload.js')
    .addEntry('dca-ajax-operations', './assets/dca-ajax-operations.js')
    .copyFiles({
        from: './assets',
        to: '[name].[hash:8].[ext]',
        pattern: /\.(css)$/,
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    .enablePostCssLoader()
;

module.exports = Encore.getWebpackConfig();
