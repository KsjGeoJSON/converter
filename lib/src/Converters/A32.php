<?php
namespace Converter\Converters;

class A32 extends \Converter\Converter
{
    public $dir = 'MiddleSchoolDistrict';

    public function getProperties($type, $props)
    {
        $data = array(
            'type' => $type,
            'pref_id' => substr($props['administrativeArea'], 0, 2),
            'city_id' => $props['administrativeArea'],
            'establish' => $props['installationSubject'],
            'name' => $props['name'],
        );

        return $data;
    }

    public function getItems($tag = null)
    {
        $items = parent::getItems('PublicJuniorHighSchoolArea');
        return $items;
    }
}
