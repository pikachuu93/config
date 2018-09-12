<?php

new Option('jobs',   'j', 'jobs',   TRUE);
new Option('return', 'r', 'return', TRUE);

$gitStatus = new RacedProcess("exec git status -uno");
$gitBranch = new RacedProcess("exec git branch");

$user = posix_getpwuid(posix_geteuid());

echo Colour::BOLD . (new Colour(5, 0, 5)) . "["
    . ($user["uid"] === 0 ? (new Colour(5, 0, 0)) : (new Colour(0, 5, 0)))
    . $user["name"]
    . formatIp()
    . ' '
    . formatTime()
    . formatReturn()
    . formatJobs(isset($argv[1]) ? $argv[1] : NULL)
    . formatGit($gitStatus, $gitBranch)
    . formatDir()
    . (new Colour(5, 0, 5)) . "]"
    . ($user["uid"] === 0 ? (new Colour(5, 0, 0)) . "#" : (new Colour(0, 5, 0)) . '$')
    . Colour::CLEAR
    . " ";

class Colour
{
    const CLEAR = "\001\033[0m\002";
    const BOLD  = "\001\033[1m\002";

    public function __construct($r, $g, $b, $flags = 0)
    {
        $this->r = $r;
        $this->g = $g;
        $this->b = $b;
    }

    public function __toString()
    {
        return chr(1) . chr(033) . "[38;5;" . (
            $this->r * 36 + $this->g * 6 + $this->b + 16)
            . "m" . chr(2);
    }
}

class RacedProcess
{
    private
        $proc,
        $running,
        $pipes,
        $buffer,
        $return;

    public function __construct($command, $timeout = 500)
    {
        $this->proc = proc_open(
            $command,
            [["pipe", "r"], ["pipe", "w"], ["file", "/dev/null", "w"]],
            $pipes);

        $this->running = true;
        $this->pipes   = $pipes;
        $this->buffer  = "";
    }

    public function isRunning()
    {
        if ($this->running)
        {
            $status = proc_get_status($this->proc);
            $this->running = true === $status["running"];

            if ($this->running === false)
            {
                $this->return = $status["exitcode"];
            }
        }

        return $this->running;
    }

    public function read()
    {
        $read = [$this->pipes[1]];
        $changed = stream_select($read, $write, $except, 0, 10);

        if ($changed && isset($read[0]))
        {
            $this->buffer .= stream_get_contents($read[0], 1024 * 10);
        }

        return $this->buffer;
    }

    public function wait($timeout)
    {
        $end = microtime(TRUE) + ($timeout / 1000);
        while (microtime(TRUE) < $end)
        {
            if (!$this->isRunning())
            {
                break;
            }
        }

        return $this->read();
    }

    public function getReturn()
    {
        return $this->return;
    }
}

function formatJobs()
{
    $jobs = Option::get("jobs")->value();

    if ($jobs)
    {
        return 
            (new Colour(0, 5, 0))
            . "["
            . (new Colour(3, 3, 2))
            . $jobs
            . (new Colour(0, 5, 0))
            . "] ";
    }

    return "";
}

function formatIp()
{
    $ip = `ifconfig`;
    if (preg_match("/192.168.243.(\d+)/", $ip, $ipMatches))
    {
        return (new Colour(5, 0, 5))
            . "@"
            . (new Colour(5, 2, 0)) . $ipMatches[1];
    }
}

function formatReturn()
{
    $value = Option::get("return")->value();

    if ($value !== NULL)
    {
        if ($value == 0)
        {
            return (new Colour(0, 4, 0)) . $value . " ";
        }
        else
        {
            return (new Colour(4, 0, 0)) . $value . " ";
        }
    }
}

function formatGit($gitStatus, $gitBranch)
{
    $status = $gitStatus->wait(100);
    $branch = $gitBranch->wait(100);

    $dirty       = preg_match("/\s*(new file|modified|deleted):/", $status);
    $foundBranch = preg_match("/\*\s*(.*)/", $branch, $branchMatches);

    if (!($status || $foundBranch))
    {
        return "";
    }

    return (new Colour(0, 5, 0)) . "("
        . (new Colour(5, 0, 0))
        . (isset($branchMatches[1]) ? $branchMatches[1] : "")
        . (new Colour(5, 0, 5))
        . ($dirty ? "*" : "")
        . (new Colour(0, 5, 0))
        . ") ";
}

function formatDir()
{
    $dirString = getcwd();

    if (strlen($dirString) < 25)
    {
        return (new Colour(1, 1, 5)) . $dirString;
    }

    $ret   = "";
    $parts = array_reverse(explode("/", getcwd()));

    do
    {
        $ret = array_shift($parts) . "/" . $ret;
    } while (strlen($ret . "/" . $parts[0]) < 24);

    return (new Colour(1, 1, 5)) . ".../" . $ret;
}

function debug($data)
{
    $file = "/home/daniel/.ps1.debug";
    if (is_string($data))
    {
        $string = $data;
    }
    else
    {
        $string = var_export($data, TRUE);
    }

    if (!file_exists($file))
    {
        touch($file);
    }

    file_put_contents($file, $string, FILE_APPEND);
}

function formatTime()
{
    return (new Colour(2, 2, 1)) . date('H:i:s') . ' ';
}

class Option
{
    private static
        $options = [],
        $populated = false;

    private
        $name,
        $short,
        $long,
        $required,
        $default;

    public function __construct($name, $short, $long, $required = NULL, $default = NULL, $description = NULL)
    {
        $this->name        = $name;
        $this->short       = $short;
        $this->long        = $long;
        $this->required    = $required;
        $this->default     = $default;
        $this->description = $description;
        $this->value       = null;

        self::$options[$name] = $this;
    }

    public function value()
    {
        if (!self::$populated)
        {
            $this->getopt();
        }

        return $this->value;
    }

    protected function required()
    {
        if ($this->required === NULL)
        {
            return;
        }
        else if ($this->required)
        {
            return ":";
        }
        else
        {
            return "::";
        }
    }

    protected function setValue($values)
    {
        if (isset($values[$this->short]) || isset($values[$this->long]))
        {
            if ($this->required === NULL)
            {
                $this->value = isset($value[$this->short]) || isset($values[$this->long]);
            }
            else
            {
                $this->value = isset($values[$this->short]) ? $values[$this->short] : $values[$this->long];
            }
        }
    }

    public static function getopt()
    {
        new Option("Help", "h", "help");

        $short = "";
        $long  = [];

        $shortOptions = [];
        $longOptions  = [];

        foreach (self::$options as $option)
        {
            $short .= $option->short . $option->required();
            $long[] = $option->long  . $option->required();
        }

        $parsed = getopt($short, $long);

        foreach (self::$options as $option)
        {
            $option->setValue($parsed);
        }

        self::$populated = true;

        if (isset($parsed["h"]) || isset($parsed["help"]))
        {
            self::helpPage();
            die();
        }
    }

    public static function get($name)
    {
        if (!isset(self::$options[$name]))
        {
            throw new RuntimeException("Option '$name' not found.");
        }

        return self::$options[$name];
    }

    public static function helpPage()
    {
        $table = [["Name", "Flag", "Description"]];

        foreach (self::$options as $option)
        {
            $row = [];
            $row[] = "{$option->name}";

            $flag = "-{$option->short}, --{$option->long}";

            if ($option->required === NULL)
            {
                //Do nothing
            }
            else if ($option->required)
            {
                $flag .= " = VALUE\t";
            }
            else
            {
                $flag .= " [= VALUE]\t";
            }

            $row[] = $flag;

            if ($option->description !== NULL)
            {
                $row[] = $option->description;
            }
            else
            {
                $row[] = "Sets the option '{$option->name}'.";
            }

            $table[] = $row;
        }

        $getMax = function($col) {
            return function($v1, $v2) use ($col) {
                if (strlen($v1[$col]) > strlen($v2[$col]))
                {
                    return $v1;
                }

                return $v2;
            };
        };

        for ($i = 0; $i < 3; ++$i)
        {
            $lengths[$i] = strlen(array_reduce($table, $getMax($i))[$i]);
        }

        foreach ($table as $row)
        {
            echo " ";

            foreach ($row as $i => $cell)
            {
                echo str_pad($cell, $lengths[$i] + 3);
            }

            echo "\n";
        }
    }
}
