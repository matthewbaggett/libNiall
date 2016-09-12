<?php
namespace Niall\Mind\Models;

use \Thru\ActiveRecord\ActiveRecord;

/**
 * Class NiallWordRelationship
 * @package Niall\Mind
 * @var $word_relationship_id integer
 * @var $word_parent_id integer
 * @var $word_child_id integer
 * @var $created datetime
 * @var $freq integer
 */
class NiallWordRelationship extends ActiveRecord
{
    protected $_table = "niall_word_relationship";
    public $word_relationship_id;
    public $word_parent_id;
    public $word_child_id;
    public $created;
    public $freq = 0;
}
