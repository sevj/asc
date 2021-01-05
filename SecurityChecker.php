<?php

namespace Adimeo\SecurityChecker;

/**
 * Class SecurityChecker
 * @package Adimeo\SecurityChecker
 */
class SecurityChecker
{
    /**
     * @var array
     */
    protected $argv;

    /**
     * @var array
     */
    protected $parsedArgv;

    /**
     * @var array
     */
    protected $expectedCommands = [
        '-t' => [],
        '-a' => ['js', 'php', 'all'],
        '-c' => ['mail', 'api'],
        '-ca' => []
    ];

    /**
     * SecurityChecker constructor.
     * @param $argv
     */
    public function __construct($argv)
    {
        array_shift($argv);
        $this->argv = $argv;
    }

    /**
     * @param $methodName
     * @param $args
     */
    public function __call($methodName, $args) {
        call_user_func_array(array($this, $methodName), $args);
    }

    public function process()
    {
        $this->parseArgs();
        $this->execute();
    }

    protected function help()
    {
        $this->write('Command line help');
        $this->write('  Required arguments');
        $this->write('    -a : security to check, values are [' . implode(', ', $this->expectedCommands['-a']) . ']');
        $this->write('    -t : targeted path');
        $this->write('    -c : adapter to use with the result');
        $this->write('    -ca : adapter argument');
        $this->write('');
        $this->write('  Example');
        $this->write('    php security-checker.phar -a php -t path_to_project -c mail -ca email@email.com,email2@email.com');
    }

    protected function parseArgs()
    {
        $this->parsedArgv = [];
        foreach ($this->argv as $k => $argv) {
            if ($argv[0] == '-') {
                if ($argv == '-h') {
                    $this->help();
                    exit;
                }
                if (!isset($this->expectedCommands[$argv])) {
                    $this->writeError(sprintf('No command found %s', $argv));
                    exit;
                }
                if (!empty($this->expectedCommands[$argv]) && !in_array($this->argv[$k + 1], $this->expectedCommands[$argv])) {
                    $this->writeError(sprintf('No command args found for %s / %s', $argv, $this->argv[$k + 1]));
                    exit;
                }
                $this->parsedArgv[$argv] = explode(',', $this->argv[$k + 1]);
            }
        }
        foreach ($this->expectedCommands as $expectedCommand => $values) {
            if (!isset($this->parsedArgv[$expectedCommand])) {
                $this->writeError(sprintf('No command %s found', $expectedCommand));
                exit;
            }
        }
    }

    protected function execute()
    {
        // Execute -a command
        $command = 'check' . ucfirst($this->parsedArgv['-a'][0]);
        $result = $this->$command();

        // Execute -c command
        $adapter = 'Adimeo\SecurityChecker\Adapter\\' . ucfirst($this->parsedArgv['-c'][0]) . 'Adapter';
        if (!class_exists($adapter)) {
            $this->writeError(sprintf('No adapter for %s', $this->parsedArgv['-c'][0]));
            exit;
        }

        $this->write('Transmitting results');
        $adapter::transmit($result, $this->parsedArgv['-ca']);
    }

    protected function checkAll(): array
    {
        $result = $this->checkPhp();
        $result = array_merge($result, $this->checkJs());

        return $result;
    }

    protected function checkPhp(): array
    {
        $this->write('Checking php status');
        $checker = new \SensioLabs\Security\SecurityChecker();
        $lockPAth = $this->parsedArgv['-t'][0] . '/composer.lock';

        $this->write('Locate path ' . $lockPAth);

        if (!is_file($lockPAth)) {
            $this->writeError('composer.lock file not found');
        }
        $result = $checker->check($lockPAth);
        $alerts = json_decode((string) $result, true);

        $result = ['php' => []];
        if (!empty($alerts)) {
            foreach ($alerts as $package => $alert) {
                $result['php'][] = [
                    'package' => $package,
                    'version' => $alert['version'],
                    'title'   => $alert['advisories'][0]['title']
                ];
            }
        }

        return $result;
    }

    protected function checkJs(): array
    {
        $this->write('Checking js status');
        $output = null;

        $lockPAth = $this->parsedArgv['-t'][0] . '/package.json';
        $this->write('Locate path ' . $lockPAth);
        if (!is_file($lockPAth)) {
            $this->writeError('package.json file not found');
        }

        $cmd = 'cd ' . $this->parsedArgv['-t'][0] . ' && npm audit --json   ';

        exec($cmd, $output);

        $alerts = json_decode((string) implode("\n", $output), true);

        $result = ['js' => []];
        if (isset($alerts['actions'])) {
            foreach ($alerts['actions'] as $action) {
                if (!isset($action['target'])) {
                    continue;
                }
                $result['js'][] = [
                    'package' => $action['module'],
                    'version' => $action['target'],
                    'title'   => $action['action']
                ];
            }
        }

        return $result;
    }

    protected function writeError($s)
    {
        echo "\033[31m> ". $s . "\n";
    }

    protected function write($s)
    {
        echo "\033[0m> ". $s . "\n";
    }
}