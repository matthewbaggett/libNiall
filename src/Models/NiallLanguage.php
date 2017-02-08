<?php
namespace Niall\Mind\Models;

use \Thru\ActiveRecord\ActiveRecord;

/**
 * Class NiallLanguage
 * @package Niall\Mind
 * @var $language_id integer
 * @var $language varchar(128)
 */
class NiallLanguage extends ActiveRecord
{
    protected $_table = "niall_languages";
    public $language_id;
    public $language;

    /**
     * @param $name
     * @return NiallLanguage
     * @throws \Thru\ActiveRecord\Exception
     */
    public static function Upsert($name)
    {
        $lang = self::search()->where('language', $name)->execOne();
        if (!$lang instanceof self) {
            $lang = new self();
            $lang->language = $name;
            $lang->save();
        }
        return $lang;
    }
}
