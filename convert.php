<?php
set_time_limit(0);
ini_set('memory_limit', '712M');
include(__DIR__.'/lib/vendor/autoload.php');
include(__DIR__.'/lib/functions.php');

use Converter\Masters\Prefs;

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

        cli()->br();
        cli()->lightBlue()->inline('Start: id='.$args['id'].', pref='.$pref);

        $api = new \Converter\Api($args['id'], $pref);
        $dir = $api->getDirectory();
        $file = $dir.$api->pref.'.geojson';

        if (empty($args['force']) && is_file($file)) {
            cli()->yellow()->inline(' Skipped. (JSON already exist)');
            // cli()->br();
            continue;
        }

        cli()->br();

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
        // cli()->br();
    }

    if (empty($md)) {
        cli()->br();
        cli()->yellow('Building README.md...');
        $api = new \Converter\Api($args['id'], '13');
        $url = $api->getUrl();
        $md .= '# [国土数値情報 '.$url['title'].']';
        $md .= '(http://nlftp.mlit.go.jp/ksj/gml/datalist/KsjTmplt-'.$api->id.'.html)'.PHP_EOL.PHP_EOL;
        $md .= 'データ年: '.$url['year'].'年'.PHP_EOL;
        $md .= PHP_EOL.'-----'.PHP_EOL.PHP_EOL;
        foreach(scandir($dir) as $val) {
            if (preg_match('/\A(\d{2})\.geojson\z/i', $val, $m)) {
                $bytes = filesize($dir.$val);
                if ($bytes >= 1073741824) {
                    $bytes = number_format($bytes / 1073741824, 2) . 'GB';
                } elseif ($bytes >= 1048576) {
                    $bytes = number_format($bytes / 1048576, 2) . 'MB';
                } elseif ($bytes >= 1024) {
                    $bytes = number_format($bytes / 1024, 2) . 'KB';
                } elseif ($bytes > 1) {
                    $bytes = $bytes . 'bytes';
                }
                $md .= '- ['.Prefs::name($m[1]).' ('.$bytes.')](./'.$api->json_dir.'/'.$val.')'.PHP_EOL;
            }
        }

        file_put_contents(dirname($dir).DIRECTORY_SEPARATOR.'README.md', $md);
        cli()->lightGreen('Success!');
    }
} catch(\Exception $e) {
    if (PHP_SAPI == 'cli') {
        cli()->lightRed($e->getMessage());
    } else {
        vd($e->getMessage(), $e->getTrace());
    }
}
