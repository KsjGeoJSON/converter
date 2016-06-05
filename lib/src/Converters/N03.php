<?php
namespace Converter\Converters;

class N03 extends \Converter\Converter
{
    public $dir = 'AdministrativeBoundary';

    public function getProperties($type, $props)
    {
        $data = array(
            'type' => $type,
            'pref_id' => substr($props['administrativeAreaCode'], 0, 2),
            'pref_name' => $props['prefectureName'],
            'city_code' => $props['administrativeAreaCode'],
            'city_name' => $props['countyName'].$props['cityName']
        );

        return $data;
    }

    public function getItems()
    {
        $items = parent::getItems('AdministrativeBoundary');
        return $items;
    }
}
