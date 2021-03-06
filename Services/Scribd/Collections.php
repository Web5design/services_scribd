<?php
/**
 * Interface for Scribd's "collections" API endpoints.
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
 * @copyright 2013 Rich Schumacher <rich.schu@gmail.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   Release: @package-version@
 * @link      http://pear.php.net/package/Services_Scribd
 */

require_once 'Services/Scribd/Common.php';

/**
 * The interface for the "collections" API endpoints.  Provides all interaction
 * that is associated with a collection of documents, such as creating and 
 * deleting collections as well as adding and removing documents to those
 * collections.
 *
 * @category  Services
 * @package   Services_Scribd
 * @author    Rich Schumacher <rich.schu@gmail.com>
 * @copyright 2013 Rich Schumacher <rich.schu@gmail.com>
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://www.scribd.com/developers/platform/api
 */
class Services_Scribd_Collections extends Services_Scribd_Common
{
    /**
     * Array of API endpoints that are supported
     *
     * @var array
     */
    protected $validEndpoints = array(
        'addDoc',
        'create',
        'delete',
        'getList',
        'listDocs',
        'removeDoc',
        'update'
    );

    /**
     * Adds a document to an existing collection.
     *
     * @param integer $docId        ID of the document to add
     * @param integer $collectionId ID of the collection to add to
     *
     * @link http://www.scribd.com/developers/platform/api/collections_adddoc
     * @return boolean
     */
    public function addDoc($docId, $collectionId)
    {
        $this->arguments['doc_id']        = $docId;
        $this->arguments['collection_id'] = $collectionId;

        $response = $this->call('collections.addDoc', HTTP_Request2::METHOD_POST);

        return (string) $response['stat'] == 'ok';
    }

    /**
     * Creates a new collection.
     *
     * @param string $name        Name of the collection
     * @param string $description Description of the collection
     * @param string $privacyType Privacy setting, either 'public' or 'private'
     *
     * @link http://www.scribd.com/developers/platform/api/collections_create
     * @return integer The ID of the created collection
     */
    public function create($name, $description = null, $privacyType = 'public')
    {
        $this->arguments['name']         = $name;
        $this->arguments['description']  = $description;
        $this->arguments['privacy_type'] = $privacyType;

        $response = $this->call('collections.create', HTTP_Request2::METHOD_POST);

        return (int) $response->collection_id;
    }

    /**
     * Deletes a collection.
     *
     * @param integer $collectionId ID of the colleciton to update
     *
     * @link http://www.scribd.com/developers/platform/api/collections_delete
     * @return boolean
     */
    public function delete($collectionId)
    {
        $this->arguments['collection_id'] = $collectionId;

        $response = $this->call('collections.delete', HTTP_Request2::METHOD_POST);

        return (string) $response['stat'] == 'ok';
    }

    /**
     * Retrieves a list of collections for a given user.
     *
     * @param string $privacyType Privacy sope, either 'public' or 'private'.
     * If omitted, all document collections are returned.
     *
     * @link http://www.scribd.com/developers/platform/api/collections_getlist
     * @return SimpleXMLElement
     */
    public function getList($privacyType = null)
    {
        $this->arguments['scope'] = $privacyType;

        $response = $this->call('collections.getList', HTTP_Request2::METHOD_GET);

        return $response->resultset;
    }

    /**
     * Retrieves a list of documents in a given collection.
     *
     * @param integer $collectionId ID of the collection to add to
     * @param integer $limit        Max number of documents to return
     * @param integer $offset       Offset into the list of documents
     *
     * @link http://www.scribd.com/developers/platform/api/collections_listdocs
     * @return SimpleXMLElement
     */
    public function listDocs($collectionId, $limit = null, $offset = null)
    {
        $this->arguments['collection_id'] = $collectionId;
        $this->arguments['limit']         = $limit;
        $this->arguments['offset']        = $offset;

        $response = $this->call('collections.listDocs', HTTP_Request2::METHOD_GET);

        return $response->result_set;
    }

    /**
     * Removes a document to an existing collection.
     *
     * @param integer $docId        ID of the document to add
     * @param integer $collectionId ID of the collection to add to
     *
     * @link http://www.scribd.com/developers/platform/api/collections_removedoc
     * @return boolean
     */
    public function removeDoc($docId, $collectionId)
    {
        $this->arguments['doc_id']        = $docId;
        $this->arguments['collection_id'] = $collectionId;

        $response = $this->call('collections.removeDoc', HTTP_Request2::METHOD_POST);

        return (string) $response['stat'] == 'ok';
    }

    /**
     * Updates a new collection's name, description or privacy_type.
     *
     * @param integer $collectionId ID of the colleciton to update
     * @param string  $name         Name of the collection
     * @param string  $description  Description of the collection
     * @param string  $privacyType  Privacy setting, either 'public' or 'private'
     *
     * @link http://www.scribd.com/developers/platform/api/collections_update
     * @return boolean
     */
    public function update($collectionId, $name = null, $description = null,
        $privacyType = null
    ) {
        $this->arguments['collection_id'] = $collectionId;
        $this->arguments['name']          = $name;
        $this->arguments['description']   = $description;
        $this->arguments['privacy_type']  = $privacyType;

        $response = $this->call('collections.update', HTTP_Request2::METHOD_POST);

        return (string) $response['stat'] == 'ok';
    }
}
