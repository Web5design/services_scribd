<?php
/**
 * Common logic for all of Scribd's API endpoints.
 *
 * PHP version 5.2.0+
 *
 * LICENSE: This source file is subject to the New BSD license that is 
 * available through the world-wide-web at the following URI:
 * http://www.opensource.org/licenses/bsd-license.php. If you did not receive  
 * a copy of the New BSD License and are unable to obtain it through the web, 
 * please send a note to license@php.net so we can mail you a copy immediately. 
 *
 * @category  Services
 * @package   Services_Scribd
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Rich Schumacher <rich.schu@gmail.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   Release: @package-version@
 * @link      http://pear.php.net/package/Services_Scribd
 */

require_once 'Services/Scribd.php';
require_once 'HTTP/Request2.php';

/**
 * This class contains common logic needed for all the API endpoints.  Handles
 * tasks such as sending requests, signing the requests, etc.
 *
 * @category  Services
 * @package   Services_Scribd
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2009 Rich Schumacher <rich.schu@gmail.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://www.scribd.com/developers/platform
 */
class Services_Scribd_Common extends Services_Scribd
{
    /**
     * An array of arguments to send to the API
     *
     * @var array
     */
    protected $arguments = array();

    /**
     * The Scribd account to use for requests
     *
     * @var Services_Scribd_Account
     */
    protected $account = null;

    /**
     * The HTTP request adapter to use
     * 
     * @var string|HTTP_Request2_Adapter
     */
    protected $requestAdapter = null;

    /**
     * An array of arguments that we must skip when calculating the API
     * signature
     *
     * @var array
     */
    private $_skipSignatureArguments = array(
        'file'
    );

    /**
     * Prevents calls from bubbling up to Serices_Scribd::_construct()
     *
     * @param Services_Scribd_Account $account The account to use
     *
     * @return void
     */
    public function __construct(Services_Scribd_Account $account)
    {
        $this->account = $account;
    }

    /**
     * Returns an array of endpoints for this driver
     *
     * @return array
     */
    public function getAvailableEndpoints()
    {
        return $this->validEndpoints;
    }

    /**
     * Traps any requests to endpoints that are not defined
     *
     * @param string $endpoint The invalid endpoint requested
     * @param array  $params   Array of params for this endpoint
     *
     * @throws Services_Scribd_Exception
     * @return null
     */
    public function __call($endpoint, array $params)
    {
        throw new Services_Scribd_Exception(
            'Invalid endpoint requested: ' . $endpoint
        );
    }

    /**
     * Sets the request adapter to use for this request
     * 
     * @param HTTP_Request2_Adapter $adapter An instance of th adapter
     * 
     * @return void
     */
    public function setRequestAdapter(HTTP_Request2_Adapter $adapter)
    {
        $this->requestAdapter = $adapter;
    }

    /**
     * Builds, sends, and returns the response for an API request
     *
     * Using curl, actually send the request to the Scribd API. Delegates to
     * helper methods to format the arguments, send request, response, etc.
     *
     * @param string $endpoint The requested endpoint
     * @param string $method   The HTTP method to use, defaults to GET
     *
     * @throws Services_Scribd_Exception
     * @return mixed
     */
    protected function call($endpoint, $method = HTTP_Request2::METHOD_GET)
    {
        if ($method !== HTTP_Request2::METHOD_GET
            && $method !== HTTP_Request2::METHOD_POST
        ) {
            throw new Services_Scribd_Exception('Invalid HTTP method: ' . $method);
        }

        $uri      = $this->_buildRequestURI($endpoint, $method);
        $response = $this->sendRequest($uri, $method);

        $this->_reset();

        return $this->_formatResponse($response);
    }

    /**
     * Sends the request to the Scribd API
     *
     * @param string $uri    The API URI to request
     * @param string $method The HTTP method to use
     *
     * @throws Services_Scribd_Exception
     * @return void
     */
    protected function sendRequest($uri, $method)
    {
        $config = array(
            'timeout' => $this->timeout
        );

        $request = new HTTP_Request2($uri, $method, $config);
        $request->setHeader('User-Agent', '@package-name@-@package-version@');

        if ($this->requestAdapter !== null) {
            $request->setAdapter($this->requestAdapter);
        }

        if ($method === HTTP_Request2::METHOD_POST) {
            if (array_key_exists('file', $this->arguments)) {
                $request->addUpload('file', $this->arguments['file']);
                unset($this->arguments['file']);
            }
            $request = $request->addPostParameter($this->arguments);
        }

        try {
            $response = $request->send();
        } catch (HTTP_Request2_Exception $e) {
            throw new Services_Scribd_Exception($e->getMessage(), $e->getCode());
        }

        if ($response->getStatus() !== 200) {
            throw new Services_Scribd_Exception(
                'Invalid response returned from server',
                $response->getStatus()
            );
        }

        return $response->getBody();
    }

    /**
     * Builds the API request URI
     *
     * Delegates the merging and request signing to more specific methods.
     *
     * @param string $endpoint The requested endpoint
     * @param string $method   The HTTP method to use, defaults to GET
     *
     * @return string
     */
    private function _buildRequestURI($endpoint, $method)
    {
        $this->_mergeRequestArguments($endpoint);

        $this->_signRequest();

        if ($method === HTTP_Request2::METHOD_POST) {
            return Services_Scribd::API;
        }

        $queryString = http_build_query($this->arguments);

        return Services_Scribd::API . '?' . $queryString;
    }

    /**
     * Merges required API arguments with request specific arguments
     *
     * @param string $endpoint The requested endpoint
     *
     * @return void
     */
    private function _mergeRequestArguments($endpoint)
    {
        $requiredArguments = array(
            'method'  => $endpoint,
            'api_key' => $this->account->apiKey
        );

        if ($this->account->apiSessionKey !== null) {
            $this->arguments['session_key'] = $this->account->apiSessionKey;
        }

        if ($this->account->myUserId !== null) {
            $this->arguments['my_user_id'] = $this->account->myUserId;
        }

        // Get rid of any nulls
        $this->arguments = array_diff($this->arguments, array(null));

        // ...and merge them with the required arguments
        $this->arguments = array_merge($requiredArguments, $this->arguments);
    }

    /**
     * Signs the request
     *
     * Generates an API signature if the Services_Scribd::$apiSecret variable
     * has been set to help protect against evesdropping attacks.
     *
     * @link http://www.scribd.com/developers/platform#signing
     * @see Services_Scribd::$apiSecret
     * @return void
     */
    private function _signRequest()
    {
        if ($this->account->apiSecret === null) {
            return;
        }

        if (!empty($this->arguments['api_sig'])) {
            unset($this->arguments['api_sig']);
        }

        ksort($this->arguments);

        $apiSig = null;

        foreach ($this->arguments as $key => $value) {
            if (!in_array($key, $this->_skipSignatureArguments)) {
                $apiSig .= $key . $value;
            }
        }

        $this->arguments['api_sig'] = md5($this->account->apiSecret . $apiSig);
    }

    /**
     * Returns a SimpleXMLElement element from the raw XML response
     *
     * @param string $response The XML response from the API
     *
     * @throws Services_Scribd_Exception
     * @return SimpleXMLElement
     */
    private function _formatResponse($response)
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response);
        if (!$xml instanceof SimpleXmlElement) {
            throw new Services_Scribd_Exception(
                'Could not parse XML response'
            );
        }

        if ( (string) $xml['stat'] !== 'ok') {
            $code    = (int) $xml->error['code'];
            $message = (string) $xml->error['message'];
            throw new Services_Scribd_Exception($message, $code);
        }

        return $xml;
    }

    /**
     * Performs any cleanup after the request has been sent
     *
     * @return void
     */
    private function _reset()
    {
        $this->arguments = array();
    }
}

?>
