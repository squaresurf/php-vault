<?php
/**
 * Vault.php
 *
 * PHP Version 5.3
 *
 * @category Encryption
 * @package  SquareSurf
 * @author   Daniel Paul Searles <daniel.paul.searles@gmail.com>
 * @license  MIT License
 * @link     https://github.com/squaresurf/php-vault
 */

require 'VaultException.php';

/**
 * Vault
 *
 * @category Encryption
 * @package  SquareSurf
 * @author   Daniel Paul Searles <daniel.paul.searles@gmail.com>
 * @license  MIT License
 * @link     https://github.com/squaresurf/php-vault
 */
class Vault
{
    /**
     * The cipher to use for encryption and decryption. Maybe we'll make this
     * configurable in the future.
     * @var string
     */
    protected $cipher = 'AES-256-CFB';

    /**
     * An array to hold the data that gets encrypted on export.
     * @var array
     */
    protected $data = array();

    /**
     * The constructor that will load data passed to it.
     *
     * @param string $password The password that unencrypts the data.
     * @param string $json     JSON with the structure from self::export()
     */
    public function __construct($password = null, $json = null)
    {
        if (!extension_loaded('OpenSSL')) {
            throw new VaultException(
                'The OpenSSL extension is required.',
                VaultException::ERROR
            );
        }

        $this->load($password, $json);
    }

    /**
     * Magic getter method to get the value for a key.
     *
     * @param mixed $key The key that corresponds to the value.
     *
     * @link http://php.net/manual/en/language.oop5.overloading.php#object.get
     *
     * @return mixed
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        return null;
    }

    /**
     * Magic setter method to set the value for a key.
     *
     * @param mixed $key   The key that corresponds to the value.
     * @param mixed $value The value that corresponds to the key.
     *
     * @link http://php.net/manual/en/language.oop5.overloading.php#object.set
     *
     * @return null
     */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Get a JSON string with the following format.
     * {
     *     "iv": "The iv for encryption",
     *     "cipher": "The encryption method",
     *     "encrypted_data": "The encrypted data"
     *  }
     *
     * @param string $password The password to use to encrypt the data.
     *
     * @throws VaultException
     *
     * @return string
     */
    public function export($password)
    {
        $cipher = $this->cipher;
        $this->throwIfCipherIsUnavailable($cipher);

        // Generate an iv
        $ivLength = openssl_cipher_iv_length($cipher);

        if ($ivLength) {
            $binIv = openssl_random_pseudo_bytes($ivLength);
            $iv = bin2hex($binIv);
        } else {
            throw new VaultException(
                "Can't determine the iv length for: $cipher",
                VaultException::ERROR
            );
        }

        // Encrypt data
        $encrypted_data = openssl_encrypt(
            json_encode($this->data), $cipher, $password, 0, $binIv
        );

        return json_encode(compact('iv', 'cipher', 'encrypted_data'));
    }

    /**
     * Load from a JSON string with the same format as returned from export.
     *
     * @param string $password The password that unencrypts the data.
     * @param string $json     JSON with the structure from self::export()
     *
     * @throws VaultException
     *
     * @return null
     */
    protected function load($password = null, $json = null)
    {
        if (is_null($json)) {
            return;
        }

        if (is_null($password)) {
            throw new VaultException(
                "Trying to load JSON with no password.",
                VaultException::ERROR
            );
        }

        $json_obj = json_decode($json);

        if (!is_object($json_obj)) {
            throw new VaultException(
                "Trying to load JSON that isn't an object.",
                VaultException::ERROR
            );
        }

        $required = array('iv', 'cipher', 'encrypted_data');
        foreach ($required as $field) {
            if (!property_exists($json_obj, $field)) {
                throw new VaultException(
                    "Missing field `$field' in JSON.",
                    VaultException::ERROR
                );
            }
        }

        $this->throwIfCipherIsUnavailable($json_obj->cipher);

        $binIv = hex2bin($json_obj->iv);

        $json = openssl_decrypt(
            $json_obj->encrypted_data, $json_obj->cipher, $password, 0, $binIv
        );

        $json_arr = json_decode($json, true);

        if (is_array($json_arr)) {
            $this->data = $json_arr;
        } else {
            throw new VaultException(
                'It is likely that your password was incorrect.',
                VaultException::WARNING
            );
        }
    }

    /**
     * Check if the cipher is available.
     *
     * @param string $cipher The cipher to check availability.
     *
     * @return null
     */
    protected function throwIfCipherIsUnavailable($cipher)
    {
        if (!in_array($cipher, openssl_get_cipher_methods(true))) {
            throw new VaultException(
                "Cipher `$cipher' is unavailable.",
                VaultException::ERROR
            );
        }
    }
}
