<?php
namespace Niall\Mind\Models;

use \Thru\ActiveRecord\ActiveRecord;

/**
 * Class NiallWordLanguage
 * @package Niall\Mind
 * @var $word_language_id integer
 * @var $language_id integer
 * @var $word_id integer
 */
class NiallWordLanguage extends ActiveRecord
{
    protected $_table = "niall_word_languages";
    public $word_language_id;
    public $language_id;
    public $word_id;

    /**
     * @return NiallLanguage
     * @throws \Thru\ActiveRecord\Exception
     */
    public function getLang()
    {
        return NiallLanguage::search()->where('language_id', $this->language_id)->execOne();
    }
}
