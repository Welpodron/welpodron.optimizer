<?

use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\Config\Option;

//! TODO: Добавить поддержку конвертации изображений и их сжатия через данное меню в welpodron.image
//! TODO: Добавить поддержку запуска скриптов оптимизации через Node.js
class welpodron_optimizer extends CModule
{
    private $DEFAULT_OPTIONS = [];

    public function __construct()
    {
        $this->MODULE_ID = 'welpodron.optimizer';
        $this->MODULE_VERSION = '1.0.0';
        $this->MODULE_NAME = 'Модуль для оптимизации файлов (welpodron.optimizer)';
        $this->MODULE_DESCRIPTION = 'Модуль для оптимизации файлов';
        $this->PARTNER_NAME = 'Welpodron';
        $this->PARTNER_URI = 'https://github.com/Welpodron';

        $this->DEFAULT_OPTIONS = [
            'USE_MINIFY_HTML' => 'Y',
        ];
    }

    public function InstallFiles()
    {
        global $APPLICATION;

        try {
            if (!CopyDirFiles(__DIR__ . '/js/', Application::getDocumentRoot() . '/bitrix/js', true, true)) {
                $APPLICATION->ThrowException('Не удалось скопировать js');
                return false;
            };
        } catch (\Throwable $th) {
            $APPLICATION->ThrowException($th->getMessage() . '\n' . $th->getTraceAsString());
            return false;
        }

        return true;
    }

    public function UnInstallFiles()
    {
        Directory::deleteDirectory(Application::getDocumentRoot() . '/bitrix/js/' . $this->MODULE_ID);
    }

    public function InstallEvents()
    {
        $eventManager = EventManager::getInstance();

        $eventManager->registerEventHandler('main', 'OnAdminListDisplay', $this->MODULE_ID, 'Welpodron\Optimizer\Utils', 'OnAdminListDisplay');
        $eventManager->registerEventHandler('main', 'OnEndBufferContent', $this->MODULE_ID, 'Welpodron\Optimizer\Utils', 'OnEndBufferContent');
    }

    public function UnInstallEvents()
    {
        $eventManager = EventManager::getInstance();

        $eventManager->unRegisterEventHandler('main', 'OnAdminListDisplay', $this->MODULE_ID, 'Welpodron\Optimizer\Utils', 'OnAdminListDisplay');
        $eventManager->unRegisterEventHandler('main', 'OnEndBufferContent', $this->MODULE_ID, 'Welpodron\Optimizer\Utils', 'OnEndBufferContent');
    }

    public function InstallOptions()
    {
        global $APPLICATION;

        try {
            foreach ($this->DEFAULT_OPTIONS as $optionName => $optionValue) {
                Option::set($this->MODULE_ID, $optionName, $optionValue);
            }
        } catch (\Throwable $th) {
            $APPLICATION->ThrowException($th->getMessage() . '\n' . $th->getTraceAsString());
            return false;
        }
        return true;
    }

    public function UnInstallOptions()
    {
        global $APPLICATION;

        try {
            foreach ($this->DEFAULT_OPTIONS as $optionName => $optionValue) {
                Option::delete($this->MODULE_ID, ['name' => $optionName]);
            }
        } catch (\Throwable $th) {
            $APPLICATION->ThrowException($th->getMessage() . '\n' . $th->getTraceAsString());
            return false;
        }
        return true;
    }

    public function DoInstall()
    {
        global $APPLICATION;

        if (!CheckVersion(ModuleManager::getVersion('main'), '14.00.00')) {
            $APPLICATION->ThrowException('Версия главного модуля ниже 14.00.00');
            return false;
        }

        if (!$this->InstallFiles()) {
            return false;
        }

        if (!$this->InstallOptions()) {
            return false;
        }

        $this->InstallEvents();

        ModuleManager::registerModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile('Установка модуля ' . $this->MODULE_ID, __DIR__ . '/step.php');
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $this->UnInstallFiles();

        $this->UnInstallOptions();

        $this->UnInstallEvents();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile('Деинсталляция модуля ' . $this->MODULE_ID, __DIR__ . '/unstep.php');
    }
}
