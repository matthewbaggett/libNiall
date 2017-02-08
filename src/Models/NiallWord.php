<?php
namespace Niall\Mind\Models;

use \Thru\ActiveRecord\ActiveRecord;

/**
 * Class NiallWord
 * @package Niall\Mind
 * @var $word_id integer
 * @var $word varchar(128)
 * @var $frequency_seen INTEGER;
 * @var $frequency_used INTEGER;
 * @var $can_start enum("Yes","No")
 * @var $can_end enum("Yes","No")
 * @var $created datetime
 * @var $score integer
 * @var $checked datetime
 */
class NiallWord extends ActiveRecord
{
    protected $_table = "niall_words";
    public $word_id;
    public $word;
    public $frequency_seen = 0;
    public $frequency_used = 0;
    public $can_start = "No";
    public $can_end = "No";
    public $created;
    public $score = 1;
    public $checked;

    public function get_child_word()
    {
        $relationship = NiallWordRelationship::search()->where('word_parent_id', $this->word_id)->order('rand()')->execOne();
        if ($relationship instanceof NiallWordRelationship) {
            $word = NiallWord::search()
                ->where('word_id', $relationship->word_child_id)
                //TODO: Check language.
                //->where('score', 0, '>')
                ->where('checked', date("Y-m-d", strtotime("last month")), '>')
                ->execOne();
            return $word;
        } else {
            return false;
        }
    }

    public function __toString()
    {
        return $this->word;
    }

    /**
     * @return NiallLanguage[]
     * @throws \Thru\ActiveRecord\Exception
     */
    public function getLanguages()
    {
        $langs = [];
        $links = NiallWordLanguage::search()->where('word_id', $this->word_id)->exec();
        if (count($links) > 0) {
            foreach ($links as $link) {
                /** @var $link NiallWordLanguage */
                $langs[] = $link->getLang();
            }
        }
        return $langs;
    }

    /**
     * @return string
     */
    public function getLanguageList()
    {
        $langList = '';
        foreach ($this->getLanguages() as $language) {
            $langList .= $language->language . ", ";
        }
        $langList = trim($langList);
        $langList = trim($langList, ",");
        return $langList;
    }

    private function getDictionaries()
    {
        $dicts = explode(PHP_EOL, rtrim(`aspell dicts`));
        $dictionariesAvailable = [];
        foreach ($dicts as $potentialDict) {
            if (strpos($potentialDict, "_") === false) {
                $dictionariesAvailable[] = $potentialDict;
            }
        }
        $dictionaries = [];
        foreach ($dictionariesAvailable as $availableDictionary) {
            $dictionaries[$availableDictionary] = [
                pspell_new($availableDictionary),
                NiallLanguage::Upsert($availableDictionary)
            ];
        }
        return $dictionaries;
    }

    public function interpret()
    {
        $dictionaries = $this->getDictionaries();

        $score = 1;

        // Step 1. Minimum length. Single characters that are not "a" and "i" are rejected.
        if (strlen($this->word) == 1) {
            if (!in_array(strtolower($this->word), ['a', 'i'])) {
                $score--;
            }
        }

        // Step 2. Scan Dictionary.
        $matchesDict = false;
        foreach ($dictionaries as $lang => $thing) {
            /** @var $language NiallLanguage */
            list($dict, $language) = $thing;
            if (pspell_check($dict, $this->word)) {
                $matchesDict = true;
                $wordLanguage = new NiallWordLanguage();
                $wordLanguage->word_id = $this->word_id;
                $wordLanguage->language_id = $language->language_id;
                $wordLanguage->save();
            }
        }
        if (!$matchesDict) {
            $score--;
        }

        $this->score = $score;

        $this->checked = date("Y-m-d H:i:s");
        $this->save();
    }
}
