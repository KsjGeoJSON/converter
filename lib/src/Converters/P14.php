<?php
namespace Converter\Converters;

class P14 extends \Converter\Converter
{
    public $dir = 'WelfareInstitution';

    public function getItems()
    {
        $items = parent::getItems('WelfareInstitution');
        return $items;
    }
}
