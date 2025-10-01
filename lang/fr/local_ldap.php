<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * local_ldap language strings.
 *
 * @package   local_ldap
 * @copyright 2013 Patrick Pollet
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


//#<-
$string['cohort_synching_ldap_groups_create_cohort_name_by_attribute'] = 'Créer les noms de cohorte à partir d\'un attribut personnalisé de groupe ldap';
$string['cohort_synching_ldap_groups_create_cohort_name_by_attribute_desc'] = 'Utiliser l\'attribut sélectionné pour le nom de cohort au lieu du nom du groupe (cn) ';
$string['group_attribute_description'] = 'Attribut personnalisé de nommage des cohortes';
$string['group_attribute_description_desc'] = 'Attribut du groupe Ldap à partir duquel on détermine le nom de la cohorte';
$string['group_filter'] = 'Filtre de groupe simple';
$string['group_filter_desc'] = 'Restreint la recherche ldap de groupes en appliquant ce filtre ldap simple';
$string['group_use_advanced_ldap_filter'] = 'Utiliser un filtre de groupe ldap avancé';
$string['group_use_advanced_ldap_filter_desc'] = 'Utiliser un filtre de groupe ldap avancé';
$string['group_advanced_filter'] = 'Filtre de groupe ldap avancé';
$string['group_advanced_filter_desc'] = 'Expression personnalisée de recherche de groupe ldap (rfc2254) ex:(cn=*)';
$string['group_contexts'] = 'Context (branche) ldap des groupes';
$string['group_contexts_desc'] = 'Sélection de contexte(s) ldap (;) comme séparateur';
$string['cohort_name_suffix'] = 'Suffixe de nom de cohorte';
$string['cohort_name_suffix_desc'] = 'Pour identifier les cohortes créées par le plugin (Utile lors de la purge afin de ne pas supprimer les cohortes manuelles)';
$string['cohortsdeltask'] = 'Suppression des cohortes ne correspondant à aucun groupe ldap en rapport au critère paramétré';
//#->

