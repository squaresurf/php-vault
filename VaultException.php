<?php
/**
 * VaultException.php
 *
 * PHP Version 5.3
 *
 * @category Exception
 * @package  SquareSurf
 * @author   Daniel Paul Searles <daniel.paul.searles@gmail.com>
 * @license  MIT License
 * @link     https://github.com/squaresurf/php-vault
 */

/**
 * VaultException
 *
 * A custom exception to throw from the Vault class.
 *
 * @category Exception
 * @package  SquareSurf
 * @author   Daniel Paul Searles <daniel.paul.searles@gmail.com>
 * @license  MIT License
 * @link     https://github.com/squaresurf/php-vault
 */
class VaultException extends Exception
{
    // Constants represent exception code values.
    const ERROR = 1;
    const WARNING = 2;
    const DEBUG = 3;
    const INFO = 4;
}
