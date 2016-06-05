<?php
class EmptyClass
{
    public function __call($name, $args)
    {
        return $this;
    }
}

function cli()
{
    static $instance = null;

    if (PHP_SAPI != 'cli') {
        if (null === $instance) {
            $instance = new EmptyClass();
        }
        
        return $instance;
    }

    if (null === $instance) {
        $instance = new \League\CLImate\CLImate;
    }

    $args = func_get_args();
    if (!empty($args[0])) {
        $instance->out($args[0]);
    } else {
        return $instance;
    }
}

function progress($total, $now = 0)
{
    static $progress;
    if (null === $progress && $total > 0) {
        $progress = cli()->progress()->total($total);
    } elseif ($progress && $now > 0) {
        $progress->current($now);
        if ($total == $now) {
            $progress = null;
        }
    }
}

function vd() {
    $args = func_get_args();
    if (empty($args)) {
        return null;
    }
    $trace = debug_backtrace();
    $file = str_replace($_SERVER['DOCUMENT_ROOT'], '', $trace[0]['file']);
    $line = $trace[0]['line'];
    $cli_chr = chr(27);
    $is_cli = $is_plain = false;
    if (PHP_SAPI == 'cli') {
        $is_cli = true;
    } elseif(false !== strpos(implode('', headers_list()), 'text/plain')) {
        $is_plain = true;
    }
    foreach($args as $val) {
        ob_start();
        var_dump($val);
        $dump = ob_get_contents();
        ob_end_clean();
        if ($is_cli) {
            echo PHP_EOL;
            echo $cli_chr.'[100m '.$file.' (Line:'.$line.') '.$cli_chr.'[0m'.PHP_EOL;
            echo $cli_chr.'[90m'.$dump.$cli_chr.'[0m'.PHP_EOL;
        } elseif($is_plain) {
            echo '- '.$file.' (Line:'.$line.') -'.PHP_EOL;
            echo $dump.PHP_EOL;
        } else {
            echo '<pre style="font-family:monospace;text-align:left;margin:5px;padding:0;font-size:12px;background-color:transparent;border:0;">';
            echo '<span style="color:gray"><b>'.$file.'</b> (Line:<b>'.$line.'</b>)</span>'.PHP_EOL;
            echo htmlspecialchars($dump);
            echo '</pre><br>'.PHP_EOL;
        }
    }
}
