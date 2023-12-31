<?

use Bitrix\Main\Loader;

//! ОБЯЗАТЕЛЬНО 

Loader::registerAutoLoadClasses(
    'welpodron.optimizer',
    [
        'Welpodron\Optimizer\Utils' => 'lib/utils/utils.php',
    ]
);

CJSCore::RegisterExt('welpodron.optimizer.csso', [
    'js' => '/bitrix/js/welpodron.optimizer/csso/csso.js',
    'use' => CJSCore::USE_ADMIN,
    'skip_core' => true
]);

CJSCore::RegisterExt('welpodron.optimizer.uglifyjs', [
    'js' => '/bitrix/js/welpodron.optimizer/uglifyjs/uglifyjs.js',
    'use' => CJSCore::USE_ADMIN,
    'skip_core' => true
]);

CJSCore::RegisterExt('welpodron.optimizer', [
    'js' => '/bitrix/js/welpodron.optimizer/optimizer/script.js',
    'rel' => ['welpodron.optimizer.csso', 'welpodron.optimizer.uglifyjs'],
    'use' => CJSCore::USE_ADMIN,
    'skip_core' => true
]);

CJSCore::init(['welpodron.optimizer']);
