<?php
/**
 * @package OaiPmhRepository
 * @subpackage MetadataFormats
 * @copyright Copyright 2009-2014 John Flatness, Yu-Hsun Lin
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

/**
 * Class implementing metadata output for the required oai_dc metadata format.
 * oai_dc is output of the 15 unqualified Dublin Core fields.
 *
 * Override OaiPmhRepository_Metadata_OaiDc via an option in config.
 *
 * @package OaiPmhRepository
 * @subpackage Metadata Formats
 */
class OaiPmhRepository_Metadata_OaiDcCustom implements OaiPmhRepository_Metadata_FormatInterface
{
    /** OAI-PMH metadata prefix */
    const METADATA_PREFIX = 'oai_dc';

    /** XML namespace for output format */
    const METADATA_NAMESPACE = 'http://www.openarchives.org/OAI/2.0/oai_dc/';

    /** XML schema for output format */
    const METADATA_SCHEMA = 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd';

    /** XML namespace for unqualified Dublin Core */
    const DC_NAMESPACE_URI = 'http://purl.org/dc/elements/1.1/';

    protected $customMapping;

    /**
     * Appends Dublin Core metadata.
     *
     * Appends a metadata element, an child element with the required format,
     * and further children for each of the Dublin Core fields present in the
     * item.
     */
    public function appendMetadata($item, $metadataElement)
    {
        $document = $metadataElement->ownerDocument;

        /** @var DOMElement $oai_dc */
        $oai_dc = $document->createElementNS(
            self::METADATA_NAMESPACE,
            'oai_dc:dc'
        );

        $metadataElement->appendChild($oai_dc);

        $custom = $this->getCustomMapping();

        $oai_dc->setAttribute('xmlns:dc', self::DC_NAMESPACE_URI);
        $oai_dc->declareSchemaLocation(self::METADATA_NAMESPACE, self::METADATA_SCHEMA);

        /* Each of the 15 unqualified Dublin Core elements, in the order
         * specified by the oai_dc XML schema
         */
        $dcElementNames = array(
            'title', 'creator', 'subject', 'description', 'publisher',
            'contributor', 'date', 'type', 'format', 'identifier', 'source',
            'language', 'relation', 'coverage', 'rights',
        );

        $dcTypes = array(
            'Collection',
            'Dataset',
            'Event',
            'Image',
            'Interactive Resource',
            'Service',
            'Software',
            'Sound',
            'Text',
            'Physical Object',
            'Still Image',
            'Moving Image',
        );

        $exposeItemType = (bool) get_option('oaipmh_repository_expose_item_type');
        $exposeFiles = metadata($item, 'has_files') && get_option('oaipmh_repository_expose_files');
        $exposeThumbnail = $item->hasThumbnail() && get_option('oaipmh_repository_expose_thumbnail');

        /* Must create elements using createElement to make DOM allow a
         * top-level xmlns declaration instead of wasteful and non-
         * compliant per-node declarations.
         */
        foreach ($dcElementNames as $elementName) {
            $term = 'dc:' . $elementName;
            $upperName = Inflector::camelize($elementName);
            $values = $item->getElementTexts(
                'Dublin Core',
                $upperName
            );

            // Simplify all element texts.
            foreach ($values as $key => $elementText) {
                $values[$key] = $elementText->text;
            }

            // Manage specificities.
            switch ($elementName) {
                case 'type':
                    // Prepend the item type, if any and if wanted.
                    if ($exposeItemType) {
                        $dcType = $item->getProperty('item_type_name');
                        if ($dcType) {
                            array_unshift($values, $dcType);
                        }
                    }

                    // TODO Add an option for default language when type is text? Better to fill source data.
                    break;

                case 'identifier':
                    // Append the browse URI to all results
                    $values[] = record_url($item, 'show', true);

                    // Also append an identifier for each file
                    if ($exposeFiles) {
                        $files = $item->getFiles();
                        foreach ($files as $file) {
                            $values[] = $file->getWebPath('original');
                        }
                    }
                    break;

                case 'relation':
                    // For Bibliothèque nationale de France, use "vignette :"
                    // instead of "thumbnail:".
                    if ($exposeThumbnail) {
                        $values[] = 'vignette : ' . $item->getFile()->getWebPath('thumbnail');
                    }
                    break;
            }

            // Output normal metadata (no custom, no language).
            if (empty($custom['mapping'][$term])) {
                $values = array_values(array_unique($values));
                foreach ($values as $elementText) {
                    $oai_dc->appendNewElement($term, $elementText);
                }
                continue;
            }

            // Process a specific custom term.
            if (isset($custom['replace'][$term])) {
                $normalizedValues = $values;
                if (!empty($custom['options'][$term]['case'])) {
                    foreach ($custom['options'][$term]['case'] as $case) {
                        $normalizedValues = array_map($case, $normalizedValues);
                    }
                }
                $normalizedValues = array_values(array_unique($normalizedValues));

                $mergedValues = array_combine($normalizedValues, $normalizedValues);
                $mergedValues = array_unique(array_replace(
                    $mergedValues,
                    array_intersect_key($custom['replace'][$term], $mergedValues)
                ));
                $values = array_values($mergedValues);
            }

            // Second step: translation, or get the language of each value.
            // Process the third step too when a source is found.
            $translated = array();
            if (isset($custom['translate'][$term])) {
                foreach ($values as $value) {
                    $isTranslated = false;
                    $isLangPairs = false;
                    $langValue = $custom['default_language'];
                    foreach ($custom['translate'][$term] as $lang => $texts) {
                        $keyText = array_search($value, $texts);
                        $isTranslated = $keyText !== false;
                        if ($isTranslated) {
                            $langValue = $lang;
                            $isLangPairs = !empty($custom['langpairs'][$term][$langValue]);
                            $translations = array();
                            // Keep this source with the language.
                            $translations[] = array($lang => $texts[$keyText]);
                            // Get all translations except if it is limited.
                            if ($isLangPairs) {
                                foreach ($custom['langpairs'][$term][$langValue] as $lang) {
                                    if (isset($custom['mapping'][$term][$keyText]['translate'][$lang])) {
                                        $translations[] = array($lang => $custom['mapping'][$term][$keyText]['translate'][$lang]);
                                    }
                                }
                            } else {
                                foreach ($custom['translate'][$term] as $lang => $texts) {
                                    if ($lang === $langValue) {
                                        continue;
                                    }
                                    if (isset($texts[$keyText])) {
                                        $translations[] = array($lang => $texts[$keyText]);
                                    }
                                }
                            }

                            // Order the default language first.
                            if ($custom['default_language']) {
                                $ordered = array(0 => array(), 1 => array());
                                foreach ($translations as $translation) {
                                    $ordered[(int) (key($translation) === $custom['default_language'])][] = $translation;
                                }
                                $translations = array_merge($ordered[1], $ordered[0]);
                            }

                            // Third step: prepend or append values.
                            $translated = array_merge(
                                $translated,
                                $custom['mapping'][$term][$keyText]['prepend'],
                                $translations,
                                $custom['mapping'][$term][$keyText]['append']
                            );
                            break;
                        }
                    }
                    if (!$isTranslated) {
                        $translated[] = array($custom['default_language'] => $value);
                    }
                }
            } else {
                foreach ($values as $value) {
                    $translated[] = array($custom['default_language'] => $value);
                }
            }
            $values = $translated;

            // Deduplicate values.
            // array_unique() cannot be used with array values, and doesn't
            // manage case.
            $result = array();
            foreach ($values as $value) {
                $result[serialize(array_map('strtolower', $value))] = $value;
            }
            $values = array_values($result);

            // Specific value for BnF: dctype should be first, without language.
            // Furthermore, Image should be before Still Image.
            // Furthermore, the dctype may be set in English with another lang.
            // TODO The last point is not fixed, since it's related to metadata.
            // TODO Order extra types by lang.
            if ($term === 'dc:type') {
                $langs = array();
                $dcTypesBase = array_combine(array_map('strtolower', $dcTypes), $dcTypes);

                $ordered = $dcTypesBase;
                foreach ($values as $valueData) {
                    $value = reset($valueData);
                    $lang = key($valueData);
                    $isEng = empty($lang) || $lang === 'eng';
                    if (!$isEng && !in_array($lang, $langs)) {
                        $langs[] = $lang;
                        foreach ($dcTypes as $dcType) {
                            $ordered[strtolower($dcType) . '-' . $lang] =  $dcType;
                        }
                    }
                    $ordered[strtolower($value) . ($isEng ? '' : ('-' . $lang))] = $valueData;
                }

                $values = array_values(array_filter($ordered, 'is_array'));
                if ($values) {
                    $first = $values[0];
                    $value = reset($first);
                    $lang = key($first);
                    if ($lang === 'eng' && isset($dcTypesBase[strtolower($value)])) {
                        $values[0] = [$value];
                    }
                }
            }

            foreach ($values as $valueData) {
                $value = reset($valueData);
                $lang = key($valueData);
                $element = $oai_dc->appendNewElement($term, $value);
                if ($lang && !is_numeric($lang)) {
                    $element->setAttribute('xml:lang', $lang);
                }
            }
        }
    }

    protected function getCustomMapping()
    {
        if (is_null($this->customMapping)) {
            $customMapping = include PLUGIN_DIR
                . DIRECTORY_SEPARATOR
                . 'OaiPmhRepository'
                . DIRECTORY_SEPARATOR
                . 'data'
                . DIRECTORY_SEPARATOR
                . 'oaidc_custom_record.php';
            if (empty($customMapping)) {
                $this->customMapping = array();
                return $this->customMapping;
            }

            // Prepare some derivate tables to quick process.
            $custom = array();

            // This is a global options, not related to a term.
            $defaultLanguage = get_option('oaipmh_repository_custom_default_language');
            $custom['default_language'] = $defaultLanguage;

            $custom['mapping'] = $customMapping;
            $custom['options'] = array();
            $custom['replace'] = array();
            $custom['translate'] = array();
            $custom['langpairs'] = array();

            foreach ($customMapping as $term => $mapping) {
                $custom['options'][$term] = empty($mapping['options'])
                    ? null
                    : $mapping['options'];
                if (isset($mapping['options']['case'][$defaultLanguage])) {
                    if (!is_array($mapping['options']['case'][$defaultLanguage])) {
                        $mapping['options']['case'][$defaultLanguage] = array($mapping['options']['case'][$defaultLanguage]);
                    }
                    $cases = array();
                    foreach ($mapping['options']['case'][$defaultLanguage] as $case) {
                        if (method_exists('Inflector', $case)) {
                            $cases[] = array('Inflector', $case);
                        } elseif (function_exists($case)) {
                            $cases[] = $case;
                        }
                    }
                    $custom['options'][$term]['case'] = $cases;
                } else {
                    $custom['options'][$term]['case'] = array();
                }

                unset($mapping['options']);
                unset($custom['mapping'][$term]['options']);

                // Prepare tables.
                $custom['replace'][$term] = array();
                // Set the default language first.
                if ($defaultLanguage) {
                    $custom['translate'][$term][$defaultLanguage] = array();
                }

                foreach ($mapping as $keyMap => $map) {
                    if (!empty($map['replace'])) {
                        $custom['replace'][$term] += $map['replace'];
                    }

                    if (!empty($map['translate'])) {
                        // Merge all texts by language for quick search.
                        // Note: the same word may exist in many languages.
                        // The key is kept to get the translation instantly
                        // and to check limited.
                        foreach ($map['translate'] as $lang => $text) {
                            $custom['translate'][$term][$lang][$keyMap] = $text;
                        }
                    }

                    if (!empty($map['langpairs'])) {
                        $custom['langpairs'][$term][$keyMap] = $map['langpairs'];
                    }

                    // Add a prepend array and an append array to simplify process.
                    if (!isset($map['prepend'])) {
                        $custom['mapping'][$term][$keyMap]['prepend'] = array();
                    }
                    if (!isset($map['append'])) {
                        $custom['mapping'][$term][$keyMap]['append'] = array();
                    }
                }
            }
            $this->customMapping = $custom;
        }
        return $this->customMapping;
    }
}
