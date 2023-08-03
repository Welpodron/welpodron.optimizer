<?

namespace Welpodron\Optimizer\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Application;
use Bitrix\Main\IO\File;


class Receiver extends Controller
{
    const DEFAULT_MODULE_ID = 'welpodron.optimizer';
    //! Данный метод обязателен если мы не хотим получить invalid_authentication https://qna.habr.com/q/1043030
    protected function getDefaultPreFilters()
    {
        return [];
    }

    // Вызов из BX.ajax.runAction - welpodron:optimizer.Receiver.load
    public function loadAction()
    {
        try {
            if (!CurrentUser::get()->isAdmin()) {
                throw new \Exception('Действие запрещено');
            }

            $request = $this->getRequest();
            $arDataRaw = $request->getPostList()->toArray();

            // Данные должны содержать идентификатор сессии битрикса 
            if ($arDataRaw['sessid'] !== bitrix_sessid()) {
                throw new \Exception('Неверный идентификатор сессии');
            }

            $path = $arDataRaw['path'];

            $file = new File(Application::getDocumentRoot() .  $path);

            if (!$file->isExists()) {
                throw new \Exception('Файл не найден');
            }

            if ($file->getExtension() !== 'css' && $file->getExtension() !== 'js') {
                throw new \Exception('Недопустимый формат файла');
            }

            return [
                'FILE_CONTENT' => $file->getContents(),
                'FILE_DIRECTORY' => pathinfo($path, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR,
                'FILE_EXT' => $file->getExtension(),
                'FILE_NAME' => pathinfo($file->getName(), PATHINFO_FILENAME),
            ];
        } catch (\Throwable $th) {
            $this->addError(new Error($th->getMessage(), $th->getCode()));
            return;
        }
    }

    // Вызов из BX.ajax.runAction - welpodron:optimizer.Receiver.save
    public function saveAction()
    {
        try {
            if (!CurrentUser::get()->isAdmin()) {
                throw new \Exception('Действие запрещено');
            }

            $request = $this->getRequest();
            $arDataRaw = $request->getPostList()->toArray();

            // Данные должны содержать идентификатор сессии битрикса 
            if ($arDataRaw['sessid'] !== bitrix_sessid()) {
                throw new \Exception('Неверный идентификатор сессии');
            }

            $path = $arDataRaw['file_path'];

            if (File::putFileContents(Application::getDocumentRoot() . $path, $arDataRaw['file_content']) === false) {
                throw new \Exception('Не удалось сохранить файл ' . $path);
            }

            $path = $arDataRaw['map_path'];

            if ($path) {
                if (File::putFileContents(Application::getDocumentRoot() . $path, $arDataRaw['map_content']) === false) {
                    throw new \Exception('Не удалось сохранить файл ' . $path);
                }
            }
        } catch (\Throwable $th) {
            $this->addError(new Error($th->getMessage(), $th->getCode()));
            return;
        }
    }
}
