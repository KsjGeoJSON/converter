<?php
namespace Converter\Converters;

class A38 extends \Converter\Converter
{
    public $dir = 'MedicalArea';

    // public function getProperties($type, $props)
    // {
    //     $data = array(
    //         'type' => $type,
    //         'pref_id' => substr($props['administrativeArea'], 0, 2),
    //         'city_id' => $props['administrativeArea'],
    //         'establish' => $props['installationSubject'],
    //         'name' => $props['name'],
    //     );
    //
    //     return $data;
    // }

    public function getItems($tag = null)
    {
        $items = parent::getItems('FirstMedicalArea|SecondMedicalArea');
        // $items = array_merge($first, $second);
        return $items;
    }
}
