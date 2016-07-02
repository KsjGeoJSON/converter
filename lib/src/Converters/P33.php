<?php
namespace Converter\Converters;

class P33 extends \Converter\Converter
{
    public $dir = 'AttractCustomersFacility';

    public function getItems()
    {
        $items = parent::getItems('AttractCustomersFacility');
        return $items;
    }
}
