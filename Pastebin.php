<?php

namespace CC;

class Pastebin
{
    const URL = 'http://pastebin.com';

    protected $dir;

    public static $sleep = 1;

    public function __construct($dir, $sleep = null)
    {
        $this->log(__METHOD__);
        
        if ($sleep !== null) {
            self::$sleep = $sleep;
        }

        $this->dir = rtrim($dir, '/\\');
    }

    public function start()
    {
        mt_srand(time());

        $this->process();

        $this->start();
    }

    public function process()
    {
        $this->log(__METHOD__);

        $content = @file_get_contents(self::URL . '/archive');

        if (preg_match_all('~<td>(?:<img(?:[^>]+)/?>)?<a href="/([^"]{8})">([^<]+)</a>~', $content, $matches)) {
            $codes = $matches[1];
            $names = $matches[2];
            $types = array();
            if (preg_match_all('~<td(?:[^>]+)><a href="([^"]+)">([^<]+)</a></td>~', $content, $types)) {
                $types = $types[2];
            }
            foreach ($codes as $k => $code) {
                $type = isset($types[$k]) ? $types[$k] : 'None';
                $name = isset($names[$k]) ? $names[$k] : 'Untitled';
                $name = preg_replace('~[^a-z0-9]+~i', '_', $name);
                $name = preg_replace('~_+~', '_', $name);
                $name = date('Y-m-d-H') . '_' . trim($name, '_') . '_' . trim($code, '/\\');
                $this->savePaste($code, $name, $type);
            }
        } else {
            $this->log('Error...');
            $this->handleError($content);
            $this->process();
        }
    }

    protected function savePaste($code, $name, $type)
    {
        $content = @file_get_contents(self::URL . '/raw.php?i=' . trim($code, '/\\'));

        if ($content && mb_strlen($content) > 3) {

            $content = trim($content);

            $subdir = $this->detectSubdir($name, $content, $type);

            $dir = $this->dir . DIRECTORY_SEPARATOR . $subdir;

            @mkdir($dir);

            $file = $dir . DIRECTORY_SEPARATOR . $name . '.pb';

            if (is_array(self::$sleep)) {
                $sec = mt_rand(self::$sleep[0], self::$sleep[1]);
            } else {
                $sec = (int) self::$sleep;
            }

            if (file_exists($file) === false) {
                file_put_contents($file, $content);
                $this->log('(Sleep: ' . $sec . ') (' . $subdir . ') ' . $name);
            } else {
                $this->log('Name "' . $name . '" exists');
            }

            sleep($sec);
        }
    }

    protected function handleError($content)
    {
        $dir = $this->dir . DIRECTORY_SEPARATOR . 'Error';

        @mkdir($dir);

        $file = $dir . DIRECTORY_SEPARATOR . date('Y-m-d-H-i-s') . '.txt';

        if (file_exists($file) === false) {
            file_put_contents($file, $content);
        }
    }

    protected function detectSubdir($name, $content, $type)
    {
        if (preg_match('~BEGIN (RSA|DSA|PGP|CERTIFICATE|OpenVPN)~i', $content)
            || preg_match('~(password|leak|leaked|mysql dump|credit card|cracked|hacked|hack|crack|INSERT INTO|CREATE TABLE)~i', $content)
        ) {
            return 'Password';
        }

        if ($type != 'None') {
            return $type;
        }

        $subdir = (preg_match('~_Untitled_~', $name) ? '~Untitled' : '~Titled');

        if (preg_match('~\?php~', $content) || preg_match('~\$(?:\w+) ~', $content)) {
            $subdir = '~PHP';
        }  else if (preg_match('~def initialize~', $content)) {
            $subdir = 'Ruby';
        } else if (preg_match('~html~i', $content) && preg_match('~body~i', $content)) {
            $subdir = 'HTML';
        } else if (preg_match('~^using~', $content) || preg_match('~using System~', $content)) {
            $subdir = 'C#';
        } else if (preg_match('~#include~', $content) || preg_match('~#define~', $content)) {
            $subdir = 'C++';
        } else if (preg_match('~^package~', $content) || preg_match('~^import~', $content) || preg_match('~java\.~', $content) || preg_match('~\.java~', $content)) {
            $subdir = 'Java';
        } else if (preg_match('~Dim ~', $content) || preg_match('~Sub ~', $content)) {
            $subdir = 'VisualBasic';
        }

        return $subdir;
    }

    protected function log($text)
    {
        echo '[' . date('Y-m-d H:i:s') . '] ' . $text . (PHP_SAPI === "cli" ? "\n" : "<br />");
    }
}