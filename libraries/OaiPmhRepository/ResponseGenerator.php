<?php
/**
 * @package OaiPmhRepository
 * @subpackage Libraries
 * @copyright Copyright 2009-2014 John Flatness, Yu-Hsun Lin
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * OaiPmhRepository_ResponseGenerator generates the XML responses to OAI-PMH
 * requests received by the repository. The DOM extension is used to generate
 * all the XML output on-the-fly.
 *
 * @package OaiPmhRepository
 * @subpackage Libraries
 */
class OaiPmhRepository_ResponseGenerator extends OaiPmhRepository_AbstractXmlGenerator
{
    /**
     * HTTP query string or POST vars formatted as an associative array.
     * @var array
     */
    private $query;

    /**
     * Array of all supported metadata formats.
     * $metdataFormats['metadataPrefix'] = ImplementingClassName
     * @var array
     */
    private $metadataFormats;

    /**
     * Number of records to display by page.
     *
     * @var int
     */
    private $_listLimit;

    /**
     * Number of minutes before expiration of token.
     *
     * @var int
     */
    private $_tokenExpirationTime;

    /**
     * The toolkit describes the app that manages the repository.
     *
     * @link http://oai.dlib.vt.edu/OAI/metadata/toolkit.xsd.
     * @var array
     */
    private $_toolkit;

    /**
     * The base url of the server, used for the OAI-PMH request.
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Id of main elements (hard coded in table elements).
     *
     * @var array
     */
    protected $dcId = array(
        'identifier' => 43,
        'title' => 50,
        'type' => 51,
    );

    /**
     * Constructor
     *
     * Creates the DomDocument object, and adds XML elements common to all
     * OAI-PMH responses.  Dispatches control to appropriate verb, if any.
     *
     * @param array $query HTTP POST/GET query key-value pair array.
     * @uses dispatchRequest()
     */
    public function __construct($query)
    {
        $this->_loadConfig();

        $this->baseUrl = OAI_PMH_BASE_URL;

        $this->error = false;
        $this->query = $query;
        $this->document = new DomDocument('1.0', 'UTF-8');
        $this->document->registerNodeClass(
            'DOMElement',
            'OaiPmhRepository_DOMElement'
        );

        OaiPmhRepository_Plugin_OaiIdentifier::initializeNamespace(get_option('oaipmh_repository_namespace_id'));

        //formatOutput makes DOM output "pretty" XML.  Good for debugging, but
        //adds some overhead, especially on large outputs.
        $this->document->formatOutput = true;
        $this->document->xmlStandalone = true;

        if (get_option('oaipmh_repository_add_human_stylesheet')) {
            $stylesheet = src('oai-pmh-repository', 'xsl', 'xsl');
            $xslt = $this->document->createProcessingInstruction('xml-stylesheet', 'type="text/xsl" href="' . $stylesheet . '"');
            $this->document->appendChild($xslt);
        }

        $root = $this->document->createElementNS(
            self::OAI_PMH_NAMESPACE_URI,
            'OAI-PMH'
        );
        $this->document->appendChild($root);

        $root->declareSchemaLocation(self::OAI_PMH_NAMESPACE_URI, self::OAI_PMH_SCHEMA_URI);

        $responseDate = $this->document->createElement(
            'responseDate',
            OaiPmhRepository_Plugin_Date::unixToUtc(time())
        );
        $root->appendChild($responseDate);

        $this->metadataFormats = $this->getFormats();

        $this->dispatchRequest();
    }

    private function _loadConfig()
    {
        $iniFile = OAI_PMH_REPOSITORY_PLUGIN_DIRECTORY . '/config.ini';

        $ini = new Zend_Config_Ini($iniFile, 'oai-pmh-repository');

        $this->_listLimit = $ini->list_limit;
        $this->_tokenExpirationTime = $ini->token_expiration_time;
        $this->_toolkit = $ini->toolkit->toArray();
    }

    /**
     * Parses the HTTP query and dispatches to the correct verb handler.
     *
     * Checks arguments for each verb type, and sets XML request tag.
     *
     * @uses checkArguments()
     */
    private function dispatchRequest()
    {
        $request = $this->document->createElement('request', $this->baseUrl);
        $this->document->documentElement->appendChild($request);

        $this->checkRequestMethod();

        $requiredArgs = array();
        $optionalArgs = array();
        if (!($verb = $this->_getParam('verb'))) {
            $this->throwError(self::OAI_ERR_BAD_VERB, 'No verb specified.');
            return;
        }
        $resumptionToken = $this->_getParam('resumptionToken');

        if ($resumptionToken) {
            $requiredArgs = array('resumptionToken');
        } else {
            switch ($this->query['verb']) {
                case 'Identify':
                    break;
                case 'GetRecord':
                    $requiredArgs = array('identifier', 'metadataPrefix');
                    break;
                case 'ListRecords':
                    $requiredArgs = array('metadataPrefix');
                    $optionalArgs = array('from', 'until', 'set');
                    break;
                case 'ListIdentifiers':
                    $requiredArgs = array('metadataPrefix');
                    $optionalArgs = array('from', 'until', 'set');
                    break;
                case 'ListSets':
                    break;
                case 'ListMetadataFormats':
                    $optionalArgs = array('identifier');
                    break;
                default:
                    $this->throwError(self::OAI_ERR_BAD_VERB);
            }
        }

        $this->checkArguments($requiredArgs, $optionalArgs);

        if (!$this->error) {
            foreach ($this->query as $key => $value) {
                $request->setAttribute($key, $value);
            }

            if ($resumptionToken) {
                $this->resumeListResponse($resumptionToken);
            }
            /* ListRecords and ListIdentifiers use a common code base and share
               all possible arguments, and are handled by one function. */
            elseif ($verb == 'ListRecords' || $verb == 'ListIdentifiers') {
                $this->initListResponse();
            } else {
                /* This Inflector use means verb-implementing functions must be
                   the lowerCamelCased version of the verb name. */
                $functionName = Inflector::variablize($verb);
                $this->$functionName();
            }
        }
    }

    /**
     * Check the method of the request.
     */
    private function checkRequestMethod()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        if (!in_array($method, array('GET', 'POST'))) {
            $this->throwError(
                self::OAI_ERR_BAD_ARGUMENT,
                __('The OAI-PMH protocol version 2.0 supports only "GET" and "POST" requests, not "%s".', $method)
            );
        }
    }

    /**
     * Checks the argument list from the POST/GET query.
     *
     * Checks if the required arguments are present, and no invalid extra
     * arguments are present.  All valid arguments must be in either the
     * required or optional array.
     *
     * @param array requiredArgs Array of required argument names.
     * @param array optionalArgs Array of optional, but valid argument names.
     */
    private function checkArguments($requiredArgs = array(), $optionalArgs = array())
    {
        $requiredArgs[] = 'verb';

        // Checks (essentially), if there are more arguments in the query string
        // than in PHP's returned array, if so there were duplicate arguments,
        // which is not allowed.
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $query = $_SERVER['QUERY_STRING'];
                if ((urldecode($query) != urldecode(http_build_query($this->query)))) {
                    $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Duplicate arguments in request.");
                }
                break;
            case 'POST':
                // TODO Check duplicate post arguments.
                break;
        }

        $keys = array_keys($this->query);

        foreach (array_diff($requiredArgs, $keys) as $arg) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Missing required argument $arg.");
        }
        foreach (array_diff($keys, $requiredArgs, $optionalArgs) as $arg) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Unknown argument $arg.");
        }

        $from = $this->_getParam('from');
        $until = $this->_getParam('until');

        $fromGran = OaiPmhRepository_Plugin_Date::getGranularity($from);
        $untilGran = OaiPmhRepository_Plugin_Date::getGranularity($until);

        if ($from && !$fromGran) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Invalid date/time argument.");
        }
        if ($until && !$untilGran) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Invalid date/time argument.");
        }
        if ($from && $until && $fromGran != $untilGran) {
            $this->throwError(self::OAI_ERR_BAD_ARGUMENT, "Date/time arguments of differing granularity.");
        }

        $metadataPrefix = $this->_getParam('metadataPrefix');

        if ($metadataPrefix && !array_key_exists($metadataPrefix, $this->metadataFormats)) {
            $this->throwError(self::OAI_ERR_CANNOT_DISSEMINATE_FORMAT);
        }
    }

    /**
     * Responds to the Identify verb.
     *
     * Appends the Identify element for the repository to the response.
     */
    public function identify()
    {
        if ($this->error) {
            return;
        }

        /* according to the schema, this order of elements is required for the
           response to validate */
        $elements = array(
            'repositoryName' => get_option('oaipmh_repository_name'),
            'baseURL' => $this->baseUrl,
            'protocolVersion' => self::OAI_PMH_PROTOCOL_VERSION,
            'adminEmail' => get_option('administrator_email'),
            'earliestDatestamp' => $this->_getEarliestDatestamp(),
            'deletedRecord' => 'no',
            'granularity' => OaiPmhRepository_Plugin_Date::OAI_GRANULARITY_STRING,
        );
        $identify = $this->document->documentElement->appendNewElementWithChildren(
            'Identify',
            $elements
        );

        // Publish support for compression, if appropriate
        // This defers to compression set in Omeka's paths.php
        if (extension_loaded('zlib') && ini_get('zlib.output_compression')) {
            $gzip = $this->document->createElement('compression', 'gzip');
            $deflate = $this->document->createElement('compression', 'deflate');
            $identify->appendChild($gzip);
            $identify->appendChild($deflate);
        }

        $description = $this->document->createElement('description');
        $identify->appendChild($description);
        OaiPmhRepository_Plugin_OaiIdentifier::describeIdentifier($description);

        $toolkitDescription = $this->document->createElement('description');
        $identify->appendChild($toolkitDescription);
        $this->describeToolkit($toolkitDescription);
    }

    private function describeToolkit($parentElement)
    {
        $toolkitNamespace = 'http://oai.dlib.vt.edu/OAI/metadata/toolkit';
        $toolkitSchema = 'http://oai.dlib.vt.edu/OAI/metadata/toolkit.xsd';

        $iniFile = OAI_PMH_REPOSITORY_PLUGIN_DIRECTORY . '/plugin.ini';
        $ini = new Zend_Config_Ini($iniFile, 'info');
        $version = $ini->version;

        $elements = $this->_toolkit;
        $elements['version'] = $version;

        $toolkit = $parentElement->appendNewElementWithChildren('toolkit', $elements);
        $toolkit->setAttribute('xsi:schemaLocation', "$toolkitNamespace $toolkitSchema");
        $toolkit->setAttribute('xmlns', $toolkitNamespace);
    }

    /**
     * Responds to the GetRecord verb.
     *
     * Outputs the header and metadata in the specified format for the specified
     * identifier.
     */
    private function getRecord()
    {
        $identifier = $this->_getParam('identifier');
        $metadataPrefix = $this->_getParam('metadataPrefix');

        $itemId = OaiPmhRepository_Plugin_OaiIdentifier::oaiIdToItem($identifier);

        if (!$itemId) {
            $this->throwError(self::OAI_ERR_ID_DOES_NOT_EXIST);
            return;
        }

        $item = get_db()->getTable('Item')->find($itemId);

        if (!$item) {
            $this->throwError(self::OAI_ERR_ID_DOES_NOT_EXIST);
        }

        if (!$this->error) {
            $verbElement = $this->document->createElement('GetRecord');
            $this->document->documentElement->appendChild($verbElement);
            $this->appendRecord($verbElement, $item, $metadataPrefix);
        }
    }

    /**
     * Responds to the ListMetadataFormats verb.
     *
     * Outputs records for all of the items in the database in the specified
     * metadata format.
     *
     * @todo extend for additional metadata formats
     */
    private function listMetadataFormats()
    {
        $identifier = $this->_getParam('identifier');
        /* Items are not used for lookup, simply checks for an invalid id */
        if ($identifier) {
            $itemId = OaiPmhRepository_Plugin_OaiIdentifier::oaiIdToItem($identifier);

            if (!$itemId) {
                $this->throwError(self::OAI_ERR_ID_DOES_NOT_EXIST);
                return;
            }
        }
        if (!$this->error) {
            $listMetadataFormats = $this->document->createElement('ListMetadataFormats');
            $this->document->documentElement->appendChild($listMetadataFormats);
            foreach ($this->metadataFormats as $prefix => $format) {
                $elements = array(
                    'metadataPrefix' => $prefix,
                    'schema' => $format['schema'],
                    'metadataNamespace' => $format['namespace']
                );
                $listMetadataFormats->appendNewElementWithChildren('metadataFormat', $elements);
            }
        }
    }

    /**
     * Responds to the ListSets verb.
     *
     * Outputs setSpec and setName for all OAI-PMH sets (Omeka collections).
     *
     * @todo replace with Zend_Db_Select to allow use of limit or pageLimit
     */
    private function listSets()
    {
        $expose = get_option('oaipmh_repository_expose_set');
        if (!$expose || $expose === 'none') {
            $this->throwError(self::OAI_ERR_NO_SET_HIERARCHY);
            return;
        }

        $flatFormat = get_option('oaipmh_repository_identifier_format') === 'flat';

        $db = get_db();
        $sets = array();
        if (in_array($expose, array('itemset', 'itemset_itemtype', 'itemset_dctype'))) {
            if ((bool) get_option('oaipmh_repository_expose_empty_collections')) {
                $collections = get_db()->getTable('Collection')
                    ->findBy(array('public' => '1'));
            } else {
                $select = new Omeka_Db_Select();
                $select
                    ->from(array('collections' => $db->Collection))
                    ->joinInner(array('items' => $db->Item), 'collections.id = items.collection_id', array())
                    ->where('collections.public = 1')
                    ->where('items.public = 1')
                    ->group('collections.id');
                $collections = get_db()->getTable('Collection')->fetchObjects($select);
            }

            $itemSetIdentifier = get_option('oaipmh_repository_identifier_itemset');
            foreach ($collections as $collection) {
                $name = metadata(
                    $collection,
                    version_compare(OMEKA_VERSION, '2.4.2', '<') ? array('Dublin Core', 'Title') : 'display_title'
                ) ?: __('[Untitled]');
                $spec = null;
                switch ($itemSetIdentifier) {
                    case 'itemset_id':
                        $spec = ($flatFormat ? 'itemset_' : '') . $collection->id;
                        break;
                    case 'itemset_identifier':
                        $spec = $this->cleanSetString(metadata($collection, array('Dublin Core', 'Identifier')));
                        if (empty($spec)) {
                            continue 2;
                        }
                        break;
                    case 'itemset_title':
                        $spec = $this->cleanSetString($name);
                        if (empty($spec)) {
                            continue 2;
                        }
                        break;
                }
                $elements = array(
                    'setSpec' => $flatFormat ? $spec : ('itemset:' . $spec),
                    'setName' => $name,
                );
                $sets[] = array(
                    'elements' => $elements,
                    'description' => $this->_prepareCollectionDescription($collection),
                );
            }
        }

        if (in_array($expose, array('itemtype', 'itemset_itemtype'))) {
            $itemTypeIdentifier = get_option('oaipmh_repository_identifier_itemtype');
            $itemTypeId = $itemTypeIdentifier !== 'itemtype_name';
            $table = $db->getTable('ItemType');
            $select = $table->getSelect()
                ->joinInner(array('items' => $db->Item), 'items.item_type_id = item_types.id', array())
                ->where('items.public = 1')
                ->group('item_types.id')
                ->order('item_types.name');
            $itemTypes = $table->fetchAll($select);

            foreach ($itemTypes as $itemType) {
                if ($itemTypeId) {
                    $spec = 'type_' . $itemType['id'];
                } else {
                    $spec = $this->cleanSetString($itemType['name']);
                    if (empty($spec)) {
                        continue;
                    }
                }
                $elements = array(
                    'setSpec' => $flatFormat ? $spec : ('type:' . $spec),
                    'setName' => $itemType['name'],
                );
                $sets[] = array(
                    'elements' => $elements,
                    'description' => array('description' => array($itemType['description'])),
                );
            }
        }

        if (in_array($expose, array('dctype', 'itemset_dctype'))) {
            $list = array();
            $table = $db->getTable('Items');
            // Since there is no id, one is created from the lower case value.
            // It cannot be a hash, since it should be searchable.
            // According to specs, it can be anything, but must not use ":".
            // It must contain only unreserved character (alphanumeric and "-_.!~*'()").
            // Since space are not allowed, they are converted into "_", so this
            // character is forbidden too in this current implementation.
            // @see https://www.ietf.org/rfc/rfc2396.txt 2.3
            // Max size of the column in database is 190.
            // So some value can be skipped.
            // @see https://www.openarchives.org/OAI/openarchivesprotocol.html#Set
            $select = $table->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns(array('DISTINCT(element_texts.text)'))
                ->joinInner(array('element_texts' => $db->ElementText), 'element_texts.record_id = items.id', array())
                ->where('items.public = 1')
                ->where('element_texts.element_id = ' . $this->dcId['type'])
                ->where('LENGTH(element_texts.text) <= 190')
                ->where('element_texts.text NOT LIKE "%:%"')
                ->where('element_texts.text NOT LIKE "%\_%"')
                ->group('element_texts.text')
                ->order('element_texts.text');
            $dcTypes = $table->fetchCol($select);
            foreach ($dcTypes as $dcType) {
                $spec = $this->cleanSetString($dcType);
                // Check spec.
                if (empty($spec)) {
                    _log(
                        __('OAI-PMH Repository: skipped dc:type "%s": it contains unconvertable diacritic or disallowed characters.', $dcType),
                        Zend_Log::WARN
                    );
                    continue;
                }
                $list[$spec] = $dcType;
            }

            if (get_option('oaipmh_repository_custom_oai_dc')) {
                $list = $this->completeSetList('dc:type', $list);
            }
            ksort($list);
            foreach ($list as $spec => $name) {
                $elements = array(
                    'setSpec' => $flatFormat ? $spec : ('type:' . $spec),
                    'setName' => $name,
                );
                $sets[] = array(
                    'elements' => $elements,
                    'description' => null,
                );
            }
        }

        if (count($sets) == 0) {
            $this->throwError(self::OAI_ERR_NO_SET_HIERARCHY);
            return;
        }

        /** @var DOMElement $listSets */
        $listSets = $this->document->createElement('ListSets');

        if ($this->error) {
            return;
        }

        $this->document->documentElement->appendChild($listSets);
        foreach ($sets as $set) {
            $oaiSet = $listSets->appendNewElementWithChildren('set', $set['elements']);
            if (!empty($set['description'])) {
                $this->_addSetDescription($oaiSet, $set['description']);
            }
        }
    }

    /**
     * Prepare the set description for a collection / set, if any.
     *
     * @see OaiPmhRepository_Metadata_OaiDc::appendMetadata()
     *
     * @param Collection $collection
     * @return array
     */
    private function _prepareCollectionDescription(Collection $collection)
    {
        // List of the Dublin Core terms, needed to removed qualified ones.
        $dcTerms = array(
            'title' => 'Title',
            'creator' => 'Creator',
            'subject' => 'Subject',
            'description' => 'Description',
            'publisher' => 'Publisher',
            'contributor' => 'Contributor',
            'date' => 'Date',
            'type' => 'Type',
            'format' => 'Format',
            'identifier' => 'Identifier',
            'source' => 'Source',
            'language' => 'Language',
            'relation' => 'Relation',
            'coverage' => 'Coverage',
            'rights' => 'Rights',
        );

        $elementTexts = array();
        foreach ($dcTerms as $name => $elementName) {
            $elTexts = $collection->getElementTexts('Dublin Core', $elementName);
            // Remove the first title.
            if ($elementName == 'Title' && isset($elTexts[0])) {
                unset($elTexts[0]);
            }
            if ($elTexts) {
                $elementTexts[$name] = $elTexts;
            }
        }

        $result = array();
        foreach ($elementTexts as $name => $elTexts) {
            foreach ($elTexts as $elementText) {
                $result[$name][] = $elementText->text;
            }
        }
        return $result;
    }

    /**
     * Append the set description for the oai set, if any.
     *
     * @see OaiPmhRepository_Metadata_OaiDc::appendMetadata()
     *
     * @param DOMElement $set
     * @param array $values
     * @return DOMElement|null
     */
    private function _addSetDescription(DOMElement $set, $values)
    {
        if (empty($values)) {
            return null;
        }

        $setDescription = $this->document->createElement('setDescription');
        $set->appendChild($setDescription);
        $oai_dc = $this->document->createElementNS(
            OaiPmhRepository_Metadata_OaiDc::METADATA_NAMESPACE,
            'oai_dc:dc'
        );
        $setDescription->appendChild($oai_dc);

        $oai_dc->setAttribute('xmlns:dc', OaiPmhRepository_Metadata_OaiDc::DC_NAMESPACE_URI);
        $oai_dc->declareSchemaLocation(
            OaiPmhRepository_Metadata_OaiDc::METADATA_NAMESPACE,
            OaiPmhRepository_Metadata_OaiDc::METADATA_SCHEMA
        );

        foreach ($values as $name => $texts) {
            foreach ($texts as $text) {
                $oai_dc->appendNewElement('dc:' . $name, $text);
            }
        }

        return $oai_dc;
    }

    /**
     * Responds to the ListIdentifiers and ListRecords verbs.
     *
     * Only called for the initial request in the case of multiple incomplete
     * list responses
     *
     * @uses listResponse()
     */
    private function initListResponse()
    {
        $fromDate = null;
        $untilDate = null;

        $from = $this->_getParam('from');
        if ($from) {
            $fromDate = OaiPmhRepository_Plugin_Date::utcToDb($from);
        }
        $until = $this->_getParam('until');
        if ($until) {
            $untilDate = OaiPmhRepository_Plugin_Date::utcToDb($until, true);
        }

        $this->listResponse(
            $this->query['verb'],
            $this->query['metadataPrefix'],
            0,
            $this->_getParam('set'),
            $fromDate,
            $untilDate
        );
    }

    /**
     * Returns the next incomplete list response based on the given resumption
     * token.
     *
     * @param string $token Resumption token
     * @uses listResponse()
     */
    private function resumeListResponse($token)
    {
        $tokenTable = get_db()->getTable('OaiPmhRepositoryToken');
        $tokenTable->purgeExpiredTokens();

        $tokenObject = $tokenTable->find($token);

        if (!$tokenObject || ($tokenObject->verb != $this->query['verb'])) {
            $this->throwError(self::OAI_ERR_BAD_RESUMPTION_TOKEN);
        } else {
            $this->listResponse(
                $tokenObject->verb,
                $tokenObject->metadata_prefix,
                $tokenObject->cursor,
                $tokenObject->set,
                $tokenObject->from,
                $tokenObject->until
            );
        }
    }

    /**
     * Responds to the two main List verbs, includes resumption and limiting.
     *
     * @param string $verb OAI-PMH verb for the request
     * @param string $metadataPrefix Metadata prefix
     * @param int $cursor Offset in response to begin output at
     * @param mixed $set Optional set argument
     * @param string $from Optional from date argument
     * @param string $until Optional until date argument
     * @uses createResumptionToken()
     */
    private function listResponse($verb, $metadataPrefix, $cursor, $set, $from, $until)
    {
        $listLimit = $this->_listLimit;

        $db = get_db();
        /** @var Table_Item $itemTable */
        $itemTable = $db->getTable('Item');
        $select = $itemTable->getSelect();
        $alias = $itemTable->getTableAlias();

        $itemTable->filterByPublic($select, true);

        if ($set) {
            $expose = get_option('oaipmh_repository_expose_set');

            $flatFormat = get_option('oaipmh_repository_identifier_format') === 'flat';
            if (!$flatFormat) {
                $main = strtok($set, ':');
                $set = strtok(':');
                $hasItemSet = $main === 'itemset' && in_array($expose, array('itemset', 'itemset_itemtype', 'itemset_dctype'));
                $hasItemType = $main === 'type' && in_array($expose, array('itemtype', 'itemset_itemtype'));
                $hasDcType = $main === 'type' && in_array($expose, array('dctype', 'itemset_dctype'));
            } else {
                $hasItemSet = in_array($expose, array('itemset', 'itemset_itemtype', 'itemset_dctype'));
                $hasItemType = in_array($expose, array('itemtype', 'itemset_itemtype'));
                $hasDcType = in_array($expose, array('dctype', 'itemset_dctype'));
            }

            $found = false;

            if ($hasItemSet) {
                $itemSetIdentifier = get_option('oaipmh_repository_identifier_itemset');
                switch ($itemSetIdentifier) {
                    case 'itemset_id':
                        if (($flatFormat && strpos($set, 'itemset_') === 0)
                            || (!$flatFormat && is_numeric($set))
                        ) {
                            $identifier = $flatFormat ? substr($set, 8) : $set;
                            $itemTable->filterByCollection($select, $identifier);
                            $found = true;
                        }
                        break;
                    case 'itemset_identifier':
                        // An identifier must not have a space.
                        $identifier = rawurldecode($set);
                        $id = $this->fetchRecordId($identifier, 'Collection', $this->dcId['identifier']);
                        if ($id) {
                            $itemTable->filterByCollection($select, $id);
                            $found = true;
                        }
                        break;
                    case 'itemset_title':
                        $identifier = str_replace('_', ' ', rawurldecode($set));
                        $id = $this->fetchRecordId($identifier, 'Collection', $this->dcId['title']);
                        if ($id) {
                            $itemTable->filterByCollection($select, $id);
                            $found = true;
                        }
                        break;
                }
            }

            if (!$found && $hasItemType) {
                $itemTypeIdentifier = get_option('oaipmh_repository_identifier_itemtype');
                switch ($itemTypeIdentifier) {
                    case 'itemtype_id':
                        if (($flatFormat && strpos($set, 'type_') === 0)
                            || (!$flatFormat && is_numeric($set))
                        ) {
                            $identifier = $flatFormat ? substr($set, 5) : $set;
                            $itemTable->filterByItemType($select, $identifier);
                            $found = true;
                        }
                        break;
                    case 'itemtype_name':
                        $identifier = str_replace('_', ' ', rawurldecode($set));
                        $record = get_record('ItemType', array('name' => $identifier));
                        if ($record) {
                            $itemTable->filterByItemType($select, $record->id);
                            $found = true;
                        }
                        break;
                }
            }

            if (!$found && $hasDcType) {
                $value = str_replace('_', ' ', rawurldecode($set));
                if (strlen($value) <= 190 && strpos($value, ':') === false) {
                    if (get_option('oaipmh_repository_custom_oai_dc')) {
                        $values = $this->prepareSearchSetList('dc:type', $value);
                        $values = array_merge(array($value), $values);
                    } else {
                        $values = array($value);
                    }
                    $advanced = array();
                    foreach ($values as $value) {
                        $advanced[] = array(
                            'joiner' => 'or',
                            'element_id' => $this->dcId['type'],
                            'type' => 'is exactly',
                            'terms' => $value,
                        );
                    }
                    $itemTable->filterBySearch($select, array('advanced' => $advanced));
                    $found = true;
                }
            }

            if (!$found) {
                $this->throwError(self::OAI_ERR_NO_RECORDS_MATCH, 'No records match the given criteria.');
                return;
            }
        }

        if ($from) {
            $select->where("$alias.modified >= ? OR $alias.added >= ?", $from);
            $select->group("$alias.id");
        }
        if ($until) {
            $select->where("$alias.modified < ? OR $alias.added < ?", $until);
            $select->group("$alias.id");
        }

        // Total number of rows that would be returned
        $rows = $select->query()->rowCount();
        // This limit call will form the basis of the flow control
        $select->limit($listLimit, $cursor);

        $items = $itemTable->fetchObjects($select);

        if (count($items) == 0) {
            $this->throwError(self::OAI_ERR_NO_RECORDS_MATCH, 'No records match the given criteria.');
        } else {
            $verbElement = $this->document->createElement($verb);
            $this->document->documentElement->appendChild($verbElement);
            foreach ($items as $item) {
                if ($verb == 'ListIdentifiers') {
                    $this->appendHeader($verbElement, $item);
                } elseif ($verb == 'ListRecords') {
                    $this->appendRecord($verbElement, $item, $metadataPrefix);
                }

                // Drop Item from memory explicitly
                release_object($item);
            }
            // No token for a full list.
            if (empty($listLimit)) {
            }
            // Token.
            elseif ($rows > ($cursor + $listLimit)) {
                $token = $this->createResumptionToken(
                    $verb,
                    $metadataPrefix,
                    $cursor + $listLimit,
                    $set,
                    $from,
                    $until
                );

                $tokenElement = $this->document->createElement('resumptionToken', $token->id);
                $tokenElement->setAttribute(
                    'expirationDate',
                    OaiPmhRepository_Plugin_Date::dbToUtc($token->expiration)
                );
                $tokenElement->setAttribute('completeListSize', $rows);
                $tokenElement->setAttribute('cursor', $cursor);
                $verbElement->appendChild($tokenElement);
            }
            // Last token.
            elseif ($cursor != 0) {
                $tokenElement = $this->document->createElement('resumptionToken');
                $verbElement->appendChild($tokenElement);
            }
        }
    }

    /**
     * Appends the record's header to the XML response.
     *
     * Adds the identifier, datestamp and setSpec to a header element, and
     * appends in to the document.
     *
     * @param DOMElement $parentElement
     * @param Item $item
     */
    public function appendHeader($parentElement, $item)
    {
        $headerData = array();
        $headerData['identifier'] = OaiPmhRepository_Plugin_OaiIdentifier::itemToOaiId($item->id);
        $headerData['datestamp'] = OaiPmhRepository_Plugin_Date::dbToUtc($item->modified);
        $setSpecs = array();

        $expose = get_option('oaipmh_repository_expose_set');
        $flatFormat = get_option('oaipmh_repository_identifier_format') === 'flat';

        if (in_array($expose, array('itemset', 'itemset_itemtype', 'itemset_dctype'))) {
            $collection = $item->getCollection();
            if ($collection && $collection->public) {
                $itemSetIdentifier = get_option('oaipmh_repository_identifier_itemset');
                switch ($itemSetIdentifier) {
                    case 'itemset_id':
                        $spec = ($flatFormat ? 'itemset_' : '') . $collection->id;
                        break;
                    case 'itemset_identifier':
                        $spec = $this->cleanSetString(metadata($collection, array('Dublin Core', 'Identifier')));
                        break;
                    case 'itemset_title':
                        $name = metadata(
                            $collection,
                            version_compare(OMEKA_VERSION, '2.4.2', '<') ? array('Dublin Core', 'Title') : 'display_title'
                        ) ?: __('[Untitled]');
                        $spec = $this->cleanSetString($name);
                        break;
                }
                if ($spec) {
                    $setSpecs[] = $flatFormat ? $spec : ('itemset:' . $spec);
                }
            }
        }

        if (in_array($expose, array('itemtype', 'itemset_itemtype'))) {
            $itemType = $item->getItemType();
            if ($itemType) {
                $itemTypeIdentifier = get_option('oaipmh_repository_identifier_itemtype');
                if ($itemTypeIdentifier !== 'itemtype_name') {
                    $spec = ($flatFormat ? 'type_' : '') . $itemType['id'];
                } else {
                    $spec = $this->cleanSetString($itemType->name);
                }
                if ($spec) {
                    $setSpecs[] = $flatFormat ? $spec : ('type:' . $spec);
                }
            }
        }

        if (in_array($expose, array('dctype', 'itemset_dctype'))) {
            $dcTypes = metadata($item, array('Dublin Core', 'Type'), array('all' => true));
            $list = array();
            foreach ($dcTypes as $dcType) {
                // dc:type should be shorter than 190 and without ":" and "_".
                // @see listSets()
                if (strlen($dcType) <= 190
                    && strpos($dcType, ':') === false
                    && strpos($dcType, '_') === false
                ) {
                    $spec = $this->cleanSetString($dcType);
                    if ($spec) {
                        $list[$spec] = $spec;
                    }
                }
            }

            if (get_option('oaipmh_repository_custom_oai_dc')) {
                $list = $this->completeSetList('dc:type', $list);
            }

            ksort($list);
            if (!$flatFormat) {
                $list2 = array();
                foreach ($list as $spec => $name) {
                    $list2['type:' . $spec] = $name;
                }
                $list = $list2;
            }
            $setSpecs = array_merge($setSpecs, array_keys($list));
        }

        $element = $parentElement->appendNewElementWithChildren('header', $headerData);
        foreach ($setSpecs as $setSpec) {
            $setSpec = $this->document->createElement('setSpec', $setSpec);
            $element->appendChild($setSpec);
        }
    }

    /**
     * Appends the record to the XML response.
     *
     * Adds both the header and metadata elements as children of a record
     * element, which is appended to the document.
     *
     * @uses appendHeader
     * @uses OaiPmhRepository_Metadata_Interface::appendMetadata
     * @param DOMElement $parentElement
     * @param Item $item
     * @param string $metdataPrefix
     */
    public function appendRecord($parentElement, $item, $metadataPrefix)
    {
        $record = $this->document->createElement('record');
        $parentElement->appendChild($record);
        $this->appendHeader($record, $item);

        $metadata = $this->document->createElement('metadata');
        $record->appendChild($metadata);

        $formatClass = $this->metadataFormats[$metadataPrefix]['class'];
        $format = new $formatClass;
        $format->appendMetadata($item, $metadata);
    }

    /**
     * Stores a new resumption token record in the database
     *
     * @param string $verb OAI-PMH verb for the request
     * @param string $metadataPrefix Metadata prefix
     * @param int $cursor Offset in response to begin output at
     * @param mixed $set Optional set argument
     * @param string $from Optional from date argument
     * @param string $until Optional until date argument
     * @return OaiPmhRepositoryToken Token model object
     */
    private function createResumptionToken($verb, $metadataPrefix, $cursor, $set, $from, $until)
    {
        // $tokenTable = get_db()->getTable('OaiPmhRepositoryToken');

        $resumptionToken = new OaiPmhRepositoryToken();
        $resumptionToken->verb = $verb;
        $resumptionToken->metadata_prefix = $metadataPrefix;
        $resumptionToken->cursor = $cursor;
        if ($set) {
            $resumptionToken->set = $set;
        }
        if ($from) {
            $resumptionToken->from = $from;
        }
        if ($until) {
            $resumptionToken->until = $until;
        }
        $resumptionToken->expiration = OaiPmhRepository_Plugin_Date::unixToDb(
            time() + ($this->_tokenExpirationTime * 60)
        );
        $resumptionToken->save();

        return $resumptionToken;
    }


    /**
     * Builds an array of entries for all included metadata mapping classes.
     * Derived heavily from OaipmhHarvester's getMaps().
     *
     * @return array An array, with metadataPrefix => class.
     */
    private function getFormats()
    {
        $formats = array(
            'oai_dc' => array(
                'class' => 'OaiPmhRepository_Metadata_OaiDc',
                'namespace' => OaiPmhRepository_Metadata_OaiDc::METADATA_NAMESPACE,
                'schema' => OaiPmhRepository_Metadata_OaiDc::METADATA_SCHEMA
            ),
            'cdwalite' => array(
                'class' => 'OaiPmhRepository_Metadata_CdwaLite',
                'namespace' => OaiPmhRepository_Metadata_CdwaLite::METADATA_NAMESPACE,
                'schema' => OaiPmhRepository_Metadata_CdwaLite::METADATA_SCHEMA
            ),
            'mets' => array(
                'class' => 'OaiPmhRepository_Metadata_Mets',
                'namespace' => OaiPmhRepository_Metadata_Mets::METADATA_NAMESPACE,
                'schema' => OaiPmhRepository_Metadata_Mets::METADATA_SCHEMA
            ),
            'mods' => array(
                'class' => 'OaiPmhRepository_Metadata_Mods',
                'namespace' => OaiPmhRepository_Metadata_Mods::METADATA_NAMESPACE,
                'schema' => OaiPmhRepository_Metadata_Mods::METADATA_SCHEMA
            ),
            'omeka-xml' => array(
                'class' => 'OaiPmhRepository_Metadata_OmekaXml',
                'namespace' => Omeka_Output_OmekaXml_AbstractOmekaXml::XMLNS,
                'schema' => Omeka_Output_OmekaXml_AbstractOmekaXml::XMLNS_SCHEMALOCATION
            ),
            'rdf' => array(
                'class' => 'OaiPmhRepository_Metadata_Rdf',
                'namespace' => OaiPmhRepository_Metadata_Rdf::METADATA_NAMESPACE,
                'schema' => OaiPmhRepository_Metadata_Rdf::METADATA_SCHEMA
            ),
        );
        return apply_filters('oai_pmh_repository_metadata_formats', $formats);
    }

    private function _getParam($param)
    {
        if (array_key_exists($param, $this->query)) {
            return $this->query[$param];
        }
        return null;
    }

    /**
     * Outputs the XML response as a string
     *
     * Called once processing is complete to return the XML to the client.
     *
     * @return string the response XML
     */
    public function __toString()
    {
        return $this->document->saveXML();
    }

    /**
     * Helper to get the earlieast datestamp of the repository.
     *
     * @return string OAI-PMH date stamp.
     */
    private function _getEarliestDatestamp()
    {
        $earliestItem = get_record('Item', array(
            'public' => 1,
            'sort_field' => 'added',
            'sort_dir' => 'a',
        ));
        return $earliestItem
            ? OaiPmhRepository_Plugin_Date::dbToUtc($earliestItem->added)
            : OaiPmhRepository_Plugin_Date::unixToUtc(0);
    }

    /**
     * Get a public record from an identifier element.
     *
     * @param string $identifier
     * @param string $recordType
     * @param int $elementId
     * @return int
     */
    private function fetchRecordId($identifier, $recordType, $elementId)
    {
        if (empty($identifier)) {
            return 0;
        }
        $db = get_db();
        $table = $db->getTable('ElementText');
        $select = $table->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(array('element_texts.record_id'))
            ->joinInner(array('records' => $db->$recordType), 'element_texts.record_id = records.id', array())
            ->where('records.public = 1')
            ->where('element_texts.record_type = ?', $recordType)
            ->where('element_texts.element_id = ?', $elementId)
            ->where('element_texts.text = ?', $identifier)
            ->order('records.id')
            ->limit(1);
        $recordId = $table->fetchOne($select);
        return (int) $recordId;
    }

    /**
     * Convert a string to make it compatible for oai set.
     *
     * @param string $string
     * @return string
     */
    private function cleanSetString($string)
    {
        $unspacedName = str_replace(' ', '_', strtolower($string));
        // The regex to replace html encoded diacritic by simple characters.
        // Note: mysql doesn't manage the same conversion for some alphabets.
        $regexDiacritics ='~\&([A-Za-z])(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron)\;~';
        $asciiName = htmlentities($unspacedName, ENT_NOQUOTES, 'utf-8');
        $asciiName = preg_replace($regexDiacritics, '\1', $asciiName);
        // The specs allows more characters, but they need to be url encoded, so
        // it create complexity ("/^[A-Za-z0-9_.!~*'()-]+$/").
        return preg_match('/^[A-Za-z0-9_.-]+$/', $asciiName)
            ? rawurlencode($asciiName)
            : null;
    }

    /**
     * Complete the list of sets.
     *
     * To be used mainly with OaiDcCustom.
     *
     * @param string $term
     * @param array $list
     * @return array Associative array with spec and name.
     */
    private function completeSetList($term, $list)
    {
        static $customSets;
        if (is_null($customSets)) {
            $customSets = include PLUGIN_DIR
                . DIRECTORY_SEPARATOR
                . 'OaiPmhRepository'
                . DIRECTORY_SEPARATOR
                . 'data'
                . DIRECTORY_SEPARATOR
                . 'oaidc_custom_set.php';
        }

        if (empty($customSets[$term])) {
            return $list;
        }

        $result = $list;
        foreach ($list as $spec => $name) {
            $name = strtolower($name);
            if (empty($customSets[$term][$spec])) {
                continue;
            }
            $result += $customSets[$term][$spec];
        }
        return $result;
    }

    /**
     * Prepare the search for a value in the list of sets.
     *
     * To be used mainly with OaiDcCustom.
     *
     * @param string $term
     * @param string $value
     * @return array
     */
    private function prepareSearchSetList($term, $value)
    {
        static $reverted;
        if (is_null($reverted)) {
            $customSets = include PLUGIN_DIR
                . DIRECTORY_SEPARATOR
                . 'OaiPmhRepository'
                . DIRECTORY_SEPARATOR
                . 'data'
                . DIRECTORY_SEPARATOR
                . 'oaidc_custom_set.php';
            foreach ($customSets as $term => $mapping) {
                foreach ($mapping as $key => $map) {
                    $key = str_replace('_', ' ', $key);
                    foreach ($map as $name) {
                        $reverted[$term][strtolower($name)][] = $key;
                    }
                }
            }
        }
        return isset($reverted[$term][$value])
            ? $reverted[$term][$value]
            : array();
    }
}
