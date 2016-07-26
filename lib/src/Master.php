<?php
namespace Converter;

class Master
{
    public static function get($id)
    {
        if (!empty(static::$datas[$id])) {
            return array(
                'id' => $id,
                'name' => static::$datas[$id],
            );
        }
    }

    public static function name($val)
    {
        if (!empty(static::$datas[$val])) {
            return static::$datas[$val];
        } elseif(false !== array_search($val, static::$datas)) {
            return $val;
        } else {
            cli()->lightRed('"'.$val.'" not found! at '.get_called_class());
        }
    }

    public static function id($val)
    {
        if (!empty(static::$datas[$id])) {
            return static::$datas[$id];
        } elseif(false !== ($key = array_search(static::$datas, $val))) {
            return $key;
        }
    }
}
