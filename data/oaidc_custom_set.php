<?php
/**
 * Ajoute des sets dc:type à partir des valeurs existantes.
 *
 * À utiliser en complément du fichier `oaidc_custom_record.php` pour des
 * raisons de cohérence.
 *
 * Cette liste peut être complétée et modifié. Elle ne change pas les données
 * originales et permet donc d’exposer ses données directement.
 *
 * @copyright 2019, Daniel Berthereau pour la Bibliothèque de Paris I Sorbonne
 * @license CeCILL-B (https://www.cecill.info/licences/Licence_CeCILL-B_V1-en.html)
 */

/**
 * @lang eng
 *
 * Add sets for dc:type from existing values.
 *
 * To be used next to the file `oaidc_custom_record.php` to stay coherent.
 *
 * This list can be completed or updated. It doesn’t change original values, so
 * it can be used to expose metadata directly.
 *
 * @copyright 2019, Daniel Berthereau for Bibliothèque de Paris I Sorbonne
 * @license CeCILL-B (https://www.cecill.info/licences/Licence_CeCILL-B_V1-en.html)
 */

return array(
    // The process is designed for dc:type, but may be used for similar terms.
    // Values must be oai set compliant.
    // Keys are cleaned string of the database, lower cased and without
    // diacritic (generally automatically managed by the database as long as the
    // config of mariadb/mysql supports it (by default in recent versions). So
    // they are removed here for simplicity.
    // @see https://www.openarchives.org/OAI/openarchivesprotocol.html#Set
    'dc:type' => array(
        // Dublin Core types.
        'collection' => array(
        ),
        'dataset' => array(
            'jeu_de_donnees' =>'Jeu de données',
        ),
        'jeu_de_donnees' => array(
            'dataset' =>'Dataset',
        ),
        'event' => array(
            'evenement' =>'Événement',
        ),
        'evenement' => array(
            'event' =>'Event',
        ),
        'image' => array(
        ),
        'interactive_resource' => array(
            'ressource_interactive' =>'Resource interactive',
        ),
        'ressource_interactive' => array(
            'interactive_resource' =>'Interactive Resource',
        ),
/* // Commented, because the translation of the BnF is different below.
        'moving_image' => array(
            'image_animee' =>'Image animée',
        ),
        'image_animee' => array(
            'moving_image' =>'Moving Image',
        ),
 */
/* // Commented, because the translation of the BnF is different below.
        'physical_object' => array(
            'objet_physique' =>'Objet physique',
        ),
        'objet_physique' => array(
            'physical_object' =>'Physical Object',
        ),
 */
        'service' => array(
        ),
        'software' => array(
            'logiciel' =>'Logiciel',
        ),
        'logiciel' => array(
            'software' =>'Software',
        ),
/* // Commented, because the translation of the BnF is different below.
        'sound' => array(
            'son' =>'Son',
        ),
        'son' => array(
            'sound' =>'Sound',
        ),
 */
/* // Commented, because a value is appended below.
        'still_image' => array(
            'image_fixe' =>'Image Fixe',
        ),
        'image_fixe' => array(
            'still_image' =>'Still Image',
        ),
 */
        'text' => array(
            'texte' => 'Texte',
        ),
        'texte' => array(
            'text' =>'Text',
        ),

        // Specific metadata (BnF).
        'still_image' => array(
            'image_fixe' => 'Image fixe',
            'image' => 'Image',
        ),
        'image_fixe' => array(
            'still_image' => 'Still image',
            'image' => 'Image',
        ),
        // No reverse.
        'texte_imprime' => array(
            'text' => 'Text',
            'texte' => 'Texte',
        ),
        'printed_monograph' => array(
            'monographie_imprimee' => 'Monographie imprimée',
            'text' => 'Text',
            'texte' => 'Texte',

        ),
        'monographie_imprimee' => array(
            'printed_monograph' => 'Printed Monograph',
            'text' => 'Text',
            'texte' => 'Texte',

        ),
        'manuscrit' => array(
            'manuscript' => 'Manuscript',
            'text' => 'Text',
            'texte' => 'Texte',

        ),
        'manuscript' => array(
            'manuscrit' => 'Manuscrit' ,
            'text' => 'Text',
            'texte' => 'Texte',

        ),
        'printed_serial' => array(
            'publication_en_serie_imprimee' => 'Publication en série imprimée',
            'text' => 'Text',
            'texte' => 'Texte',

        ),
        'publication_en_serie_imprimee' => array(
            'printed_serial' => 'Printed Serial',
            'text' => 'Text',
            'texte' => 'Texte',

        ),
        'sound' => array(
            'document_sonore' => 'Document sonore',
        ),
        'document_sonore' => array(
            'sound' => 'Sound',
        ),
        'moving_image' => array(
            'images_animees' => 'Image animées',
            'video' => 'Video',
        ),
        'images_animees' => array(
            'moving_image' => 'Moving Image',
            'video' => 'Video',
        ),
        'physical_object' => array(
            'objet' => 'Objet',
            'image' => 'Image',
        ),
        'objet' => array(
            'physical_object' => 'Physical Object',
            'image' => 'Image',
        ),
        // No reverse.
        'monnaie' => array(
            'physical_object' => 'Physical Object',
            'image' => 'Image',
        ),
        'printed_music' => array(
            'musique_imprimee' => 'Musique imprimée',
            'text' => 'Text',
            'texte' => 'Texte',

        ),
        'musique_imprimee' => array(
            'printed_music' => 'Printed Music',
            'text' => 'Text',
            'texte' => 'Texte',

        ),
        'printed_and_manuscript_music' => array(
            'musique_imprimee_et_manuscrite' => 'Musique imprimée et manuscrite',
            'text' => 'Text',
            'texte' => 'Texte',

        ),
        'musique_imprimee_et_manuscrite' => array(
            'printed_and_manuscript_music' => 'Printed and Manuscript Music',
            'text' => 'Text',
            'texte' => 'Texte',

        ),
        'manuscript_music' => array(
            'musique_manuscrite' => 'Musique Manuscrite',
            'text' => 'Text',
            'texte' => 'Texte',

        ),
        'musique_manuscrite' => array(
            'manuscript_music' => 'Manuscript Music',
            'text' => 'Text',
            'texte' => 'Texte',

        ),
        'engraving' => array(
            'estampe' => 'Estampe',
            'image' => 'Image',
        ),
        'estampe' => array(
            'engraving' => 'Engraving',
            'image' => 'Image',
        ),
        'drawing' => array(
            'dessin' => 'Dessin',
            'image' => 'Image',
        ),
        'dessin' => array(
            'drawing' => 'Drawing',
            'image' => 'Image',
        ),
        'photograph' => array(
            'photographie' => 'Photographie',
            'image' => 'Image',
        ),
        'photographie' => array(
            'photograph' => 'Photograph',
            'image' => 'Image',
        ),
        'document_d_archives' => array(
            'archival_material' => 'Archival Material',
            'text' => 'Text',
            'texte' => 'Texte',

        ),
        'archival_material' => array(
            'document_d_archives' => "Document d'archives",
            'text' => 'Text',
            'texte' => 'Texte',

        ),
        'cartographic_resource' => array(
            'document_cartographique' => 'Document cartographique',
            'image' => 'Image',
        ),
        'document_cartographique' => array(
            'cartographic_resource' => 'Cartographic Resource',
            'image' => 'Image',
        ),
        'map' => array(
            'carte' => 'Carte',
            'image' => 'Image',
        ),
        'carte' => array(
            'map' => 'Map',
            'image' => 'Image',
        ),
        'atlas' => array(
            'atlas' => 'Atlas',
            'image' => 'Image',
        ),
        'diagram' => array(
            'diagramme' => 'Diagramme',
            'image' => 'Image',
        ),
        'diagramme' => array(
            'diagram' => 'Diagram',
            'image' => 'Image',
        ),
        // No reverse.
        'globe_celeste' => array(
            'globe' => 'Globe',
            'image' => 'Image',
        ),
        // No reverse.
        'globe_en_fuseaux' => array(
            'globe' => 'Globe',
            'image' => 'Image',
        ),
        // No reverse.
        'globe_terrestre' => array(
            'globe' => 'Globe',
            'image' => 'Image',
        ),
        'model' => array(
            'maquette' => 'Maquette',
            'image' => 'Image',
        ),
        'maquette' => array(
            'model' => 'Model',
            'image' => 'Image',
        ),
        'remote_sensing_image' => array(
            'image_de_teledetection' => 'Image de télédétection',
            'image' => 'Image',
        ),
        'image_de_teledetection' => array(
            'remote_sensing_image' => 'Remote Sensing Image',
            'image' => 'Image',
        ),
        'section' => array(
            'coupe' => 'Coupe',
            'image' => 'Image',
        ),
        'coupe' => array(
            'section' => 'Section',
            'image' => 'Image',
        ),
        'view' => array(
            'vue' => 'Vue',
            'image' => 'Image',
        ),
        'vue' => array(
            'view' => 'View',
            'image' => 'Image',
        ),
        'plan' => array(
            'image' => 'Image',
        ),

        // Specific metadata.
        // This mapping is recommended by Bibliothèque interuniversitaire de la Sorbonne.
        // @link https://nubis.univ-paris1.fr
        'postal_card' => array(
            'carte_postale' => 'Carte postale',
            'still_image' => 'Still Image',
            'image_fixe' => 'Image Fixe',
            'image' => 'Image',
        ),
        'carte_postale' => array(
            'postal_card' => 'Postal Card',
            'still_image' => 'Still Image',
            'image_fixe' => 'Image Fixe',
            'image' => 'Image',
        ),

        'tapuscrit' => array(
            'typescript' => 'Typescript' ,
            'text' => 'Text',
            'texte' => 'Texte',
        ),
        'typescript' => array(
            'tapuscript' => 'Tapuscrit',
            'text' => 'Text',
            'texte' => 'Texte',
        ),
    ),
);
