#!/usr/bin/env php
<?php
/**
 * php_vault.php
 *
 * A cli script to interface with the Vault class.
 *
 * PHP Version 5.3
 *
 * @category Encryption
 * @package  SquareSurf
 * @author   Daniel Paul Searles <daniel.paul.searles@gmail.com>
 * @license  MIT License
 * @link     https://github.com/squaresurf/php-vault
 */

require 'Vault.php';

/**
 * Print cli script usage.
 *
 * @param string $script The $argv[0] value for the script.
 *
 * @return null
 */
function usage($script)
{
    echo "$script -h \n"
        ."$script [-f path/to/save.json] setKey val \n"
        ."$script [-f path/to/saved.json] getKey \n"
        ."-h | Print this help text. \n"
        ."-f | The filepath to save the encrypted json in. (Default: .vault.json)";
}

// The filepath to the json vault.
$json_vault = '.vault.json';

// An array of the key/val pairs.
$key_vals = array();

for ($index = 1; $index < count($argv); $index++) {
    switch($argv[$index]) {
    case '-h':
        usage($argv[0]);
        exit(0);
        break;
    case '-f':
        $index++;
        $json_vault = $argv[$index];
        break;
    default:
        $key_vals[] = $argv[$index];
        break;
    }
}

// Get Password
$command = "/usr/bin/env bash -c 'read -s -p \"Enter Password: \" "
          ."password && echo \$password'";
$password = rtrim(shell_exec($command));
echo "\n";

try {
    // Load Vault
    $json = null;
    if (file_exists($json_vault)) {
        if (is_readable($json_vault)) {
            $json = file_get_contents($json_vault);
        } else {
            throw VaultException(
                "$json_vault is not readable.",
                VaultException::ERROR
            );
        }
    }

    $vault = new Vault($password, $json);

    $key = array_shift($key_vals);

    if (count($key_vals) > 0) {
        $value = implode(' ', $key_vals);

        $vault->$key = $value;

        file_put_contents($json_vault, $vault->export($password));
    }

    echo $vault->$key."\n";
} catch (VaultException $e) {
    echo $e->getMessage()."\n";
    exit($e->getCode());
}
