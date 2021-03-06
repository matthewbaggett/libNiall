<?php
namespace Niall\Mind;

use Niall\Mind\Models;

class Niall
{
    protected $endSentenceOdds = 100; // 8 = 1 in 8 chance

    /**
     * Set odds of ending a sentence.
     * @param int $odds example 8 = 1 in 8 chance
     * @return Niall
     */
    public function setEndSentenceOdds($odds = 8)
    {
        $this->endSentenceOdds = $odds;
        return $this;
    }

    public function niall_parse_message($message)
    {
        $sentences = explode(".", $message);
        $sentences = array_filter($sentences);

        $new_words = [];
        foreach ($sentences as $sentence) {
            $oPreviousWord = null;
            $words = explode(" ", $sentence);
            $words = array_filter($words);
            // Ignore sentences shorter than 5 words long.
            if (count($words) < 5) {
                continue;
            }
            foreach ($words as $i => $word) {
                $word = trim($word);
                if ($word) {
                    $oWord = Models\NiallWord::search()
                        ->where('word', $word)
                        ->execOne();
                    if (!$oWord instanceof Models\NiallWord) {
                        $oWord = new Models\NiallWord();
                        $oWord->word = $word;
                        $oWord->created = date("Y-m-d H:i:s");
                        $oWord->checked = "1970-01-01 00:00:00";
                        $oWord->score = 0;
                        $oWord->save();
                        $oWord->interpret();
                        $new_words[] = $word;
                    }
                    $oWord->frequency_seen = $oWord->frequency_seen + 1;
                    if ($i == 0 || !isset($oPreviousWord)) {
                        $oWord->can_start = "Yes";
                    } elseif ($i == count($words) - 1) {
                        $oWord->can_end = "Yes";
                        $this->add_word_relation($oWord, $oPreviousWord);
                    } else {
                        $this->add_word_relation($oWord, $oPreviousWord);
                    }

                    $oWord->save();

                    $oPreviousWord = $oWord;
                }
            }
        }
        return $new_words;
    }

    public function add_word_relation(Models\NiallWord $oWord, Models\NiallWord $oPreviousWord)
    {
        $oWord->save();
        $oWordRelation = Models\NiallWordRelationship::search()->where('word_child_id', $oWord->word_id)->where('word_parent_id', $oPreviousWord->word_id)->execOne();
        if (!$oWordRelation instanceof Models\NiallWordRelationship) {
            $oWordRelation = new Models\NiallWordRelationship();
            $oWordRelation->word_child_id = $oWord->word_id;
            $oWordRelation->word_parent_id = $oPreviousWord->word_id;
            $oWordRelation->created = date("Y-m-d H:i:s");
            $oWordRelation->freq = 1;
        }
        $oWordRelation->freq++;
        $oWordRelation->save();
    }

    public function get_sentence()
    {
        $start_word = Models\NiallWord::search()
            ->where('can_start', 'Yes')
            //->where('score', 0, '>')
            //->where('checked', date("Y-m-d", strtotime("last month")), '>')
            ->order('rand()')
            ->execOne();
        $finished = false;
        /**
         * @var $words Models\NiallWord[]
         */
        $words[] = $start_word;
        while ($finished == false && count($words) > 0 && end($words) != false) {
            $next_word = end($words)->get_child_word();
            if (!$next_word instanceof Models\NiallWord) {
                $finished = true;
            } else {
                if ($next_word->can_end) {
                    if (rand(1, $this->endSentenceOdds) == 1) {
                        $finished = true;
                    }
                }
                $words[] = $next_word;
            }
        }

        $words = array_filter($words);

        if (count($words) > 0) {
            foreach ($words as $word) {
                $word->frequency_used = $word->frequency_used > 0 ? $word->frequency_used + 1 : 1;
                $word->save();
            }
        }

        $reply = implode(" ", $words) . ".";
        #!\Kint::dump($reply, $words);
        #exit;
        return [$reply, $words];
    }
}
