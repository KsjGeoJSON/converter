<?php
set_time_limit(0);
ini_set('memory_limit', '712M');
include(__DIR__.'/lib/vendor/autoload.php');
include(__DIR__.'/lib/functions.php');

if (PHP_SAPI != 'cli') {
    header('Content-Type: text/plain');
}

$args = array();
if (!empty($argv[1]) || !empty($_GET)) {
    if (empty($argv[1]) && !empty($_GET)) {
        $argv = array_merge(array(__FILE__), array_values($_GET));
    }

    $args = array();
    foreach($argv as $val) {
        if ('-f' === $val) {
            $args['force'] = true;
        } elseif (preg_match('/\A\-m(\d+)/i', $val, $m)) {
            ini_set('memory_limit', $m[1].'M');
        } elseif ('-pretty' === $val) {
            $args['pretty'] = true;
        } elseif(class_exists('\\Converter\\Converters\\'.$val)) {
            $args['id'] = $val;
        } elseif(is_numeric($val) && $val >= 1 && $val <= 47) {
            $args['pref'] = $val;
        }
    }
}

if (empty($args['id'])) {
    if (PHP_SAPI == 'cli') {
        cli()->lightRed('Invalid type ID.');
    } else {
        echo 'Invalid type ID.';
    }
    exit;
}

try {
    $dir = $md = null;

    foreach(range(1, 47) as $pref) {
        if (!empty($args['pref']) && $args['pref'] != $pref) {
            continue;
        }

        cli()->lightBlue('Start: id='.$args['id'].', pref='.$pref);

        $api = new \Converter\Api($args['id'], $pref);
        $dir = $api->getDirectory();
        $file = $dir.$api->pref.'.geojson';

        if (empty($args['force']) && is_file($file)) {
            cli()->yellow('Pref:'.$pref.' Skipped. (JSON already exist)');
            cli()->br();
            continue;
        }

        $url = $api->getUrl();
        $files = $api->unZip($url['url']);
        $converter = $api->getConverter(current($files));
        $collection = $converter->getCollection();

        cli()->gray('Save JSON: '.$file);

        if (!empty($args['pretty'])) {
            $json = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $json = json_encode($collection);
        }

        if (!is_dir($dir)) {
            mkdir($dir, true, 0777);
            @chmod($dir, 0777);
        }
        
        file_put_contents($file, $json);

        cli()->lightGreen('Success! id='.$api->id.', pref='.$api->pref);
        cli()->br();

        if (empty($md)) {
            $md .= '# [国土数値情報 '.$url['title'].']';
            $md .= '(http://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-'.$api->id.'.html)'.PHP_EOL.PHP_EOL;
            $md .= 'データ年: '.$url['year'].'年'.PHP_EOL;
        }
    }

    if (!empty($md)) {
        file_put_contents($dir.'README.md', $md);
    }
} catch(\Exception $e) {
    if (PHP_SAPI == 'cli') {
        cli()->lightRed($e->getMessage());
    } else {
        vd($e->getMessage(), $e->getTrace());
    }
}
