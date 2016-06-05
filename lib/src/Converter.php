<?php
namespace Converter;

class Converter
{
    public $file;
    public $xml;
    public $json = array();

    public function setFile($file)
    {
        $this->file = $file;
        $this->xml = file_get_contents($file);
        return $this;
    }

    public function idNormalize($val)
    {
        return trim($val, "\t\r\n #_-");
    }

    public function getPoints()
    {
        if (!empty($this->points)) {
            return $this->points;
        }

        preg_match_all('@<gml:Point gml:id="([^"]+)">[^<]*<gml:pos>([\d\.]+) ([\d\.]+)</gml:pos>[^<]*</gml:Point>@isu', $this->xml, $m);
        if (empty($m[1])) {
            return;
        }

        $items = array();
        foreach($m[1] as $key => $id) {
            $id = $this->idNormalize($id);
            $items[$id] = array(
                (float) $m[3][$key],
                (float) $m[2][$key],
            );
        }

        return $this->points = $items;
    }

    public function getCurves()
    {
        if (!empty($this->curves)) {
            return $this->curves;
        }

        preg_match_all('@<gml:Curve gml:id="([^"]+)">.*?<gml:posList>(.*?)</gml:posList>.*?</gml:Curve>@isu', $this->xml, $m);
        if (empty($m[2])) {
            return;
        }

        $items = array();
        foreach($m[1] as $key => $id) {
            $id = trim($id, "\t\r\n #_-");
            $list = str_replace(array("\t", "\r"), "", $m[2][$key]);
            $list = trim($list);
            $arr = array();
            foreach(explode("\n", $list) as $v) {
                $v = trim($v);
                list($lat, $lon) = explode(" ", $v);
                $arr[] = array((float) $lon, (float) $lat);
            }
            $items[$id] = $arr;
        }

        return $this->curves = $items;
    }

    public function getSurfaces()
    {
        if (!empty($this->surfaces)) {
            return $this->surfaces;
        }

        if (!$curves = $this->getCurves()) {
            throw new \Exception('Can\'t get Curves!');
        }

        preg_match_all('@<gml:Surface gml:id="([^"]+)">.*?<gml:Ring>(.*?)</gml:Ring>.*?</gml:Surface>@isu', $this->xml, $m);
        if (empty($m[1])) {
            return;
        }

        $items = array();
        foreach($m[1] as $key => $id) {
            $id = $this->idNormalize($id);
            $arr = array();
            preg_match_all('@<gml:curveMember xlink:href="([^"]+)"[^>]*?>@isu', $m[2][$key], $mm);
            foreach($mm[1] as $v) {
                $v = $this->idNormalize($v);
                if (!empty($curves[$v])) {
                    $arr[] = $curves[$v];
                }
            }

            $items[$id] = $arr;
        }

        return $this->surfaces = $items;
    }

    public function getProperties($type, $props)
    {
        $props = array_merge(array('type' => $type), $props);
        return $props;
    }

    public function getItems() {
        if (!empty($this->items)) {
            return $this->items;
        }

        $args = func_get_args();
        if (empty($args[0])) {
            return;
        }

        $tag = $args[0];

        preg_match_all('@<ksj:('.$tag.') gml:id="[^"]+">(.*?)</ksj:(?:'.$tag.')>@isu', $this->xml, $m);
        if (empty($m[2])) {
            throw new \Exception('Item "'.$tag.'" not found.');
        }

        foreach($m[2] as $key => $inner) {
            $links = $props = array();
            foreach(explode("\n", $inner) as $line) {
                if (preg_match('@<ksj:([a-zA-Z0-9]+) xlink:href="([^"]*)"[^/]*?/>@i', $line, $mm)) {
                    $link = $mm[1];
                    if ('area' == $link || 'bounds' == $link) {
                        $link = 'surface';
                    }
                    $links[$link] = $this->idNormalize($mm[2]);
                } elseif(preg_match('@<ksj:([a-zA-Z0-9]+)(?:[^>]+)?>([^<]*)</[^>]+>@isu', $line, $mm)) {
                    if (!empty($props[$mm[1]])) {
                        if (is_array($props[$mm[1]])) {
                            $props[$mm[1]][] = $mm[2];
                        } else {
                            $props[$mm[1]] = array($mm[2]);
                        }
                    } else {
                        $props[$mm[1]] = $mm[2];
                    }
                }
            }

            if (!empty($props)) {
                $items[] = array(
                    'type' => $m[1][$key],
                    'props' => $props,
                    'links' => $links,
                );
            }
        }

        return $this->items = $items;
    }

    public function getDatas()
    {
        if (!empty($this->datas)) {
            return $this->datas;
        }

        if (!$items = $this->getItems()) {
            throw new \Exception('Items not found.');
        }

        $points = $this->getPoints();
        $surfaces = $this->getSurfaces();


        $datas = array();
        foreach($items as $val) {
            $item = array(
                'type' => 'Feature',
                'geometry' => array(
                    'type' => 'GeometryCollection',
                    'geometries' => array(),
                ),
                'properties' => $this->getProperties($val['type'], $val['props']),
            );

            if (!empty($val['links'])) {
                if (!empty($val['links']['position']) && !empty($points[$val['links']['position']])) {
                    $item['geometry']['geometries'][] = array(
                        'type' => 'Point',
                        'coordinates' => $points[$val['links']['position']],
                    );
                }

                if (!empty($val['links']['surface']) && !empty($surfaces[$val['links']['surface']])) {
                    $item['geometry']['geometries'][] = array(
                        'type' => 'MultiPolygon',
                        'coordinates' => array($surfaces[$val['links']['surface']]),
                    );
                }
            }

            $datas[] = $item;
        }

        return $this->datas = $datas;
    }

    public function getCollection()
    {
        cli()->gray('Get JSON Data...');
        $datas = $this->getDatas();
        if (!empty($datas)) {
            $data = array(
                'type' => 'FeatureCollection',
                'features' => $datas
            );

            return $data;
        } else {
            throw new \Exception('Convert JSON failed.');
        }
    }
}
