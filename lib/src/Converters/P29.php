<?php
namespace Converter\Converters;

use Converter\Masters\Prefs as Prefs;
use Converter\Masters\Cities as Cities;
use Converter\Masters\PublicFacilityLargeClass as LargeClass;
use Converter\Masters\PublicFacilitySmallClass as SmallClass;
use Converter\Masters\PublicFacilityAdministrativeCode as AdministrativeCode;

class P29 extends \Converter\Converter
{
    public $dir = 'School';

    public function getProperties($type, $props)
    {
        $pref = substr($props['administrativeArea'], 0, 2);
        $pref_name = Prefs::name($pref);
        $city = $props['administrativeArea'];
        $address = null;
        if (($city_name = Cities::name($props['administrativeArea'])) && !empty($props['address'])) {
            $address = $pref_name.$city_name.$props['address'];
        }
        $data = array(
            'type' => $type,
            'name' => $props['name'],
            'address' => $address,
            'pref_id' => $pref,
            'pref_name' => $pref_name,
            'city_id' => $city,
            'city_name' => $city_name,
            'large_class_id' => $props['publicFacilityLargeClassification'],
            'large_class_name' => LargeClass::name($props['publicFacilityLargeClassification']),
            'small_class_id' => $props['publicFacilitySmallClassification'],
            'small_class_name' => SmallClass::name($props['publicFacilitySmallClassification']),
            'administrative_id' => $props['administrativeCode'],
            'administrative_name' => AdministrativeCode::name($props['administrativeCode']),
        );

        return $data;
    }

    public function getItems()
    {
        $items = parent::getItems('School');
        return $items;
    }
}
