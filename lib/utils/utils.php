<?

namespace Welpodron\Optimizer;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Config\Option;

class Utils
{
    const DEFAULT_MODULE_ID = 'welpodron.optimizer';

    private static function minifyHTML($buffer)
    {
        //remove redundant (white-space) characters
        $replace = array(
            //remove tabs before and after HTML tags
            '/\>[^\S ]+/s'   => '>',
            '/[^\S ]+\</s'   => '<',
            //shorten multiple whitespace sequences; keep new-line characters because they matter in JS!!!
            '/([\t ])+/s'  => ' ',
            //remove leading and trailing spaces
            '/^([\t ])+/m' => '',
            '/([\t ])+$/m' => '',
            // remove JS line comments (simple only); do NOT remove lines containing URL (e.g. 'src="http://server.com/"')!!!
            '~//[a-zA-Z0-9 ]+$~m' => '',
            //remove empty lines (sequence of line-end and white-space characters)
            '/[\r\n]+([\t ]?[\r\n]+)+/s'  => "\n",
            //remove empty lines (between HTML tags); cannot remove just any line-end characters because in inline JS they can matter!
            '/\>[\r\n\t ]+\</s'    => '><',
            //remove "empty" lines containing only JS's block end character; join with next line (e.g. "}\n}\n</script>" --> "}}</script>"
            '/}[\r\n\t ]+/s'  => '}',
            '/}[\r\n\t ]+,[\r\n\t ]+/s'  => '},',
            //remove new-line after JS's function or condition start; join with next line
            '/\)[\r\n\t ]?{[\r\n\t ]+/s'  => '){',
            '/,[\r\n\t ]?{[\r\n\t ]+/s'  => ',{',
            //remove new-line after JS's line end (only most obvious and safe cases)
            '/\),[\r\n\t ]+/s'  => '),',
            //remove quotes from HTML attributes that does not contain spaces; keep quotes around URLs!
            '~([\r\n\t ])?([a-zA-Z0-9]+)="([a-zA-Z0-9_/\\-]+)"([\r\n\t ])?~s' => '$1$2=$3$4', //$1 and $4 insert first white-space character found before/after attribute
        );

        $body = preg_replace(array_keys($replace), array_values($replace), $buffer);

        $search = array(
            '/(\n|^)(\x20+|\t)/',
            '/(\n|^)\/\/(.*?)(\n|$)/',
            '/\n/',
            '/\<\!--.*?-->/',
            '/(\x20+|\t)/', # Delete multispace (Without \n)
            '/\>\s+\</', # strip whitespaces between tags
            '/(\"|\')\s+\>/', # strip whitespaces between quotation ("') and end tags
            '/=\s+(\"|\')/'
        ); # strip whitespaces between = "'

        $replace = array(
            "\n",
            "\n",
            " ",
            "",
            " ",
            "><",
            "$1>",
            "=$1"
        );

        $body = preg_replace($search, $replace, $body);

        //remove optional ending tags (see http://www.w3.org/TR/html5/syntax.html#syntax-tag-omission )
        $remove = array(
            '</option>', '</li>', '</dt>', '</dd>', '</tr>', '</th>', '</td>'
        );

        $body = str_ireplace($remove, '', $body);

        return $body;
    }

    public static function OnAdminListDisplay(&$list)
    {
        if (!CurrentUser::get()->isAdmin()) {
            return;
        }

        if (!Loader::includeModule(SELF::DEFAULT_MODULE_ID)) {
            return;
        }

        if ($list->table_id == "tbl_fileman_admin") {
            foreach ($list->aRows as $row) {
                $info = $row->arRes;
                // https://dev.1c-bitrix.ru/api_help/main/general/admin.section/classes/cadminlistrow/addactions.php
                if (Path::getExtension($info['ABS_PATH']) == 'css' || Path::getExtension($info['ABS_PATH']) == 'js') {
                    $row->aActions[] = [
                        "SEPARATOR" => true,
                    ];

                    $row->aActions[] = [
                        'ICON' => 'edit',
                        'TEXT' => 'Минифицировать',
                        'ACTION' => '(() => {const o = new welpodron.optimizer({sessid: "' . bitrix_sessid() .  '"}); o.optimize("' . $info['ABS_PATH'] . '");})()',
                    ];
                    // можно обрабатывать вот тут: https://dev.1c-bitrix.ru/api_help/main/events/onadminlistdisplay.php
                }
            }
        }
    }

    public static function OnEndBufferContent(&$content)
    {
        if (CurrentUser::get()->isAdmin()) {
            return $content;
        }

        if (Option::get(SELF::DEFAULT_MODULE_ID, 'USE_MINIFY_HTML') != 'Y') {
            return $content;
        }

        $content = self::minifyHTML($content);
    }
}
