<?php
namespace Converter\Converters;

class A27 extends \Converter\Converter
{
    public $dir = 'ElementarySchoolDistrict';

    public function getProperties($type, $props)
    {
        $data = array(
            'type' => $type,
            'pref_id' => substr($props['administrativeAreaCode'], 0, 2),
            'city_id' => $props['administrativeAreaCode'],
            'establish' => $props['establishmentBody'],
            'name' => $props['name'],
            'address' => $props['address'],
        );

        return $data;
    }

    public function getItems()
    {
        $items = array();
        foreach(parent::getItems('PublicElementarySchool|SchoolDistrict') as $val) {
            $id = implode('', $val['props']);
            if (!empty($_items[$id])) {
                $items[$id]['links'] = array_merge($items[$id]['links'], $val['links']);
            } else {
                $items[$id] = $val;
            }
        }

        return array_values($items);
    }
}
