<?php
namespace Converter;

/**
 * @see http://nlftp.mlit.go.jp/ksj/api/about_api.html
 */

class Api
{
    public $base = 'http://nlftp.mlit.go.jp/ksj/api/';
    public $version = '1.0b';
    public $app_id = 'ksjapibeta1';
    public $lang = 'J';
    public $format = '1';
    public $json_dir = 'json';
    public $id;
    public $pref;
    public $url;

    private $request_url;

    public function __construct($id, $pref)
    {
        $temp = sys_get_temp_dir().DIRECTORY_SEPARATOR;
        $temp .= md5(__CLASS__).'_temp'.DIRECTORY_SEPARATOR;
        $this->temp = $temp;

        $this->id = $id;
        $this->pref = str_pad($pref, 2, '0', STR_PAD_LEFT);

        $class = '\\'.__NAMESPACE__.'\\Converters\\'.$this->id;
        if (!class_exists($class)) {
            throw new \Exception('Converter "'.$class.'" not exists.');
        }
        $this->converter = new $class;
    }

    public function __destruct()
    {
        $this->rmdir($this->temp);
    }

    private function rmdir($dir) {
        if (!$dir = realpath($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            if (is_dir($path)) {
                $this->rmdir($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($dir);
    }

    public function request($path, array $params = array())
    {
        $params['appId'] = $this->app_id;
        $params['lang'] = $this->lang;
        $params['dataformat'] = $this->format;
        $url = $this->base.$this->version.'/index.php/app/'.$path;
        $url .= '?'.http_build_query($params);
        $this->request_url = $url;

        cli()->darkGray('Request: '.$url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        curl_close($ch);

        $xml = simplexml_load_string($res);
        return $xml;
    }

    public function getUrl($year = null)
    {
        if (null !== $this->url) {
            return $this->url;
        }

        $res = $this->request('getKSJURL.xml', array(
            'identifier' => $this->id,
            'prefCode' => $this->pref,
            'fiscalyear' => $year
        ));

        $item = null;
        if (!empty($res->KSJ_URL)) {
            if (1 == $res->NUMBER) {
                $item = $res->KSJ_URL->item;
            } else {
                $items = array();
                foreach($res->KSJ_URL->item as $val) {
                    $year = (string) $val->year;
                    $items[$year] = $val;
                }
                krsort($items);
                $item = current($items);
            }
        }

        if (empty($item) || empty($item->zipFileUrl)) {
            throw new \Exception('Zip URL not found at "'.$this->request_url.'".');
        }

        $data = array(
            'id' => (string) $item->identifier,
            'title' => (string) $item->title,
            'field' => (string) $item->field,
            'year' => (string) $item->year,
            'url' => (string) $item->zipFileUrl,
            'size' => (string) $item->zipFileSize,
        );

        return $this->url = $data;
    }

    public function getSummary()
    {
        $res = $this->request('getKSJSummary.xml');
        if (empty($res->KSJ_SUMMARY)) {
            return;
        }

        $items = array();
        foreach($res->KSJ_SUMMARY->item as $val) {
            $id = (string) $val->identifier;
            $items[$id] = (array) $val;
        }

        return $items;
    }

    private function download($url)
    {
        cli()->gray('Downloading: '.$url);

        if (!is_dir($this->temp)) {
            mkdir($this->temp, true, 0777);
        }

        $file = $this->temp.basename($url);
        if (is_file($file)) {
            return $file;
        }

        $fp = fopen($file, 'w');
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => false,
            CURLOPT_FILE => $fp,
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => array($this, 'downloadProgress')
        ));
        @curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        @chmod($file, 0777);
        return $file;
    }

    public function downloadProgress($resource, $dltotal, $dlnow, $ultotal, $ulnow)
    {
        static $progress;
        if (null === $progress && $dltotal > 0) {
            $progress = cli()->progress()->total($dltotal);
        } elseif ($progress) {
            if ($dltotal == $dlnow) {
                $progress = null;
            } else {
                $progress->current($dlnow);
            }
        }
    }

    public function unZip($url)
    {
        $file = $this->download($url);

        cli()->gray('UnZip: '.basename($url));

        $zip = new \ZipArchive;
        $res = $zip->open($file);
        if ($res === true) {
            $xmls = array();
            for($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $path = $zip->getNameIndex($i);
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                if ('xml' == strtolower($ext) && 0 === strpos(basename($path), $this->id)) {
                    $path = $this->temp.basename($path);
                    if (copy("zip://".$file."#".$entry['name'], $path)) {
                        $xmls[] = $path;
                    } else {
                        cli()->red('Failed Zip extract "'.$path.'"');
                    }
                }
            }
            $zip->close();
            if (empty($xmls)) {
                throw new \Exception('Not found XML file at "'.$file.'".');
            }

            return $xmls;
        } else {
            throw new \Exception('Zip open fail from "'.$file.'".');
        }
    }

    private function searchXml($path)
    {
        $files = array();
        if (is_dir($path)) {
            $path = trim($path, '/\\').DIRECTORY_SEPARATOR;
            foreach(scandir($path) as $dir) {
                if ('.' == $dir{0}) {
                    continue;
                }
                $res = $this->searchXml($path.$dir);
                if (!empty($res)) {
                    $files = array_merge($files, $res);
                }
            }
        } else {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if ('xml' == strtolower($ext) && 0 === strpos(basename($path), $this->id)) {
                cli()->gray(' -> '.basename($path));
                $files[] = $path;
            }
        }
        return $files;
    }

    public function getDirectory()
    {
        if (empty($this->converter->dir)) {
            throw new Exception('Directory not set at '.get_class($this->converter));
        }

        $dir = dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR;
        $dir .= $this->converter->dir.DIRECTORY_SEPARATOR;
        $dir .= $this->json_dir.DIRECTORY_SEPARATOR;
        return $dir;
    }

    public function getConverter($file)
    {
        cli()->lightGray('Convert at '.get_class($this->converter));
        return $this->converter->setFile($file);
    }
}
