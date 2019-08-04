<?php
/**
 * Corrige et complète les valeurs à des éléments spécifiques à exposer.
 *
 * Par défaut, ces options permettent de suivre les recommandations de Europeana
 * et de la Bibliothèque nationale de France (BnF) (types et langues).
 * @link https://pro.europeana.eu/resources/apis/oai-pmh-service
 * @link http://www.BnF.fr/documents/Guide_oaipmh.pdf
 *
 * Une valeur spécifique est également ajoutée pour un type de document
 * courant : « carte postale » (« Postal Card »), qui est une « Image fixe ».
 * @link https://nubis.univ-paris1.fr
 *
 * Cette liste peut être complétée et modifiée. Elle ne change pas les données
 * originales et permet donc d’exposer ses données directement.
 *
 * @copyright 2019, Daniel Berthereau pour la Bibliothèque interuniversitaire de la Sorbonne
 * @license CeCILL-B (https://www.cecill.info/licences/Licence_CeCILL-B_V1-en.html)
 */

/**
 * @lang eng
 *
 * Correct and add values to specific elements to expose.
 *
 * By default, these options manage recommendations of Europeana and Bibliothèque nationale de France (BnF)
 * (types and languages).
 * @link https://pro.europeana.eu/resources/apis/oai-pmh-service
 * @link http://www.BnF.fr/documents/Guide_oaipmh.pdf
 *
 * A specific value is added for a common document type too: "Carte postale" ("Postal Card"),
 * that is a Still Image.
 * @link https://nubis.univ-paris1.fr
 *
 * This list can be completed or updated. It doesn’t change original values, so
 * it can be used to expose metadata directly.
 *
 * @copyright 2019, Daniel Berthereau for Bibliothèque interuniversitaire de la Sorbonne
 * @license CeCILL-B (https://www.cecill.info/licences/Licence_CeCILL-B_V1-en.html)
 */

/**
 * Quand un terme est défini, vérifie les valeurs et les corrige, ajoute ou
 * supprime.
 * - La première étape est la normalisation des métadonnées au cas où une
 * contradiction existe entre les standards. Par exemple, la BnF ne suit pas les
 * règles typographiques et utilise « ' » au lieu de « ’ ». De même, elle
 * recommande que le type DCMI soit en minuscule, mais le standard Dublin Core
 * impose casse de titre (première lettre majuscule). Cette étape gère donc les
 * cas où les métadonnées suivent un standard ou une recommendation spécifique.
 * Si la valeur de remplacement est vide, elle est supprimée. Toutes les clés
 * sont dans la casse souhaitée, conformément à l’option principale.
 * Note : même si la BnF recommande le type Dublin Core en minuscule, elle
 * tolère les majuscules de titre; c’est donc cette dernière qui est utilisée.
 * - La deuxième étape est la traduction des métadonnées. Le traitement est
 * réalisé de manière récursive et sans tenir compte de la casse. Le cas où les
 * valeurs originales sont dans une langue ou l’autre est également gérée : si
 * la valeur est « manuscrit », la valeur « manuscript » sera également exposée,
 * et si la valeur est « manuscript », la valeur « manuscrit » sera également
 * exposée.
 * Néanmoins, dans certains cas, la source et la traduction ne sont pas strictes
 * et ne sont pas réversibles. Par exemple, la BnF traduit « Globe terrestre »,
 * « Globe céleste » et « Globe en fuseaux » par « Globe » en anglais, et donc
 * la traduction est moins précise. De même pour « Physical Object », qui peut
 * être « Objet » ou « Monnaie », ou « Text », qui peut être « Texte imprimé »
 * ou « Texte ». Dans ce cas, l’option « langpairs » force à traduire seulement
 * dans les sens indiqués (par exemple de « fre » vers « eng »).
 * - La troisième étape permet d’ajouter des valeurs en premier ou en dernier
 * (« prepend » ou « append »). Ce sont généralement des valeurs davantage
 * génériques.
 * - Enfin, les valeurs en doublon sont supprimées et seule la première est
 * conservée. Dans tous les cas, la langue est facultative.
 */

/**
 * @lang eng
 *
 * When a term is set, check the values and correct, add or remove them.
 * - The first step is the normalization of metadata for the case when there are
 * contradictions between standards. For example, the BnF doesn’t follow
 * typographic standards and uses "'" for "’". The same, it recommends lower
 * case DCMI type, but the Dublin Core standard requires a title case. So this
 * step allows to manage the cases where metadata are recorded according to the
 * standard or a specific recommendations. If the replacement value is empty,
 * the value is removed. All keys are in the wanted case, according to the main
 * option.
 * Note: even if the BnF recommends lower case Dublin Core type, it tolerates
 * title case, so the mapping uses it anyway (ucfirst in French).
 * - The second step is the translation of metadata. The process is done
 * recursively, and case insensitively, and it manages the case where the
 * original metadata are saved in a language or another one: if value is
 * "manuscrit", the value "manuscript" will be exposed too, and if value is
 * "manuscript", the value "manuscrit" will be exposed too.
 * Nevertheless, in some cases, the source and the translation are not strict
 * and not reversible. For example, the BnF translates "Globe terrestre",
 * "Globe céleste" and "Globe en fuseaux" as English "Globe", so the translation
 * is less accurate. The same for "Physical Object", that can be "Objet" or
 * "Monnaie", or "Text", that can be "Texte imprimé" or "Texte". In that case,
 * the option "langpairs" forces to translate only in the specified way (for
 * example only from "fre" to "eng").
 * - The third step allows to add new values. They can be prepended or appended.
 * They are generally a more generic value.
 * - Finally, duplicate values are removed: the first one is kept. In all cases,
 * the language is optional.
 */
return array(
    // The process is designed for dc:type, but may be used for similar terms.
    'dc:type' => array(
        'options' => array(
            // Can be empty, strtolower, ucfirst, ucwords, titleize, camelize,
            // humanize, or a combinaison of them.
            // The default selected case is defined in the config of the plugin.
            // @see Inflector
            'case' => array(
                'eng' => 'titleize',
                'fre' => array('strtolower', 'ucfirst'),
            ),
        ),
        // Standard Dublin Core types.
        // @link http://dublincore.org/documents/dcmi-type-vocabulary/#section-7-dcmi-type-vocabulary
        array(
            'translate' => array(
                'eng' => 'Collection',
                'fre' => 'Collection',
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Dataset',
                'fre' => 'Jeu de données',
            ),
        ),
        array(
            'replace' => array(
                'Evénement' => 'Événement',
            ),
            'translate' => array(
                'eng' => 'Event',
                'fre' => 'Événement',
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Image',
                'fre' => 'Image',
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Interactive Resource',
                'fre' => 'Ressource interactive',
            ),
        ),
/* // Commented, because the translation of the BnF is different below.
        array(
            'translate' => array(
                'eng' => 'Moving Image',
                'fre' => 'Image animée',
            ),
        ),
 */
/* // Commented, because the translation of the BnF is different below.
        array(
            'translate' => array(
                'eng' => 'Physical Object',
                'fre' => 'Objet physique',
            ),
        ),
 */
        array(
            'translate' => array(
                'eng' => 'Service',
                'fre' => 'Service',
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Software',
                'fre' => 'Logiciel',
            ),
        ),
/* // Commented, because the translation of the BnF is different below.
        array(
            'translate' => array(
                'eng' => 'Sound',
                'fre' => 'Son',
            ),
        ),
 */
/* // Commented, because a value is appended below.
        array(
            'translate' => array(
                'eng' => 'Still Image',
                'fre' => 'Image fixe',
            ),
        ),
 */
        array(
            'translate' => array(
                'eng' => 'Text',
                'fre' => 'Texte',
            ),
        ),

        // Specific metadata.
        // This mapping is recommended by BnF (annexe 1).
        // @link http://www.BnF.fr/documents/Guide_oaipmh.pdf
        array(
            'translate' => array(
                'eng' => 'Still Image',
                'fre' => 'Image fixe',
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Text',
                'fre' => 'Texte imprimé',
            ),
            'langpairs' => array(
                'fre' => array('eng'),
            ),
            'append' => array(
                array('eng' => 'Text'),
                array('fre' => 'Texte'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Printed Monograph',
                'fre' => 'Monographie imprimée',
            ),
            'append' => array(
                array('eng' => 'Text'),
                array('fre' => 'Texte'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Manuscrit',
                'fre' => 'Manuscript',
            ),
            'append' => array(
                array('eng' => 'Text'),
                array('fre' => 'Texte'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Printed Serial',
                'fre' => 'Publication en série imprimée',
            ),
            'append' => array(
                array('eng' => 'Text'),
                array('fre' => 'Texte'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Sound',
                'fre' => 'Document sonore',
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Moving Image',
                'fre' => 'Images animées',
            ),
            'append' => array(
                array('eng' => 'Video'),
                array('fre' => 'Vidéo'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Physical Object',
                'fre' => 'Objet',
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Physical Object',
                'fre' => 'Monnaie',
            ),
            'langpairs' => array(
                'fre' => array('eng'),
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Printed Music',
                'fre' => 'Musique imprimée',
            ),
            'append' => array(
                array('eng' => 'Text'),
                array('fre' => 'Texte'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Printed and Manuscript Music',
                'fre' => 'Musique imprimée et manuscrite',
            ),
            'append' => array(
                array('eng' => 'Text'),
                array('fre' => 'Texte'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Manuscript Music',
                'fre' => 'Musique manuscrite',
            ),
            'append' => array(
                array('eng' => 'Text'),
                array('fre' => 'Texte'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Engraving',
                'fre' => 'Estampe',
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Drawing',
                'fre' => 'Dessin',
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Photograph',
                'fre' => 'Photographie',
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'replace' => array(
                'Document d’archives' => "Document d'archives",
            ),
            'translate' => array(
                'eng' => 'Archival Material',
                'fre' => "Document d'archives",
            ),
            'append' => array(
                array('eng' => 'Text'),
                array('fre' => 'Texte'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Cartographic Resource',
                'fre' => 'Document cartographique',
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Map',
                'fre' => 'Carte',
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Atlas',
                'fre' => 'Atlas',
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Diagram',
                'fre' => 'Diagramme',
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Globe',
                'fre' => 'Globe céleste',
            ),
            'langpairs' => array(
                'fre' => array('eng'),
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Globe',
                'fre' => 'Globe en fuseaux',
            ),
            'langpairs' => array(
                'fre' => array('eng'),
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Globe',
                'fre' => 'Globe terrestre',
            ),
            'langpairs' => array(
                'fre' => array('eng'),
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Model',
                'fre' => 'Maquette',
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Remote Sensing Image',
                'fre' => 'Image de télédétection',
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Section',
                'fre' => 'Coupe',
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'View',
                'fre' => 'Vue',
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),
        array(
            'translate' => array(
                'eng' => 'Plan',
                'fre' => 'Plan',
            ),
            'append' => array(
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),

        // Specific metadata.
        // This mapping is recommended by Bibliothèque interuniversitaire de la Sorbonne.
        // @link https://nubis.univ-paris1.fr
        array(
            'translate' => array(
                'eng' => 'Postal Card',
                'fre' => 'Carte postale',
            ),
            'append' => array(
                array('eng' => 'Still Image'),
                array('fre' => 'Image fixe'),
                array('eng' => 'Image'),
                array('fre' => 'Image'),
            ),
        ),

        array(
            'translate' => array(
                'eng' => 'Typescript',
                'fre' => 'Tapuscrit',
            ),
            'append' => array(
                array('eng' => 'Text'),
                array('fre' => 'Texte'),
            ),
        ),
    ),
);
