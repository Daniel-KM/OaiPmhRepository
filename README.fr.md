OAI-PMH Repository (plugin pour Omeka)
======================================

[OAI-PMH Repository] est un plugin pour [Omeka] qui implémente un entrepôt au
protocole Open Archives Initiative Protocol for Metadata Harvesting ([OAI-PMH])
dans Omeka. permettant ainsi aux moissonneurs OAI-PMH de récupérer les contenus,
les collections et les fichiers. Le plugin implémente la version 2.0 du
protocole.


Installation
------------

Décompressez le zip dans le dossier du `plugins` et renommez-le `OaiPmhRepository`.

Consulter la documentation générale de l'utilisateur pour [Installer un plugin].


Configuration
-------------

### Identification

#### Url de base de l'entrepôt

Le point d'accès du service.

Valeur par défaut : oai-pmh-repository/request

#### Nom de l'entrepôt

Nom pour cet entrepôt OAI-PMH. Cette valeur est retournée en réponse aux
demandes d'identification et indique comment l'entrepôt est identifié par les
moissoneurs.

Valeur par défaut : nom de l'installation Omeka.

#### Identifiant de l'espace de nom

La spécification `oai-identifier` requiert que les entrepôts spécifient un
identifiant pour l'espace de nom. Il sera utilisé pour former des identifiants
uniques globalement  pour les métadonnées des contenus exposés. Cette valeur
doit être un nom de domaine que vous avez enregistré. L'utilisation d'autres
valeurs générera des identifiants non valides.

Défaut : s'il le peut, le plug-in essaiera de détecter automatiquement le
domaine du serveur hébergeant le site et de l'utiliser comme identifiant
d'espace de nom par défaut. Si un nom ne peut pas être détecté (par exemple, si
le site est accessible via le domaine `localhost`), la valeur par défaut sera
`default.must.change` (naturellement, cette valeur est destinée à être modifiée
et non pas à être utilisée telle quelle). Le plugin fonctionnera avec cette
chaîne, ou toute autre chaîne, comme identifiant d'espace de nom, mais cela
rompt l'hypothèse selon laquelle chaque identifiant est globalement unique. La
meilleure pratique consiste à définir cette valeur sur le nom de domaine sur
lequel le serveur Omeka est publié, éventuellement avec un préfixe du type
"oai".

### Exposition

#### Exposer les lots

Les lots (ou ensembles) permettent de rassembler des documents pour simplifier
le moissonage partiel. Trois types de lot peuvent être utilisés : collection,
type de contenu et la valeur du champ Dublin Core : Type.

Défaut: Collections

#### Exposes les fichiers

Indique si l'entreôt doit exposer des URL directes à tous les fichiers associés
à un élément dans le cadre des métadonnées renvoyées. Cela donne aux exploitants
la possibilité d'accéder directement au support décrit par les métadonnées.

Par défaut: vrai

### Masquer les collections vides

Indique si le plugin doit exposer les collections vides. Si cette option est
activée, seules les collections contenant au moins un document public seront
incluses dans la sortie ListSets. Si cette option est désactivée, tous les
contenus publics seront inclus dans la sortie ListSets.

Par défaut: vrai

#### Exposer le type de contenu

Indique si le plug-in doit exposer le type de contenu en tant que valeur Dublin Core : Type.

Par défaut: faux

#### Exposer la vignette

La vignette peut être exposée en tant que Dublin Core : Relation.

Par défaut : faux

### Identifiants des lots

Les lots OAI sont identifiés avec un identifiant unique, qui doit être différent
pour chaque type de lot. Vous devez donc vérifier s'il n'y a pas de nom en
doublon.
De plus, seuls certains caractères sont autorisés. Les noms interdits seront
ignorés. Les signes diacritiques sont supprimés dans les noms, mais ils peuvent
être utilisés s'ils sont gérés automatiquement par la base de données et php.

#### Format des identifiants des lots

Lorsque le format est hiérarchique, tous les identifiants des lots OAI sont
préfixés du type (collection, type de contenu ou dc:type). Lorsque le format est
plat, tous les identifiants de lots OAI sont simplement listés et mélangés.

Par défaut: hiérarchique

#### Format de l'identifiant de lot OAI pour les collections

Le format peut être `itemset_id` ou le premier Dublin Core : Identifier ou Title.
Dans tous les cas, ils sont normalisés (pas d'espaces, etc.).

#### Format de l'identifiant de lot OAI pour les types de contenu

Le format peut être `type_id` ou le nom du type de contenu.

### Personalisation

#### Métadonnées personnalisées pour oai_dc

Cette option permet d'exposer un format personnalisé pour oai_dc. Par défaut, ce
dernier suit les recommandations de la bibliothèque numérique [Europeana] et de
la [Bibliothèque nationale de France].

Les fichiers [`data/oaidc_custom_record.php`] et [`data/oaidc_custom_set.php`]
peuvent être adaptés pour compléter les données, par exemple pour ajouter un
nouveau type de document. Une aide complémentaire y est disponible.

Remarque : les types Dublin Core ne sont pas disponibles pour les versions
antérieures à Omeka 2.5 : la recherche n'est pas possible dans les lots dérivés.
Pour l'utiliser néanmoins, vous devez modifier le fichier `application/models/Table/Item.php` :
remplacez la méthode `Table_Item::_advancedSearch()` par celle de [Omeka 2.5] ou
d'une version ultérieure.

#### Langue par défaut pour les métadonnées personnalisées

Cette langue à trois lettres (ISO 639-2b) permet de définir la langue par défaut
des métadonnées afin de les traduire. Cette option est utilisée uniquement pour
normaliser les métadonnées personnalisées.

### Interface

#### Interface humaine

Toutes les pages OAI-PMH peuvent être affichées et consultées dans un navigateur
standard. Néanmoins, pour plus d'ergonomie, une interface adaptative pour les
personnes, basée sur [Bootstrap] est disponible et thémable. Pour adapter le
thème, créez un dossier `oai-pmh-repository/xsl` dans votre thème et copiez le
fichier [`oai-pmh-repository.xsl`] intérieur, puis modifiez-le. Le fichier css
peut également être copié et mis à jour.


Configuration avancée
---------------------

Le plugin permet également de configurer quelques options supplémentaires sur la
façon dont l'entreôt répond aux moissonneurs. Les valeurs par défaut sont celles
recommandées pour la plupart des utilisateurs. Elles peuvent être modifiées à la
main dans le fichier `config.ini` du dossier racine du plugin.

### Limite de réponse à une liste

Nombre de contenus individuels pouvant être renvoyés dans une réponse. Des
valeurs plus grandes nécessitent plus de mémoire, mais réduisent le nombre de
requêtes à la base de données et les requêtes HTTP. Des valeurs plus petites
réduisent l'usage de la mémoire mais augmentent le nombre de requêtes HTTP et de
requêtes de base de données.

Par défaut : 50

### Date d'expiration des listes

Durée en minutes pendant laquelle un jeton de continuation est valable. La
spécification suggère un nombre dans les dizaines de minutes. Cela correspond au
temps pendant lequel un moissoneur doit demander la suite d'une réponse paginée.

Par défaut : 10 (minutes)


Formats de métadonnées
----------------------

Le plugin propose plusieurs formats par défaut. D'autres plugins peuvent les
modifier ou en ajouter.

### Dublin Core (préfixe `oai_dc`)

Le Dublin Core est requis par la spécification OAI-PMH pour tous les entrepôts.
Les champs de métadonnées Omeka sont mappés un par un avec les champs pour ce
format.

### Dublin Core qualifié (`oai_qdc`)

Le format Dublin Core qualifié gère les éléments complémentaires ajoutés par le
Dublin Core Extended. Pour les installations qui n'utilisent pas les éléments
étendus, les métadonnées exposées sont les même que le format oai_dc.

*Ajouté dans la version 2.2 (officiel)*

### CDWA Lite (préfixe `cdwalite`)

Le mapping entre les champs Omeka et les champs CDWA Lite est plus compliqué et
certains champs peuvent ne pas être renseignés correctement. Le principal
avantage du format CDWA Lite est que les URL de fichier peuvent être générées
dans un format contrôlé, contrairement au Dublin Core. Les moissoneurs peuvent
donc récupérer des fichiers ou créer des liens vers ces fichiers en plus des
métadonnées.

### MODS (préfixe `mods`)

Ce format convertit les métadonnées Dublin Core en MODS en utilisant le mapping
recommandé par la Bibliothèque du Congrès.

### METS (préfixe `mets`)

Le format Metadata Encoding and Transmission Standard (standard d'encodage et de
transmission des métadonnées) expose les fichiers aux moissoneurs ainsi que les
métadonnées Dublin Core.

### RDF (préfixe `rdf`)

Ce format expose les métadonnées en tant que RDF / XML. Contrairement à la
plupart des autres formats, RDF permet à l'entrepôt d’exposer des métadonnées de
normes différentes, le tout dans la même sortie. La principale différence
pratique par rapport aux autres formats est que la sortie RDF inclut
automatiquement les données "qualifiées" du plugin [Dublin Core Extended], si
elles sont présentes.

### Omeka XML (préfixe `omeka-xml`)

Ce format de sortie utilise une sortie XML spécifique à Omeka qui inclut tous
les éléments de métadonnées sans nécessiter de croisement ni de sous-ensemble,
mais n'est pas bien pris en charge par les moissoneurs ou d'autres outils, et le
RRCHNM a lui-même supprimé ce schéma sur le dernier site.


Extension
---------

Le plugin fournit un filtre que d'autres plugins peuvent utiliser pour ajouter
de nouveaux formats de métadonnées ou remplacer ceux existants par de nouvelles
implémentations. Depuis la version 2.1, il n'est plus nécessaire d'ajouter ou de
modifier des fichiers dans le plugin lui-même pour modifier les formats
disponibles.

### Filtre `oai_pmh_repository_metadata_formats`

Le filtre ne transmet aucun paramètre supplémentaire. La valeur filtrée est un
tableau de tableaux, chaque tableau inférieur décrivant un seul format de
métadonnées. La clé dans le tableau externe est le préfixe de métadonnées du
format (c'est-à-dire `oai_dc` ou `rdf`). Chaque tableau interne a trois clés
obligatoires :

* `class` est le nom d'une classe implémentant `OaiPmhRepository_Metadata_FormatInterface`.
  Cette classe contient l'implémentation réelle du format.
* `namespace` est l'espace de noms XML du format.
* `schema` est l'emplacement du schéma XML pour le format.


Historique des versions
-----------------------

*2.1.1*

* Fixed a bug with XML special characters in Collection names
* New translations: Catalan, Czech, German, Mongolian, Polish, Portuguese (Brazil), Portuguese (Portugal), Serbian

*2.1*

* New RDF metadata format
* New `oai_pmh_repository_metadata_formats` filter to allow other plugins to add
  and modify metadata formats
* Localization support (contributed by [jajm](https://github.com/jajm))
* New option to exclude empty collections from ListSets (contributed by [Daniel-KM]
* New option to expose item type as Dublin Core Type value (contributed by [Daniel-KM
* More accurate "earliest datestamp" calculation (contributed by [Daniel-KM]
* Fixed "expose files" flag check for METS and omeka-xml outputs (contributed by
  [Daniel-KM]
* Additional miscellaneous cleanup (significant portions contributed by [Daniel-KM]

*2.0*

* Initial support for Omeka 2.0 and up
* File exposure support for METS


Avertissement
-------------

À utiliser à vos risques et périls.

Il est toujours recommandé de sauvegarder ses fichiers et ses bases de données
et de vérifier ses archives régulièrement afin de pouvoir les restaurer en cas
de besoin.

Dépannage
---------

Voir les problèmes en ligne sur la page [issues] du plugin sur GitHub.


Licence
-------

This plugin is published under [GNU/GPL].

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

**Interface humaine** (xslt stylesheet)

L'interface humaine est publiée sous la licence de type BSD [CeCILL-B].
Cf. l'entête du fichier pour les autres notes de licences.


Copyright
---------

Cf. les commits pour la liste complète des contributeurs.

* Copyright Yu-Hsun Lin, 2009
* Copyright John Flatness, 2009-2016
* Copyright Julian Maurice pour BibLibre, 2016-2017
* Copyright Daniel Berthereau, 2014-2019 (cf. [Daniel-KM])

Les améliorations de 2019 ont été réalisées pour la [Bibliothèque interuniversitaire de la Sorbonne].


[OAI-PMH Repository]: https://github.com/Daniel-KM/Omeka-S-plugin-OaiPmhRepository
[Omeka]: https://omeka.org/s
[OAI-PMH]: https://www.openarchives.org/OAI/openarchivesprotocol.html
[OAI-PMH Repository plugin]: https://github.com/omeka/plugin-OaiPmhRepository
[Omeka]: https://omeka.org/classic
[BibLibre]: https://github.com/biblibre
[Installer un plugin]: https://omeka.org/classic/docs/Admin/Adding_and_Managing_Plugins/
[Bootstrap]: https://getbootstrap.com
[Europeana]: https://pro.europeana.eu/resources/apis/oai-pmh-service
[Bibliothèque nationale de France]: http://www.BnF.fr/documents/Guide_oaipmh.pdf
[`data/oaidc_custom_record.php`]: https://github.com/Daniel-KM/Omeka-plugin-OaiPmhRepository/blob/master/data/oaidc_custom_record.php
[`data/oaidc_custom_set.php`]: https://github.com/Daniel-KM/Omeka-plugin-OaiPmhRepository/blob/master/data/oaidc_custom_set.php
[Omeka 2.5]: https://github.com/omeka/Omeka/blob/master/application/models/Table/Item.php#L79-L174
[`oai-pmh-repository.xsl`]: https://github.com/Daniel-KM/Omeka-plugin-OaiPmhRepository/blob/master/views/public/xsl/oai-pmh-repository.xsl
[Dublin Core]: http://dublincore.org
[Dublin Core Terms]: http://www.dublincore.org/documents/dcmi-terms/
[CDWA Lite]: https://www.getty.edu/research/publications/electronic_publications/cdwa/cdwalite.html
[MODS]: http://www.loc.gov/standards/mods/
[METS]: http://www.loc.gov/standards/mets/
[RDF]: https://www.w3.org/TR/rdf-syntax-grammar/
[Dublin Core Extended]: https://omeka.org/classic/plugins/DublinCoreExtended
[Omeka XML]: http://omeka.org/schemas/omeka-xml/v5/omeka-xml-5-0.xsd
[schema]: http://omeka.org/schemas/omeka-xml/v5/omeka-xml-5-0.xsd
[issues]: https://github.com/Daniel-KM/Omeka-plugin-OaiPmhRepository/issues
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[CeCILL-B]: https://www.cecill.info/licences/Licence_CeCILL-B_V1-en.html
[Bibliothèque interuniversitaire de la Sorbonne]: https://nubis.univ-paris1.fr
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
