<?php
/**
 * Laravel Curl Class
 *
 * Work with remote servers via cURL much easier than using the native PHP bindings.
 *
 * @category   Libraries
 * @package    Laravel
 * @subpackage Libraries
 * @author     Philip Sturgeon <imogetutu@gmail.com>
 * @license    http://philsturgeon.co.uk/code/dbad-license dbad-license
 * @link       http://philsturgeon.co.uk/code/Codeigniter-curl
 */
class Curl
{
    protected $response = '';       // Contains the cURL response for debug
    protected $session;             // Contains the cURL handler for a session
    protected $url;                 // URL of the session
    protected $options = array();   // Populates curl_setopt_array
    protected $headers = array();   // Populates extra HTTP headers
    public $error_code;             // Error code returned as an int
    public $error_string;           // Error message returned as a string
    public $info;                   // Returned after request (elapsed time, etc)

    /**
     * Constructor
     *
     * @param string $url URL
     */
    public function __construct($url = '')
    {
        if ( ! $this->isEnabled()) {
            throw new CurlException('cURL Class - PHP was not built with cURL enabled. Rebuild PHP with --with-curl to use cURL.');
        }

        $url AND $this->create($url);
    }

    /**
     * [__call description]
     *
     * @param string $method    HTTP Methods
     *
     * @param string $arguments Arguments
     *
     * @return Response
     */
    public function __call($method, $arguments)
    {
        if (in_array($method, array('simple_get', 'simple_post', 'simple_put', 'simple_delete'))) {
            // Take off the "simple_" and past get/post/put/delete to _simpleCall
            $verb = str_replace('simple_', '', $method);
            array_unshift($arguments, $verb);

            return call_user_func_array(array($this, '_simpleCall'), $arguments);
        }
    }

    /* =================================================================================
     * SIMPLE METHODS
     * Using these methods you can make a quick and easy cURL call with one line.
     * ================================================================================= */
    /**
     * Simple One Liner Call
     *
     * @param string $method  HTTP Method
     *
     * @param string $url     URL
     *
     * @param array  $params  cURL Parameters
     *
     * @param array  $options cURL Options
     *
     * @return Response
     */
    private function _simpleCall($method, $url, $params = array(), $options = array())
    {
        // Get acts differently, as it doesnt accept parameters in the same way
        if ($method === 'get') {
                // If a URL is provided, create new session
            $this->create($url.($params ? '?'.http_build_query($params, null, '&') : ''));
        } else {
                // If a URL is provided, create new session
                $this->create($url);

                $this->{$method}($params);
        }

        // Add in the specific options provided
        $this->options($options);

        return $this->execute();
    }

    /**
     * Simple FTP Get
     *
     * @param string $url       URL
     *
     * @param string $file_path File Path
     *
     * @param string $username  FTP Username
     *
     * @param string $password  FTP Password
     *
     * @return Response
     */
    public function simpleFtpGet($url, $file_path, $username = '', $password = '')
    {
        // If there is no ftp:// or any protocol entered, add ftp://
        if ( ! preg_match('!^(ftp|sftp)://! i', $url)) {
                $url = 'ftp://' . $url;
        }

            // Use an FTP login
        if ($username != '') {
                $auth_string = $username;

            if ($password != '') {
                    $auth_string .= ':' . $password;
            }

                // Add the user auth string after the protocol
                $url = str_replace('://', '://' . $auth_string . '@', $url);
        }

        // Add the filepath
        $url .= $file_path;

        $this->option(CURLOPT_BINARYTRANSFER, true);
        $this->option(CURLOPT_VERBOSE, true);

        return $this->execute();
    }

    /* =================================================================================
     * ADVANCED METHODS
     * Use these methods to build up more complex queries
     * ================================================================================= */

    public function post($params = array(), $options = array())
    {
        // If its an array (instead of a query string) then format it correctly
        if (is_array($params)) {
            $params = http_build_query($params, null, '&');
        }

        // Add in the specific options provided
        $this->options($options);

        $this->httpMethod('post');

        $this->option(CURLOPT_POST, true);
        $this->option(CURLOPT_POSTFIELDS, $params);
    }

    public function put($params = array(), $options = array())
    {
            // If its an array (instead of a query string) then format it correctly
        if (is_array($params)) {
            $params = http_build_query($params, null, '&');
        }

            // Add in the specific options provided
            $this->options($options);

        $this->httpMethod('put');
        $this->option(CURLOPT_POSTFIELDS, $params);

        // Override method, I think this overrides $_POST with PUT data but... we'll see eh?
        $this->option(CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: PUT'));
    }

    public function delete($params, $options = array())
    {
        // If its an array (instead of a query string) then format it correctly
        if (is_array($params)) {
            $params = http_build_query($params, null, '&');
        }

        // Add in the specific options provided
        $this->options($options);

        $this->httpMethod('delete');

        $this->option(CURLOPT_POSTFIELDS, $params);
    }

    public function setCookies($params = array())
    {
        if (is_array($params)) {
            $params = http_build_query($params, null, '&');
        }

        $this->option(CURLOPT_COOKIE, $params);
        return $this;
    }

    public function httpHeader($header, $content = null)
    {
        $this->headers[] = $content ? $header . ': ' . $content : $header;
        return $this;
    }

    public function httpMethod($method)
    {
        $this->options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
        return $this;
    }

    public function httpLogin($username = '', $password = '', $type = 'any')
    {
        $this->option(CURLOPT_HTTPAUTH, constant('CURLAUTH_' . strtoupper($type)));
        $this->option(CURLOPT_USERPWD, $username . ':' . $password);
        return $this;
    }

    public function proxy($url = '', $port = 80)
    {
        $this->option(CURLOPT_HTTPPROXYTUNNEL, true);
        $this->option(CURLOPT_PROXY, $url . ':' . $port);
        return $this;
    }

    public function proxyLogin($username = '', $password = '')
    {
        $this->option(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
        return $this;
    }

    public function ssl($verify_peer = true, $verify_host = 2, $path_to_cert = null)
    {
        if ($verify_peer) {
            $this->option(CURLOPT_SSL_VERIFYPEER, true);
            $this->option(CURLOPT_SSL_VERIFYHOST, $verify_host);
            if (isset($path_to_cert)) {
                $path_to_cert = realpath($path_to_cert);
                $this->option(CURLOPT_CAINFO, $path_to_cert);
            }
        } else {
            $this->option(CURLOPT_SSL_VERIFYPEER, false);
        }
        return $this;
    }

    public function options($options = array())
    {
        // Merge options in with the rest - done as array_merge() does not overwrite numeric keys
        foreach ($options as $option_code => $option_value) {
            $this->option($option_code, $option_value);
        }

        // Set all options provided
        curl_setopt_array($this->session, $this->options);

        return $this;
    }

    public function option($code, $value)
    {
        if (is_string($code) && !is_numeric($code)) {
            $code = constant('CURLOPT_' . strtoupper($code));
        }

        $this->options[$code] = $value;
        return $this;
    }

    /**
     * Start a session from a URL
     *
     * @param string $url URL
     *
     * @return Response
     */
    public function create($url)
    {
        // If no a protocol in URL, assume its a Laravel link
        if ( ! preg_match('!^\w+://! i', $url)) {
            $url = url($url);
        }

        $this->url = $url;
        $this->session = curl_init($this->url);

        return $this;
    }

    /**
     * End a session and return the results
     *
     * @return Response
     */
    public function execute()
    {
        // Set two default options, and merge any extra ones in
        if ( ! isset($this->options[CURLOPT_TIMEOUT])) {
            $this->options[CURLOPT_TIMEOUT] = 30;
        }
        if ( ! isset($this->options[CURLOPT_RETURNTRANSFER])) {
            $this->options[CURLOPT_RETURNTRANSFER] = true;
        }
        if ( ! isset($this->options[CURLOPT_FAILONERROR])) {
            $this->options[CURLOPT_FAILONERROR] = true;
        }

        // Only set follow location if not running securely
        if ( ! ini_get('safe_mode') && ! ini_get('open_basedir')) {
            // Ok, follow location is not set already so lets set it to true
            if ( ! isset($this->options[CURLOPT_FOLLOWLOCATION])) {
                $this->options[CURLOPT_FOLLOWLOCATION] = true;
            }
        }

        if ( ! empty($this->headers)) {
            $this->option(CURLOPT_HTTPHEADER, $this->headers);
        }

        $this->options();

        // Execute the request & and hide all output
        $this->response = curl_exec($this->session);
        $this->info = curl_getinfo($this->session);

        // Request failed
        if ($this->response === false) {
            $errno = curl_errno($this->session);
            $error = curl_error($this->session);

            curl_close($this->session);
            $this->setDefaults();

            $this->error_code = $errno;
            $this->error_string = $error;

            return false;
        } else {
            // Request successful
            curl_close($this->session);
            $this->last_response = $this->response;
            $this->setDefaults();
            return $this->last_response;
        }
    }

    public function isEnabled()
    {
        return function_exists('curl_init');
    }

    public function debug()
    {
        echo "=============================================<br/>\n";
        echo "<h2>CURL Test</h2>\n";
        echo "=============================================<br/>\n";
        echo "<h3>Response</h3>\n";
        echo "<code>" . nl2br(htmlentities($this->last_response)) . "</code><br/>\n\n";

        if ($this->error_string) {
                echo "=============================================<br/>\n";
                echo "<h3>Errors</h3>";
                echo "<strong>Code:</strong> " . $this->error_code . "<br/>\n";
                echo "<strong>Message:</strong> " . $this->error_string . "<br/>\n";
        }

        echo "=============================================<br/>\n";
        echo "<h3>Info</h3>";
        echo "<pre>";
        print_r($this->info);
        echo "</pre>";
    }

    public function debugRequest()
    {
        return array(
            'url' => $this->url
        );
    }

    public function setDefaults()
    {
        $this->response = '';
        $this->headers = array();
        $this->options = array();
        $this->error_code = null;
        $this->error_string = '';
        $this->session = null;
    }

}

Class CurlException extends Exception {}

/* End of file Curl.php */
/* Location: ./libraries/Curl.php */
