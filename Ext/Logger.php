<?php
namespace Ext;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as Monolog;

class Logger extends \Eaw\Logger
{
    public static function getInstance()
    {
        $instance = parent::getInstance();

        if (!$instance instanceof static) {
            static::$instance = $instance = new static();
        }

        return $instance;
    }

    protected function getDefaultLogger()
    {
        if (!array_key_exists($this->defaultName, $this->loggers)) {
            $logger = new Monologger($this->defaultName);

            $handler = new StreamHandler('php://stdout');
            $handler->setFormatter($this);
            $logger->pushHandler($handler);

            $this->loggers[$this->defaultName] = $logger;
        }

        return $this->loggers[$this->defaultName];
    }

    public function format(array $record)
    {
        $useEol = $record['context']['eol'] ?? false;
        $useTimestamp = $record['context']['timestamp'] ?? false;

        $format = [];
        if ($useTimestamp) {
            $format[] = '{dyellow}[{datetime}]{reset} ';
        }

        $format[] = '{message}{reset}';

        if ($useEol) {
            $format[] = '{eol}';
        }

        $record['extra']['format'] = implode('', $format);

        return parent::format($record);
    }
}

class Monologger extends \Monolog\Logger
{
    const ESCAPE = "\033[%sm";

    const DARK = 30;
    const LIGHT = 90;

    const BLACK = 0;
    const RED = 1;
    const GREEN = 2;
    const YELLOW = 3;
    const BLUE = 4;
    const MAGENTA = 5;
    const CYAN = 6;
    const GRAY = 7;

    const RESET = 39;

    public function color(string $string, int $color)
    {
        return sprintf(static::ESCAPE, $color) . $string . sprintf(static::ESCAPE, static::RESET);
    }
}
