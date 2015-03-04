<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage TAGS
 */

 /**
 * Find a tag searching by tag's or description name. 
 * 
 * @param string $tag_name_description Name or description of the tag that it's currently searched. 
 * @param array $filter Array with pagination parameters. 
 * @param bool $only_names Whether to return only names or all fields.
 * 
 * @return mixed Returns an array with the tag selected by name or false.
 */
function tags_search_tag ($tag_name_description = false, $filter = false, $only_names = false) {
	global $config;
	
	if ($tag_name_description) {
		switch ($config["dbtype"]) {
			case "mysql":
				$sql = 'SELECT *
					FROM ttag
					WHERE ((name COLLATE utf8_general_ci LIKE "%'. $tag_name_description .'%") OR 
						(description COLLATE utf8_general_ci LIKE "%'. $tag_name_description .'%")) ORDER BY name';
				break;
			case "postgresql":
				$sql = 'SELECT *
					FROM ttag
					WHERE ((name COLLATE utf8_general_ci LIKE \'%'. $tag_name_description .'%\') OR
						(description COLLATE utf8_general_ci LIKE \'%'. $tag_name_description .'%\')) ORDER BY name';
				break;
			case "oracle":
				$sql = 'SELECT *
					FROM ttag
					WHERE (UPPER(name) LIKE UPPER (\'%'. $tag_name_description .'%\') OR
						UPPER(dbms_lob.substr(description, 4000, 1)) LIKE UPPER (\'%'. $tag_name_description .'%\')) ORDER BY name';
				break;
		}
	}
	else {
		$sql = 'SELECT * FROM ttag ORDER BY name';
	}
	if ($filter !== false) {
		switch ($config["dbtype"]) {
			case "mysql":
				$result = db_get_all_rows_sql ($sql . ' LIMIT ' . $filter['offset'] . ',' . $filter['limit']);
				break;
			case "postgresql":
				$result = db_get_all_rows_sql ($sql . ' OFFSET ' . $filter['offset'] . ' LIMIT ' . $filter['limit']);
				break;
			case "oracle":
				$result = oracle_recode_query ($sql, $filter, 'AND', false);
				if ($components != false) {
					for ($i=0; $i < count($components); $i++) {
						unset($result[$i]['rnum']);
					}
				}
				break;
		}
	}
	else {
		$result = db_get_all_rows_sql ($sql);
	}
	
	if ($result === false)
		$result = array();
	
	if ($only_names) {
		$result_tags = array();
		foreach ($result as $tag) {
			$result_tags[$tag['id_tag']] = $tag['name'];
		}
		$result = $result_tags;
	}
	
	return $result;
}

/**
 * Create a new tag. 
 * 
 * @param array $values Array with all values to insert. 
 *
 * @return mixed Tag id or false.
 */
function tags_create_tag($values) {
	if (empty($values)) {
		return false;
	}

	//No create tag if the tag exists	
	if (tags_get_id($values["name"])) {
		return false;
	}
	
	return db_process_sql_insert('ttag',$values);
}

/**
 * Search tag by id. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Array with the seleted tag or false.
 */
function tags_search_tag_id($id) {
	return db_get_row ('ttag', 'id_tag', $id);
}

/**
 * Get tag name. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed String with tag name or false.
 */
function tags_get_name($id) {
	return db_get_value_filter ('name', 'ttag', array('id_tag' => $id));
}

/**
 * Get tag id given the tag name. 
 * 
 * @param string Tag name.
 *
 * @return int Tag id.
 */
function tags_get_id($name) {
	return db_get_value_filter ('id_tag', 'ttag', array('name' => $name));
}

/**
 * Get tag description. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed String with tag description or false.
 */
function tags_get_description($id) {
	return db_get_value_filter('description', 'ttag', array('id_tag' => $id));
}

/**
 * Get tag url. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed String with tag url or false.
 */
function tags_get_url($id) {
	return db_get_value_filter('description', 'ttag', array('id_tag' => $id));
}

/**
 * Get tag's module count. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Int with the tag's count or false.
 */
function tags_get_modules_count($id) {
	$num_modules = (int)db_get_value_filter('count(*)', 'ttag_module', array('id_tag' => $id));
	$num_policy_modules = (int)db_get_value_filter('count(*)', 'ttag_policy_module', array('id_tag' => $id));
	
	return $num_modules + $num_policy_modules;
}

/**
 * Get tag's local module count. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Int with the tag's count or false.
 */
function tags_get_local_modules_count($id) {
	$num_modules = (int)db_get_value_filter('count(*)', 'ttag_module', array('id_tag' => $id));

	return $num_modules;
}

/**
 * Get tag's local module count. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Int with the tag's count or false.
 */
function tags_get_modules_tag_count($id) {
	$num_modules = (int)db_get_value_filter('count(*)', 'ttag_module', array('id_agente_modulo' => $id));
	
	return $num_modules;
}

/**
 * Get tag's policy module count. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Int with the tag's count or false.
 */
function tags_get_policy_modules_count($id) {
	$num_policy_modules = (int)db_get_value_filter('count(*)', 'ttag_policy_module', array('id_tag' => $id));
	
	return $num_policy_modules;
}



/**
 * Updates a tag by id. 
 * 
 * @param array $id Int with tag id info. 
 * @param string $where Where clause to update record.
 *
 * @return bool True or false if something goes wrong.
 */
function tags_update_tag($values, $where) {
	return db_process_sql_update ('ttag', $values, $where);
}

/**
 * Delete a tag by id. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return bool True or false if something goes wrong.
 */
function tags_delete_tag ($id_tag) {
	$errn = 0;
	
	$result_tag = db_process_delete_temp ('ttag', 'id_tag', $id_tag);
	if ($result_tag === false)
		$errn++;
	
	$result_module = db_process_delete_temp ('ttag_module', 'id_tag', $id_tag);
	if ($result_module === false)
		$errn++;
	
	$result_policy = db_process_delete_temp ('ttag_policy_module', 'id_tag', $id_tag);
	if ($result_policy === false)
		$errn++;
	
	if ($errn == 0) {
		return true;
	}
	else {
		return false;
	}
	
}

function tags_remove_tag($id_tag, $id_module) {
	$result = (bool)db_process_sql_delete('ttag_module',
		array('id_tag' => $id_tag, 
			'id_agente_modulo' => $id_module));
	
	return $result;
}

/**
 * Get tag's total count.  
 *
 * @return mixed Int with the tag's count.
 */
function tags_get_tag_count() {
	return (int)db_get_value('count(*)', 'ttag');
}

/**
 * Inserts tag's array of a module. 
 * 
 * @param int $id_agent_module Module's id.
 * @param array $tags Array with tags to associate to the module. 
 *
 * @return bool True or false if something goes wrong.
 */
function tags_insert_module_tag ($id_agent_module, $tags) {
	$errn = 0;
	
	$values = array();
	
	if($tags == false) {
		$tags = array();
	}
	
	foreach ($tags as $tag) {
		//Protect against default insert
		if (empty($tag))
			continue;
		
		$values['id_tag'] = $tag;
		$values['id_agente_modulo'] = $id_agent_module;
		$result_tag = db_process_sql_insert('ttag_module', $values);
		if ($result_tag === false)
			$errn++;
	}
	
	return $errn;
}

/**
 * Inserts tag's array of a policy module. 
 * 
 * @param int $id_agent_module Policy module's id.
 * @param array $tags Array with tags to associate to the module. 
 *
 * @return bool True or false if something goes wrong.
 */
function tags_insert_policy_module_tag ($id_agent_module, $tags) {
	$errn = 0;
	
	$values = array();
	foreach ($tags as $tag) {
		//Protect against default insert
		if (empty($tag))
			continue;
		
		$values['id_tag'] = $tag;
		$values['id_policy_module'] = $id_agent_module;
		$result_tag = db_process_sql_insert('ttag_policy_module', $values, false);
		if ($result_tag === false)
			$errn++;
	}
	
	if ($errn > 0) {
		return false;
	}
	else {
		return true;
	}
}

/**
 * Updates tag's array of a module. 
 * 
 * @param int $id_agent_module Module's id.
 * @param array $tags Array with tags to associate to the module. 
 * @param bool $autocommit Whether to do automatical commit or not.
 * 
 * @return bool True or false if something goes wrong.
 */
function tags_update_module_tag ($id_agent_module, $tags,
	$autocommit = false, $update_policy_tags = true) {
	$errn = 0;
	
	if (empty($tags))
		$tags = array();
	
	if ($update_policy_tags) {
		/* First delete module tag entries */
		$result_tag = db_process_sql_delete('ttag_module',
			array('id_agente_modulo' => $id_agent_module));
	}
	else {
		$result_tag = db_process_sql_delete('ttag_module',
			array('id_agente_modulo' => $id_agent_module,
				'id_policy_module' => '0'));
	}
	
	$values = array();
	foreach ($tags as $tag) {
		//Protect against default insert
		if (empty($tag))
			continue;
		
		$values['id_tag'] = $tag;
		$values['id_agente_modulo'] = $id_agent_module;
		$result_tag = db_process_sql_insert('ttag_module', $values, false);
		if ($result_tag === false)
			$errn++;
	}
	
}

/**
 * Updates tag's array of a policy module. 
 * 
 * @param int $id_policy_module Policy module's id.
 * @param array $tags Array with tags to associate to the module. 
 * @param bool $autocommit Whether to do automatical commit or not.
 * 
 * @return bool True or false if something goes wrong.
 */
function tags_update_policy_module_tag ($id_policy_module, $tags, $autocommit = false) {
	$errn = 0;
	
	if (empty($tags))
		$tags = array();
	
	/* First delete module tag entries */
	$result_tag = db_process_sql_delete ('ttag_policy_module', array('id_policy_module' => $id_policy_module));
	
	$values = array();
	foreach ($tags as $tag) {
		//Protect against default insert
		if (empty($tag))
			continue;
		
		$values['id_tag'] = $tag;
		$values['id_policy_module'] = $id_policy_module;
		$result_tag = db_process_sql_insert('ttag_policy_module', $values, false);
		if ($result_tag === false)
			$errn++;
	}
	
}

/**
 * Select all tags of a module. 
 * 
 * @param int $id_agent_module Module's id.
 *
 * @return mixed Array with module tags or false if something goes wrong.
 */
function tags_get_module_tags ($id, $policy = false) {
	if (empty($id))
		return false;
	
	if ($policy) {
		$tags = db_get_all_rows_filter('ttag_policy_module',
			array('id_policy_module' => $id), false);
	}
	else {
		$tags = db_get_all_rows_filter('ttag_module',
			array('id_agente_modulo' => $id), false);
	}
	
	if ($tags === false)
		return array();
	
	$return = array();
	foreach ($tags as $tag) {
		$return[] = $tag['id_tag'];
	}
	
	return $return;
}

/**
 * Select all tags of a policy module. 
 * 
 * @param int $id_policy_module Policy module's id.
 *
 * @return mixed Array with module tags or false if something goes wrong.
 */
function tags_get_policy_module_tags ($id_policy_module) {
	if (empty($id_policy_module))
		return false;
	
	$tags = db_get_all_rows_filter('ttag_policy_module',
		array('id_policy_module' => $id_policy_module), false);
	
	if ($tags === false)
		return false;
	
	$return = array();
	foreach ($tags as $tag) {
		$return[] = $tag['id_tag'];
	}
	
	return $return;
}

/**
 * Select all tags.
 *
 * @return mixed Array with tags.
 */
function tags_get_all_tags ($return_url = false) {
	$tags = db_get_all_fields_in_table('ttag', 'name', '', 'name');
	
	if ($tags === false)
		return false;
	
	$return = array();
	foreach ($tags as $tag) {
		$return[$tag['id_tag']] = $tag['name'];
		if ($return_url) {
			$return[$tag['id_tag']] .= ' ' . $tag['url'];
		}
	}
	
	return $return;
}

/**
 * Get the tags required
 *
 * @return mixed Array with tags.
 */
function tags_get_tags ($ids) {
	$all_tags = tags_get_all_tags(true);
	
	$tags = array();
	foreach ($ids as $id) {
		if (isset($all_tags[$id])) {
			$tags[$id] = $all_tags[$id];
		}
	}
	
	return $tags;
}

function tags_get_agents($id_tag, $id_policy_module = 0) {
	
	$agents = db_get_all_rows_sql("
		SELECT id_agente
		FROM tagente
		WHERE id_agente IN (
			SELECT t1.id_agente
			FROM tagente_modulo AS t1
			WHERE t1.id_agente_modulo IN (
				SELECT t2.id_agente_modulo
				FROM ttag_module AS t2
				WHERE id_tag = " . $id_tag . "
					AND id_policy_module = " . $id_policy_module . "))");
	
	if (empty($agents)) {
		return array();
	}
	
	
	$temp = array();
	foreach ($agents as $agent) {
		$temp[] = $agent['id_agente'];
	}
	$agents = $temp;
	
	return $agents;
}

/**
 * Give format to tags when go concatened with url.
 *
 * @param string name of tags serialized
 * @param bool flag to return the url or not
 * 
 * @return string Tags with url format
 */
function tags_get_tags_formatted ($tags_array, $get_url = true) {
	if(!is_array($tags_array)) {
		$tags_array = explode(',',$tags_array);
	}
	
	$tags = array();
	foreach($tags_array as $t) {
		$tag_url = explode(' ', trim($t));
		$tag = $tag_url[0];
		if(isset($tag_url[1]) && $tag_url[1] != '' && $get_url) {
			$title = $tag_url[1];
			//$link = '<a href="'.$tag_url[1].'" target="_blank">'.html_print_image('images/zoom.png',true, array('alt' => $title, 'title' => $title)).'</a>';
			$link = '<a href="javascript: openURLTagWindow(\'' . $tag_url[1] . '\');">' . html_print_image('images/zoom.png', true, array('title' => __('Click here to open a popup window with URL tag'))) . '</a>';
		
		}
		else {
			$link = '';
		}
		
		$tags[] = $tag.$link;
	}
	
	$tags = implode(',',$tags);
	
	$tags = str_replace(',',' , ',$tags);
	
	return $tags;
}

/**
 * Get the tags (more restrictive) of an access flag in a group
 *
 * @param string id of the user
 * @param string id of the group
 * @param string access flag (AR,AW...)
 * @param string return_mode 
 * 			- 'data' for return array with groups and tags
 * 			- 'module_condition' for return string with sql condition for tagente_module
 * 			- 'event_condition' for return string with sql condition for tevento
 * 
 * @return mixed/string Tag ids
 */
 
function tags_get_acl_tags($id_user, $id_group, $access = 'AR', $return_mode = 'module_condition', $query_prefix = '', $query_table = '', $meta = false, $childrens_ids = array(), $force_group_and_tag = false) {
	
	global $config;
	
	if ($id_user == false) {
		$id_user = $config['id_user'];
	}
	
	if (is_user_admin ($id_user)) {
		switch ($return_mode) {
			case 'data':
				return array();
				break;
			case 'event_condition':
			case 'module_condition':
				return "";
				break;
		}
	}
	
	if ($id_group[0] != 0) {
		$id_group = groups_get_all_hierarchy_group ($id_group[0]);
	}
	
	if ((string)$id_group === "0") {
		$id_group = array_keys(users_get_groups($id_user, $access, false));
		
		if (empty($id_group)) {
			return ERR_WRONG_PARAMETERS;
		}
	}
	elseif (empty($id_group)) {
		return ERR_WRONG_PARAMETERS;
	}
	elseif (!is_array($id_group)) {
		$id_group = (array) $id_group;
	}
	
	$acl_column = get_acl_column($access);
	
	if (empty($acl_column)) {
		return ERR_WRONG_PARAMETERS;
	}
	
	$query = sprintf("SELECT tags, id_grupo 
			FROM tusuario_perfil, tperfil
			WHERE tperfil.id_perfil = tusuario_perfil.id_perfil AND
				tusuario_perfil.id_usuario = '%s' AND 
				tperfil.%s = 1 AND
			(tusuario_perfil.id_grupo IN (%s) OR tusuario_perfil.id_grupo = 0)
			ORDER BY id_grupo", $id_user, $acl_column, implode(',',$id_group));
	$tags = db_get_all_rows_sql($query);

	// If not profiles returned, the user havent acl permissions
	if (empty($tags)) {
		return ERR_ACL;
	}
	
	// Array to store groups where there arent tags restriction
	$non_restriction_groups = array();
	
	$acltags = array();
	foreach ($tags as $tagsone) {
		if ($force_group_and_tag) {
			if (empty($tagsone['tags'])) {
				// Do none
			}
		}
		else {
			if (empty($tagsone['tags'])) {
				// If there arent tags restriction in all groups (group 0), return no condition
				if ($tagsone['id_grupo'] == 0) {
					switch ($return_mode) {
						case 'data':
							return array();
							break;
						case 'event_condition':
						case 'module_condition':
							return "";
							break;
					}
				}
				
				$non_restriction_groups[] = $tagsone['id_grupo'];
				continue;
			}
		}
		
		$tags_array = explode(',',$tagsone['tags']);
		if ($force_group_and_tag) {
			if (empty($tagsone['tags'])) {
				$tags_array = array();
			}
		}
		
		if (!isset($acltags[$tagsone['id_grupo']])) {
			$acltags[$tagsone['id_grupo']] = $tags_array;
		}
		else {
			$acltags[$tagsone['id_grupo']] = array_unique(array_merge($acltags[$tagsone['id_grupo']], $tags_array));
		}
	}
	
	// Delete the groups without tag restrictions from the acl tags array
	foreach ($non_restriction_groups as $nrgroup) {
		if (isset($acltags[$nrgroup])) {
			unset($acltags[$nrgroup]);
		}
	}
	
	switch ($return_mode) {
		case 'data':
			// Stop here and return the array
			return $acltags;
			break;
		case 'module_condition':
			// Return the condition of the tags for tagente_modulo table
			$condition = tags_get_acl_tags_module_condition($acltags, $query_table, true);
			if (!empty($condition)) {
				return " $query_prefix " . $condition;
			}
			break;
		case 'event_condition':
			// Return the condition of the tags for tevento table
			$condition = tags_get_acl_tags_event_condition($acltags, $meta, $force_group_and_tag);
			if (!empty($condition)) {
				return " $query_prefix " . "(" . $condition . ")";
			}
			break;
	}
	
	return "";
}

/**
 * Transform the acl_groups data into a SQL condition
 * 
 * @param mixed acl_groups data calculated in tags_get_acl_tags function
 * 
 * @return string SQL condition for tagente_module
 */
 
function tags_get_acl_tags_module_condition($acltags, $modules_table = '') {
	if (!empty($modules_table)) {
		$modules_table .= '.';
	}
	
	$condition = '';
	
	// Fix: Wrap SQL expression with "()" to avoid bad SQL sintax that makes Pandora retrieve all modules without taking care of id_agent => id_agent = X AND (sql_tag_expression)   
	$i = 0;
	foreach ($acltags as $group_id => $group_tags) {
		if ($condition != '') {
			$condition .= ' OR ';
		}
		
		// Fix: Wrap SQL expression with "()" to avoid bad SQL sintax that makes Pandora retrieve all modules without taking care of id_agent => id_agent = X AND (sql_tag_expression) 
		if ($i == 0)
			$condition .= ' ( ';
		
		// Group condition (The module belongs to an agent of the group X)
		// Juanma (08/05/2014) Fix: Now group and tag is checked at the same time, before only tag was checked due to a bad condition
		if (!array_key_exists(0, $acltags)) {
			// Juanma (08/05/2014) Fix: get all groups recursively (Acl proc func!)
			$group_condition = sprintf('%sid_agente IN (SELECT id_agente FROM tagente WHERE id_grupo IN (%s))', $modules_table, implode(',', array_values(groups_get_id_recursive($group_id))));
		}
		else {
			//Avoid the user profiles with all group access.
			$group_condition = " 1 = 1 ";
		}
			
		//When the acl is only group without tags
		if (empty($group_tags)) {
			$condition .= "($group_condition)\n";
		}
		else {
			if (is_array($group_tags)) {
				$group_tags_query = implode(',',$group_tags);
			} else {
				$group_tags_query = $group_tags;
			}
			// Tags condition (The module has at least one of the restricted tags)
			$tags_condition = sprintf('%sid_agente_modulo IN (SELECT id_agente_modulo FROM ttag_module WHERE id_tag IN (%s))', $modules_table, $group_tags_query);
			
			$condition .= "($group_condition AND \n$tags_condition)\n";
		}
				
		$i++;
	}
	
	// Fix: Wrap SQL expression with "()" to avoid bad SQL sintax that makes Pandora retrieve all modules without taking care of id_agent => id_agent = X AND (sql_tag_expression) 
	if (!empty($acltags))
		$condition .= ' ) ';
	
	//Avoid the user profiles with all group access.
	//if (!empty($condition)) {
	if (!empty($condition) &&
		!array_key_exists(0, array_keys($acltags))) {
		$condition = sprintf("\n((%s) OR %sid_agente NOT IN (SELECT id_agente FROM tagente WHERE id_grupo IN (%s)))", $condition, $modules_table, implode(',',array_keys($acltags)));
	}
	
	return $condition;
}

/**
 * Transform the acl_groups data into a SQL condition
 * 
 * @param mixed acl_groups data calculated in tags_get_acl_tags function
 * 
 * @return string SQL condition for tagente_module
 */
 
function tags_get_acl_tags_event_condition($acltags, $meta = false, $force_group_and_tag = false) {

	global $config;
	$condition = '';

	// Get all tags of the system
	$all_tags = tags_get_all_tags(false);
	
	// Juanma (08/05/2014) Fix : Will have all groups  retrieved (also propagated ones)
	$_groups_not_in = '';

	foreach ($acltags as $group_id => $group_tags) {
		// Group condition (The module belongs to an agent of the group X)
		// Juanma (08/05/2014) Fix : Get all groups (children also, Propagate ACL func!)
		$group_condition = sprintf('id_grupo IN (%s)', implode(',', array_values(groups_get_id_recursive($group_id))));
		$_groups_not_in .= implode(',', array_values(groups_get_id_recursive($group_id))) . ','; 
		
		// Tags condition (The module has at least one of the restricted tags)
		$tags_condition = '';
		if (empty($group_tags)) {
			$tags_condition = "id_grupo = ".$group_id;
		} else {
			if (!is_array($group_tags)) {
				$group_tags = explode(',', $group_tags);
			}

			foreach ($group_tags as $tag) {
				// If the tag ID doesnt exist, ignore
				if (!isset($all_tags[$tag])) {
					continue;
				}
				
				if ($tags_condition != '') {
					$tags_condition .= " OR \n";
				}
				
				//~ // Add as condition all the posibilities of the serialized tags
				//~ $tags_condition .= sprintf('tags LIKE "%s,%%"',io_safe_input($all_tags[$tag]));
				//~ $tags_condition .= sprintf(' OR tags LIKE "%%,%s,%%"',io_safe_input($all_tags[$tag]));
				//~ $tags_condition .= sprintf(' OR tags LIKE "%%,%s"',io_safe_input($all_tags[$tag]));
				//~ $tags_condition .= sprintf(' OR tags LIKE "%s %%"',io_safe_input($all_tags[$tag]));
				//~ $tags_condition .= sprintf(' OR tags LIKE "%%,%s %%"',io_safe_input($all_tags[$tag]));
				
				if ($force_group_and_tag) {
					if (!empty($all_tags[$tag])) {
						//~ $tags_condition .= sprintf('(tags = "%s"',io_safe_input($all_tags[$tag]));
						$tags_condition .= "(tags LIKE '%".io_safe_input($all_tags[$tag])."%'";
						$childrens = groups_get_childrens($group_id, null, true);

						if (empty($childrens)) {
							$tags_condition .= sprintf(' AND id_grupo = %d )', $group_id);
						} else {
							$childrens_ids[] = $group_id;
							foreach ($childrens as $child) {
								$childrens_ids[] = (int)$child['id_grupo'];
							}
							$ids_str = implode(',', $childrens_ids);

							$tags_condition .= sprintf(' AND id_grupo IN (%s) )', $ids_str);
						}
					} else {
						$tags_condition .= "id_grupo = ".$group_id;
					}
				} else {
					//~ $tags_condition .= sprintf('tags = "%s"',io_safe_input($all_tags[$tag]));
					$tags_condition .= "tags LIKE '%".io_safe_input($all_tags[$tag])."%'";
				}
			}
		}
		
		// If there is not tag condition ignore
		if (empty($tags_condition)) {
			continue;
		}
		
		if ($condition != '') {
			$condition .= ' OR ';
		}
		
		$condition .= "($tags_condition)\n";
	}
	
	//Commented because ACLs propagation don't work
/*
	if (!empty($condition)) {
		// Juanma (08/05/2014) Fix : Also add events of other groups (taking care of propagate ACLs func!)
		if (!empty($_groups_not_in))
			$condition = sprintf("\n((%s) OR id_grupo NOT IN (%s))", $condition, rtrim($_groups_not_in, ','));
	}
*/
	
	return $condition;
}

/**
 * Check if a user has assigned acl tags or not (if is admin, is like not acl tags)
 * 
 * @param string ID of the user (with false the user will be taked from config)
 * 
 * @return bool true if the user has tags and false if not
 */
function tags_has_user_acl_tags($id_user = false) {
	global $config;
	
	if($id_user === false) {
		$id_user = $config['id_user'];
	}
	
	if(is_user_admin($id_user)) {
		return false;
	}
	
	$query = sprintf("SELECT count(*) 
			FROM tusuario_perfil, tperfil
			WHERE tperfil.id_perfil = tusuario_perfil.id_perfil AND
			tusuario_perfil.id_usuario = '%s' AND tags != ''", 
			$id_user);
			
	$user_tags = db_get_value_sql($query);
	
	return (bool)$user_tags;
}

/**
 * Get the tags of a user in an ACL flag
 * 
 * @param string ID of the user (with false the user will be taked from config)
 * @param string Access flag where check what tags have the user
 * 
 * @return string SQL condition for tagente_module
 */
function tags_get_user_tags($id_user = false, $access = 'AR') {
	global $config;

	if ($id_user === false) {
		$id_user = $config['id_user'];
	}
	
	// Get all tags to have the name of all of them
	$all_tags = tags_get_all_tags();

	// If at least one of the profiles of this access flag hasent
	// tags restrictions, the user can see all tags
	$acl_column = get_acl_column($access);
	
	if(empty($acl_column)) {
		return array();
	}

	$query = sprintf("SELECT count(*) 
			FROM tusuario_perfil, tperfil
			WHERE tperfil.id_perfil = tusuario_perfil.id_perfil AND
			tusuario_perfil.id_usuario = '%s' AND 
			tperfil.%s = 1 AND tags <> ''", 
			$id_user, $acl_column);
			
	$profiles_without_tags = db_get_value_sql($query);
	
	if ($profiles_without_tags == 0) {
		return $all_tags;
	}

	// Get the tags of the required access flag for each group
	$tags =  tags_get_acl_tags($id_user, 0, $access, 'data','','', true, array(), true);
	// If there are wrong parameters or fail ACL check, return false
	if ($tags_user === ERR_WRONG_PARAMETERS || $tags_user === ERR_ACL) {
		return array();
	}

	// Merge the tags to get an array with all of them
	$user_tags_id = array();
	
	foreach ($tags as $t) {
		if(empty($user_tags_id)) {
			$user_tags_id = $t;
		}
		else {
			$user_tags_id = array_unique(array_merge($t,$user_tags_id));
		}
	}
	
	// Set the format id=>name to tags
	$user_tags = array();
	foreach ($user_tags_id as $id) {
		if (!isset($all_tags[$id])) {
			continue;
		}
		$user_tags[$id] = $all_tags[$id];
	}
	
	
	return $user_tags;
}

function tags_check_acl_by_module($id_module = 0, $id_user = false,
	$access = 'AW') {
	
	global $config;
	
	
	$return = false;
	
	if (!empty($id_module)) {
		$tags = tags_get_module_tags($id_module);
		$group = modules_get_agent_group($id_module);
		
		if ($id_user === false) {
			$id_user = $config["id_user"];
		}
		
		$return = tags_check_acl($id_user, $group, $access, $tags, true);
	}
	
	return $return;
}

/**
 * Check the ACLs with tags
 * 
 * @param string ID of the user (with false the user will be taked from config)
 * @param string id of the group (0 means for at least one)
 * @param string access flag (AR,AW...)
 * @param mixed tags to be checked (array() means for at least one)
 * 
 * @return bool true if the acl check has success, false otherwise
 */
function tags_check_acl($id_user, $id_group, $access, $tags = array(), $flag_id_tag = false) {
	global $config;
	
	if ($id_user === false) {
		$id_user = $config['id_user'];
	}
	
	// Get parents to check in propagate ACL cases
	if (!is_array($id_group) && $id_group != 0) {
		$id_group = array($id_group);
		$group = db_get_row_filter('tgrupo',
			array('id_grupo' => $id_group));
		$parents = groups_get_parents($group['parent'], true);
		
		foreach ($parents as $parent) {
			$id_group[] = $parent['id_grupo'];
		}
	}
	
	$acls = tags_get_acl_tags($id_user, $id_group, $access, 'data');
	
	// If there are wrong parameters or fail ACL check, return false
	if ($acls === ERR_WRONG_PARAMETERS || $acls === ERR_ACL) {
		return false;
	}
	
	// If there are not tags restrictions or tags passed, return true
	if (empty($acls) || empty($tags)) {
		return true;
	}
	
	# Fix: If user profile has more than one group, due to ACL propagation then id_group can be an array
	if (is_array($id_group)) {
		
		foreach ($id_group  as $group) {
			if ($group > 0) {
				if (array_key_exists(0, $acls)) {
					//There is a All group
					
					foreach ($tags as $tag) {
						if (in_array($tag, $acls[0])) {
							return true;
						}
						else {
							return false;
						}
					}
				}
				else if (isset($acls[$group])) {
					foreach ($tags as $tag) {
						if (!$flag_id_tag)
							$tag = tags_get_id($tag);
						
						if (in_array($tag, $acls[$group])) {
							return true;
						}
					}
				}
				else {
					return false;
				}
			}
			else {
				
				foreach ($acls as $acl_tags) {
					foreach ($tags as $tag) {
						if (!$flag_id_tag)
							$tag = tags_get_id($tag);
						
						if (in_array($tag, $acl_tags)) {
							return true;
						}
					}
				}
			}
		}
	}
	else {
		if ($id_group > 0) {
			if (isset($acls[$id_group])) {
				foreach ($tags as $tag) {
					if (!$flag_id_tag)
						$tag = tags_get_id($tag);
					
					if (in_array($tag, $acls[$id_group])) {
						return true;
					}
				}
			}
			else {
				return false;
			}
		}
		else {
			foreach ($acls as $acl_tags) {
				foreach ($tags as $tag) {
					if (!$flag_id_tag)
						$tag = tags_get_id($tag);
					
					if (in_array($tag, $acl_tags)) {
						return true;
					}
				}
			}
		}
	}
	
	return false;
}

function tags_check_acl_event($id_user, $id_group, $access, $tags = array(),$p = false) {
	global $config;

	if($id_user === false) {
		$id_user = $config['id_user'];
	}
	
	$acls = tags_get_acl_tags($id_user, $id_group, $access, 'data');

	// If there are wrong parameters or fail ACL check, return false
	if($acls === ERR_WRONG_PARAMETERS || $acls === ERR_ACL) {
		return false;
	}

	// If there are not tags restrictions or tags passed, return true
	if(empty($acls) || empty($tags)) {
		return true;
	}

	# Fix: If user profile has more than one group, due to ACL propagation then id_group can be an array
	if (is_array($id_group)) {

		foreach ($id_group  as $group) {
			if($group > 0) {
				if(isset($acls[$group])) {
					foreach($tags as $tag) {
						$tag = tags_get_id($tag);
						if(in_array($tag, $acls[$group])) {
							return true;
						}
					}
				}
				else {
					//return false;
					$return = false;
                }
			} else {
				foreach($acls as $acl_tags) {
						foreach($tags as $tag) {
								$tag = tags_get_id($tag);
								if(in_array($tag, $acl_tags)) {
										return true;
								}
						}
				}
			}

		}

	} else {
		if($id_group > 0) {
			if(isset($acls[$id_group])) {
				foreach($tags as $tag) {
					$tag = tags_get_id($tag);
					
					if(in_array($tag, $acls[$id_group])) {
						return true;
					}
				}
			}
			else {
				//return false;
				$return = false;
			}
		}
		else {
			foreach($acls as $acl_tags) {
				foreach($tags as $tag) {
					$tag = tags_get_id($tag);
					if(in_array($tag, $acl_tags)) {
						return true;
					}
				}
			}
		}
	}	
	//return false;
	$return = false;
	
	if ($return == false) {
		$parent = db_get_value('parent','tgrupo','id_grupo',$id_group);

		if ($parent !== 0) {
			$propagate = db_get_value('propagate','tgrupo','id_grupo',$parent);
			if ($propagate == 1) {
				$acl_parent = tags_check_acl_event($id_user, $parent, $access, $tags,$p);
				return $acl_parent;
			}
		}
	}
}

/* This function checks event ACLs */
function tags_checks_event_acl($id_user, $id_group, $access, $tags = array(), $childrens_ids = array()) {
	global $config;

	if($id_user === false) {
		$id_user = $config['id_user'];
	}
	
	$tags_user = tags_get_acl_tags($id_user, $id_group, $access, 'data', '', '', true, $childrens_ids, true);
	// If there are wrong parameters or fail ACL check, return false
	if ($tags_user === ERR_WRONG_PARAMETERS || $tags_user === ERR_ACL) {
		return false;
	}

	//check user without tags
	$sql = "SELECT id_usuario FROM tusuario_perfil
		WHERE id_usuario = '".$config["id_user"]."' AND tags = ''
		AND id_perfil IN (SELECT id_perfil FROM tperfil WHERE ".get_acl_column($access)."=1)";
	$user_has_perm_without_tags = db_get_all_rows_sql ($sql);
	
	if ($user_has_perm_without_tags) {
		return true;
	}

	$query = sprintf("SELECT tags, id_grupo 
				FROM tusuario_perfil, tperfil
				WHERE tperfil.id_perfil = tusuario_perfil.id_perfil AND
					tusuario_perfil.id_usuario = '%s' AND 
					tperfil.%s = 1
				ORDER BY id_grupo", $id_user, get_acl_column($access));
	$user_tags = db_get_all_rows_sql($query);

	if ($user_tags === false) {
		$user_tags = array();
	}
	
	foreach ($user_tags as $user_tag) {
		$tags_user = $user_tag['tags'];
		$id_group_user = $user_tag['id_grupo'];
		$childrens = groups_get_childrens($id_group_user, null, true);

		if (empty($childrens)) {
			$group_ids = $id_group_user;
		} else {
			$childrens_ids[] = $id_group_user;
			foreach ($childrens as $child) {
				$childrens_ids[] = (int)$child['id_grupo'];
			}
			$group_ids = implode(',', $childrens_ids);
		}
		$sql = "SELECT id_usuario FROM tusuario_perfil
					WHERE id_usuario = '".$config["id_user"]."' AND tags = '$tags_user'
					AND id_perfil IN (SELECT id_perfil FROM tperfil WHERE ".get_acl_column($access)."=1)
					AND id_grupo IN ($group_ids)";
		$has_perm = db_get_value_sql ($sql);
		
		if ($has_perm) {
			return true;
		}
	}
	
	return false;
}

/**
 * Get total agents filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search total agents
 * 
 * @return mixed Returns count of agents with this tag or false if they aren't.
 */
function tags_get_total_agents ($id_tag, $groups_and_tags = array()) {
	
	// Avoid mysql error
	if (empty($id_tag))
		return;
	
	$groups_clause = "";
	if (!empty($groups_and_tags)) {
		
		$groups_id = array();
		foreach ($groups_and_tags as $group_id => $tags) {
			if (!empty($tags)) {
				$tags_arr = explode(',', $tags);
				foreach ($tags_arr as $tag) {
					if ($tag == $id_tag)
						$groups_id[] = $group_id;
				}
			}
		}
		if (array_search(0, $groups_id) === false) {
			$groups_id_str = implode(",", $groups_id);
			$groups_clause = " AND ta.id_grupo IN ($groups_id_str)"; 
		}
	}
	
	$total_agents = "SELECT COUNT(DISTINCT ta.id_agente) 
					FROM tagente AS ta
					INNER JOIN tagente_modulo AS tam
						ON ta.id_agente = tam.id_agente
							AND tam.disabled = 0
					INNER JOIN ttag_module AS ttm
						ON ttm.id_tag = $id_tag
							AND tam.id_agente_modulo = ttm.id_agente_modulo
					WHERE ta.disabled = 0
						$groups_clause";

	return db_get_sql($total_agents);
}

 /**
 * Get normal agents by using the status code in modules by filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search module with normal state
 * 
 * @return mixed Returns count of agents in normal status or false if they aren't.
 */
function tags_get_normal_agents ($id_tag, $groups_and_tags = array()) {

	// Avoid mysql error
	if (empty($id_tag))
		return;
	
	$groups_clause = "";
	if (!empty($groups_and_tags)) {
		
		$groups_id = array();
		foreach ($groups_and_tags as $group_id => $tags) {
			if (!empty($tags)) {
				$tags_arr = explode(',', $tags);
				foreach ($tags_arr as $tag) {
					if ($tag == $id_tag)
						$groups_id[] = $group_id;
				}
			}
		}
		if (array_search(0, $groups_id) === false) {
			$groups_id_str = implode(",", $groups_id);
			$groups_clause = " AND ta.id_grupo IN ($groups_id_str)"; 
		}
	}
	
	$ok_agents = "SELECT COUNT(DISTINCT ta.id_agente) 
					FROM tagente AS ta
					INNER JOIN tagente_modulo AS tam
						ON ta.id_agente = tam.id_agente
							AND tam.disabled = 0
					INNER JOIN ttag_module AS ttm
						ON ttm.id_tag = $id_tag
							AND tam.id_agente_modulo = ttm.id_agente_modulo
					WHERE ta.disabled = 0
						AND ta.critical_count = 0
						AND ta.warning_count = 0
						AND ta.unknown_count = 0
						AND ta.normal_count > 0
						$groups_clause";

	return db_get_sql($ok_agents);
}

 /**
 * Get warning agents by using the status code in modules by filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search module with warning state
 * 
 * @return mixed Returns count of agents in warning status or false if they aren't.
 */
function tags_get_warning_agents ($id_tag, $groups_and_tags = array()) {
	
	// Avoid mysql error
	if (empty($id_tag))
		return;
	
	$groups_clause = "";
	if (!empty($groups_and_tags)) {
		
		$groups_id = array();
		foreach ($groups_and_tags as $group_id => $tags) {
			if (!empty($tags)) {
				$tags_arr = explode(',', $tags);
				foreach ($tags_arr as $tag) {
					if ($tag == $id_tag)
						$groups_id[] = $group_id;
				}
			}
		}
		if (array_search(0, $groups_id) === false) {
			$groups_id_str = implode(",", $groups_id);
			$groups_clause = " AND ta.id_grupo IN ($groups_id_str)"; 
		}
	}
	
	$warning_agents = "SELECT COUNT(DISTINCT ta.id_agente) 
						FROM tagente AS ta
						INNER JOIN tagente_modulo AS tam
							ON ta.id_agente = tam.id_agente
								AND tam.disabled = 0
						INNER JOIN ttag_module AS ttm
							ON ttm.id_tag = $id_tag
								AND tam.id_agente_modulo = ttm.id_agente_modulo
						WHERE ta.disabled = 0
							AND ta.total_count > 0
							AND ta.critical_count = 0
							AND ta.warning_count > 0
							$groups_clause";

	return db_get_sql($warning_agents);
}

/**
 * Get unknown agents filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search unknown agents
 * 
 * @return mixed Returns count of unknown agents with this tag or false if they aren't.
 */
function tags_get_critical_agents ($id_tag, $groups_and_tags = array()) {
	
	// Avoid mysql error
	if (empty($id_tag))
		return;
	
	$groups_clause = "";
	if (!empty($groups_and_tags)) {
		
		$groups_id = array();
		foreach ($groups_and_tags as $group_id => $tags) {
			if (!empty($tags)) {
				$tags_arr = explode(',', $tags);
				foreach ($tags_arr as $tag) {
					if ($tag == $id_tag)
						$groups_id[] = $group_id;
				}
			}
		}
		if (array_search(0, $groups_id) === false) {
			$groups_id_str = implode(",", $groups_id);
			$groups_clause = " AND ta.id_grupo IN ($groups_id_str)"; 
		}
	}
	
	$critical_agents = "SELECT COUNT(DISTINCT ta.id_agente) 
						FROM tagente AS ta
						INNER JOIN tagente_modulo AS tam
							ON ta.id_agente = tam.id_agente
								AND tam.disabled = 0
						INNER JOIN ttag_module AS ttm
							ON ttm.id_tag = $id_tag
								AND tam.id_agente_modulo = ttm.id_agente_modulo
						WHERE ta.disabled = 0
							AND ta.critical_count > 0
							$groups_clause";

	return db_get_sql($critical_agents);
}

/**
 * Get unknown agents filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search unknown agents
 * 
 * @return mixed Returns count of unknown agents with this tag or false if they aren't.
 */
function tags_get_unknown_agents ($id_tag, $groups_and_tags = array()) {
	
	// Avoid mysql error
	if (empty($id_tag))
		return;
	
	$groups_clause = "";
	if (!empty($groups_and_tags)) {
		
		$groups_id = array();
		foreach ($groups_and_tags as $group_id => $tags) {
			if (!empty($tags)) {
				$tags_arr = explode(',', $tags);
				foreach ($tags_arr as $tag) {
					if ($tag == $id_tag)
						$groups_id[] = $group_id;
				}
			}
		}
		if (array_search(0, $groups_id) === false) {
			$groups_id_str = implode(",", $groups_id);
			$groups_clause = " AND ta.id_grupo IN ($groups_id_str)"; 
		}
	}
	
	$unknown_agents = "SELECT COUNT(DISTINCT ta.id_agente) 
						FROM tagente AS ta
						INNER JOIN tagente_modulo AS tam
							ON ta.id_agente = tam.id_agente
								AND tam.disabled = 0
						INNER JOIN ttag_module AS ttm
							ON ttm.id_tag = $id_tag
								AND tam.id_agente_modulo = ttm.id_agente_modulo
						WHERE ta.disabled = 0
							AND ta.critical_count = 0
							AND ta.warning_count = 0
							AND ta.unknown_count > 0
							$groups_clause";

	return db_get_sql($unknown_agents);
}

/**
 * Get not init agents filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search not init agents
 * 
 * @return mixed Returns count of not init agents with this tag or false if they aren't.
 */
function tags_get_not_init_agents ($id_tag, $groups_and_tags = array()) {
	
	// Avoid mysql error
	if (empty($id_tag))
		return;
	
	$groups_clause = "";
	if (!empty($groups_and_tags)) {
		
		$groups_id = array();
		foreach ($groups_and_tags as $group_id => $tags) {
			if (!empty($tags)) {
				$tags_arr = explode(',', $tags);
				foreach ($tags_arr as $tag) {
					if ($tag == $id_tag)
						$groups_id[] = $group_id;
				}
			}
		}
		if (array_search(0, $groups_id) === false) {
			$groups_id_str = implode(",", $groups_id);
			$groups_clause = " AND ta.id_grupo IN ($groups_id_str)"; 
		}
	}
	
	$not_init_agents = "SELECT COUNT(DISTINCT ta.id_agente) 
						FROM tagente AS ta
						INNER JOIN tagente_modulo AS tam
							ON ta.id_agente = tam.id_agente
								AND tam.disabled = 0
						INNER JOIN ttag_module AS ttm
							ON ttm.id_tag = $id_tag
								AND tam.id_agente_modulo = ttm.id_agente_modulo
						WHERE ta.disabled = 0
							AND (ta.total_count = 0
								OR ta.total_count = ta.notinit_count)
							$groups_clause";

	return db_get_sql($not_init_agents);
}

function tags_monitors_count ($type, $id_tag, $groups_and_tags = array()) {
	
	// Avoid mysql error
	if (empty($id_tag))
		return;
	
	$groups_clause = "";
	if (!empty($groups_and_tags)) {
		
		$groups_id = array();
		foreach ($groups_and_tags as $group_id => $tags) {
			if (!empty($tags)) {
				$tags_arr = explode(',', $tags);
				foreach ($tags_arr as $tag) {
					if ($tag == $id_tag)
						$groups_id[] = $group_id;
				}
			}
		}
		if (array_search(0, $groups_id) === false) {
			$groups_id_str = implode(",", $groups_id);
			$groups_clause = " AND ta.id_grupo IN ($groups_id_str)"; 
		}
	}

	switch ($type) {
		case AGENT_MODULE_STATUS_CRITICAL_ALERT:
		case AGENT_MODULE_STATUS_CRITICAL_BAD:
			$status = AGENT_MODULE_STATUS_CRITICAL_ALERT.",".AGENT_MODULE_STATUS_CRITICAL_BAD;
			break;
		case AGENT_MODULE_STATUS_WARNING_ALERT:
		case AGENT_MODULE_STATUS_WARNING:
			$status = AGENT_MODULE_STATUS_WARNING_ALERT.",".AGENT_MODULE_STATUS_WARNING;
			break;
		case AGENT_MODULE_STATUS_UNKNOWN:
			$status = AGENT_MODULE_STATUS_UNKNOWN;
			break;
		case AGENT_MODULE_STATUS_NO_DATA:
		case AGENT_MODULE_STATUS_NOT_INIT:
			$status = AGENT_MODULE_STATUS_NO_DATA.",".AGENT_MODULE_STATUS_NOT_INIT;
			break;
		case AGENT_MODULE_STATUS_NORMAL_ALERT:
		case AGENT_MODULE_STATUS_NORMAL:
			$status = AGENT_MODULE_STATUS_NORMAL_ALERT.",".AGENT_MODULE_STATUS_NORMAL;
			break;
		default:
			// The type doesn't exist
			return;
	}
	
	$sql = "SELECT COUNT(DISTINCT tam.id_agente_modulo)
			FROM tagente_modulo AS tam
			INNER JOIN tagente_estado AS tae
				ON tam.id_agente_modulo = tae.id_agente_modulo
					AND tae.estado IN ($status)
			INNER JOIN ttag_module AS ttm
				ON ttm.id_tag = $id_tag
					AND tam.id_agente_modulo = ttm.id_agente_modulo
			INNER JOIN tagente AS ta
				ON tam.id_agente = ta.id_agente
					AND ta.disabled = 0
					$groups_clause
			WHERE tam.disabled = 0";
			
	$count = db_get_sql ($sql);

	return $count;	
}

function tags_monitors_normal ($id_tag, $groups_and_tags = array()) {
	return tags_monitors_count(AGENT_MODULE_STATUS_NORMAL, $id_tag, $groups_and_tags);
}

function tags_monitors_critical ($id_tag, $groups_and_tags = array()) {
	return tags_monitors_count(AGENT_MODULE_STATUS_CRITICAL_BAD, $id_tag, $groups_and_tags);
}

function tags_monitors_warning ($id_tag, $groups_and_tags = array()) {
	return tags_monitors_count(AGENT_MODULE_STATUS_WARNING, $id_tag, $groups_and_tags);
}

function tags_monitors_not_init ($id_tag, $groups_and_tags = array()) {
	return tags_monitors_count(AGENT_MODULE_STATUS_NOT_INIT, $id_tag, $groups_and_tags);
}

function tags_monitors_unknown ($id_tag, $groups_and_tags = array()) {
	return tags_monitors_count(AGENT_MODULE_STATUS_UNKNOWN, $id_tag, $groups_and_tags);
}

function tags_monitors_fired_alerts ($id_tag, $groups_and_tags = array()) {
	
	// Avoid mysql error
	if (empty($id_tag))
		return;
	
	$groups_clause = "";
	if (!empty($groups_and_tags)) {
		
		$groups_id = array();
		foreach ($groups_and_tags as $group_id => $tags) {
			if (!empty($tags)) {
				$tags_arr = explode(',', $tags);
				foreach ($tags_arr as $tag) {
					if ($tag == $id_tag)
						$groups_id[] = $group_id;
				}
			}
		}
		if (array_search(0, $groups_id) === false) {
			$groups_id_str = implode(",", $groups_id);
			$groups_clause = " AND tagente.id_grupo IN ($groups_id_str)"; 
		}
	}
							
	$sql = "SELECT COUNT(talert_template_modules.id)
		FROM talert_template_modules, tagente_modulo, tagente_estado, tagente
		WHERE tagente_modulo.id_agente = tagente.id_agente
		AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
		AND tagente_modulo.disabled = 0 AND tagente.disabled = 0 
		AND talert_template_modules.disabled = 0 
		AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo 
		AND times_fired > 0 
		AND tagente_modulo.id_agente_modulo IN (SELECT id_agente_modulo FROM ttag_module WHERE id_tag = $id_tag)
		$groups_clause";

	$count = db_get_sql ($sql);
			
	return $count;	
}

/* Return array with groups and their tags */
function tags_get_user_module_and_tags ($id_user = false, $access = 'AR', $strict_user = false) {
	global $config;
	
	if ($id_user == false) {
		$id_user = $config['id_user'];
	}
	
	$acl_column = get_acl_column($access);

	$sql = sprintf("SELECT tags, id_grupo 
					FROM tusuario_perfil, tperfil
					WHERE tperfil.id_perfil = tusuario_perfil.id_perfil AND
						tusuario_perfil.id_usuario = '%s' AND 
						tperfil.%s = 1
					ORDER BY id_grupo", $id_user, $acl_column);
	$tags_and_groups = db_get_all_rows_sql($sql);

	if ($tags_and_groups === false)
		$tags_and_groups = array();
	
	$acltags = array();
	
	// Change the 'All' group with all groups
	$all_group_ids = array();
	$all_groups = groups_get_all();
	if (!empty($all_groups))
		$all_group_ids = array_keys($all_groups);

	$tags_and_groups_aux = array();
	foreach ($tags_and_groups as $data) {
		// All group
		if ($data['id_grupo'] == 0) {
			// All group with empty tags. All groups without tags permission!
			if (empty($data['tags'])) {
				foreach ($all_group_ids as $group_id) {
					$acltags[$group_id] = '';
				}
				
				return $acltags; // End of the function
			}
			// Create a new element for every group with the tags
			else {
				foreach ($all_group_ids as $group_id) {
					$tags_and_groups_aux[] = array(
							'id_grupo' => $group_id,
							'tags' => $data['tags']
						);
				}
			}
		}
		// Specific group
		else {
			$tags_and_groups_aux[] = $data;
		}
	}
	$tags_and_groups = $tags_and_groups_aux;
	unset($tags_and_groups_aux);
	
	function __add_acltags (&$acltags, $group_id, $tags_str) {
		if (!isset($acltags[$group_id])) {
			// Add the new element
			$acltags[$group_id] = $tags_str;
		}
		else {
			// Add the tags. The empty tags have priority cause mean more permissions
			$existing_tags = $acltags[$group_id];
			
			if (!empty($existing_tags)) {
				$existing_tags_array = explode(",", $existing_tags);
				
				// Store the empty tags
				if (empty($tags_str)) {
					$acltags[$group_id] = '';
				}
				// Merge the old and new tabs
				else {
					$new_tags_array = explode(",", $tags_str);
					
					$final_tags_array = array_merge($existing_tags_array, $new_tags_array);
					$final_tags_str = implode(",", $final_tags_array);
					
					if (! empty($final_tags_str))
						$acltags[$group_id] = $final_tags_str;
				}
			}
		}
		
		// Propagation
		$propagate = (bool) db_get_value('propagate', 'tgrupo', 'id_grupo', $group_tag['id_grupo']);
		if ($propagate) {
			$sql = "SELECT id_grupo FROM tgrupo WHERE parent = $group_id";
			$children = db_get_all_rows_sql($sql);
			
			if ($children === false)
				$children = array();
			
			foreach ($children as $children_group) {
				// Add the tags to the children (recursive)
				__add_acltags($acltags, $children_group['id_grupo'], $tags_str);
			}
		}
	}

	foreach ($tags_and_groups as $group_tag) {
		__add_acltags($acltags, $group_tag['id_grupo'], $group_tag['tags']);
	}
	
	return $acltags;
}

function tags_get_monitors_alerts ($id_tag, $groups_and_tags = array()) {
	
	// Avoid mysql error
	if (empty($id_tag))
		return;
	
	$groups_clause = "";
	if (!empty($groups_and_tags)) {
		
		$groups_id = array();
		foreach ($groups_and_tags as $group_id => $tags) {
			if (!empty($tags)) {
				$tags_arr = explode(',', $tags);
				foreach ($tags_arr as $tag) {
					if ($tag == $id_tag)
						$groups_id[] = $group_id;
				}
			}
		}
		if (array_search(0, $groups_id) === false) {
			$groups_id_str = implode(",", $groups_id);
			$groups_clause = " AND tagente.id_grupo IN ($groups_id_str)"; 
		}
	}
								
	$sql = "SELECT COUNT(talert_template_modules.id)
		FROM talert_template_modules, tagente_modulo, tagente_estado, tagente
		WHERE tagente_modulo.id_agente = tagente.id_agente
		AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
		AND tagente_modulo.disabled = 0 AND tagente.disabled = 0
		AND	talert_template_modules.disabled = 0 
		AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo
		AND tagente_modulo.id_agente_modulo IN (SELECT id_agente_modulo FROM ttag_module WHERE id_tag = $id_tag)
		$groups_clause";

	$count = db_get_sql ($sql);
			
	return $count;	
}

/**
 * Get agents filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search total agents
 * 
 * @return mixed Returns count of agents with this tag or false if they aren't.
 */
function tags_get_all_user_agents ($id_tag = false, $id_user = false, $groups_and_tags = array(), $filter = false, $fields = false, $meta = true, $strict_user = true, $return_all_fields = false) {
	
	global $config;

	if (empty($id_tag)) {
		$tag_filter = '';
	} else {
		$tag_filter = " AND tagente_modulo.id_agente_modulo IN (SELECT id_agente_modulo FROM ttag_module WHERE id_tag = $id_tag) ";
	}
	if (empty($id_user)) {
		$id_user = $config['id_user'];
	}
	
	if (!is_array ($fields)) {
		$fields = array ();
		$fields[0] = "id_agente";
		$fields[1] = "nombre";
	}
	
	$select_fields = implode(',',$fields);

	$groups_clause = "";
	if ($strict_user) {
		if (!empty($groups_and_tags)) {
			$groups_clause = " AND ".tags_get_acl_tags_module_condition($groups_and_tags, "tagente_modulo"); 		 
		}
	} else {
		$groups_clause = " AND tagente.id_grupo IN (".implode(',',$groups_and_tags).")";
	}
	
	if (!empty($filter['id_group'])) {
		if (is_array($filter['id_group']))
			$groups_str = implode(",", $filter['id_group']);
		else
			$groups_str = $filter['id_group'];
		$groups_clause .= " AND tagente.id_grupo IN ($groups_str)";
	}

	$status_sql = '';
	if (isset($filter['status'])) {
		switch ($filter['status']) {
			case AGENT_STATUS_NORMAL:
				$status_sql =
					" AND (normal_count = total_count)";
				break;
			case AGENT_STATUS_WARNING:
				$status_sql =
					"AND (critical_count = 0 AND warning_count > 0)";
				break;
			case AGENT_STATUS_CRITICAL:
				$status_sql =
					"AND (critical_count > 0)";
				break;
			case AGENT_STATUS_UNKNOWN:
				$status_sql =
					"AND (critical_count = 0 AND warning_count = 0
						AND unknown_count > 0)";
				break;
			case AGENT_STATUS_NOT_NORMAL:
				$status_sql = " AND (normal_count <> total_count)";
				break;
			case AGENT_STATUS_NOT_INIT:
				$status_sql = "AND (notinit_count = total_count)";
				break;
		}
		
	}
	$disabled_sql = '';
	if (!empty($filter['disabled'])) {
		$disabled_sql = " AND disabled = ".$filter['disabled'];
	}
	
	$order_by_condition = '';
	if (!empty($filter['order'])) {
		$order_by_condition = " ORDER BY ".$filter['order'];
	}
	else {
		$order_by_condition = " ORDER BY tagente.nombre ASC";
	}
	$limit_sql = '';
	if (isset($filter['offset'])) {
		$offset = $filter['offset'];
	}
	if (isset($filter['limit'])) {
		$limit = $filter['limit'];
	}
	
	if (isset($offset) && isset($limit)) {
		$limit_sql = " LIMIT $offset, $limit "; 
	}
	
	if (!empty($filter['group_by'])) {
		$group_by = " GROUP BY ".$filter['group_by'];
	}
	else {
		$group_by = " GROUP BY tagente.nombre";
	}
	
	$id_agent_search = '';
	if (!empty($filter['id_agent'])) {
		$id_agent_search = " AND tagente.id_agente = ".$filter['id_agent'];
	}
	
	$search_sql = "";
	$void_agents = "";
	if ($filter) {
		if (($filter['search']) != "") {
			$string = io_safe_input ($filter['search']);
			$search_sql = ' AND (tagente.nombre COLLATE utf8_general_ci LIKE "%'.$string.'%")';
		}
		
		if (isset($filter['show_void_agents'])) {
			if (!$filter['show_void_agents']) {
				$void_agents = " AND tagente_modulo.delete_pending = 0";
			}
		}
	}
	
	$user_agents_sql = "SELECT ".$select_fields ."
		FROM tagente, tagente_modulo
		WHERE tagente.id_agente = tagente_modulo.id_agente
		". $tag_filter .
		$groups_clause . $search_sql . $void_agents .
		$status_sql .
		$disabled_sql .
		$group_by .
		$order_by_condition .
		$limit_sql;
	
	$user_agents = db_get_all_rows_sql($user_agents_sql);	
	
	if ($user_agents == false) {
		$user_agents = array();
	}
	if ($return_all_fields) {
		return $user_agents;
	}
	if (!$meta){
		$user_agents_aux = array();
		
		foreach ($user_agents as $ua) {
			$user_agents_aux[$ua['id_agente']] = $ua['nombre'];
		} 
		return $user_agents_aux;
	}
	return $user_agents;
}

function tags_get_agent_modules ($id_agent, $groups_and_tags = array(), $fields = false, $filter = false, $return_all_fields = false, $get_filter_status = -1) {
	
	global $config;
	
	// Avoid mysql error
	if (empty($id_agent))
		return false;
	
	if (!is_array ($fields)) {
		$fields = array ();
		$fields[0] = "tagente_modulo.id_agente_modulo";
		$fields[1] = "tagente_modulo.nombre";
	}
	$select_fields = implode(',',$fields);
	
	if ($filter) {
		$filter_sql = '';
		if (isset($filter['disabled'])) {
			$filter_sql .= " AND tagente_modulo.disabled = ".$filter['disabled'];
		}
		if (isset($filter['nombre'])) {
			$filter_sql .= ' AND tagente_modulo.nombre LIKE "' .$filter['nombre'].'"';
		}
		
	}
	
	$tag_filter = "";
	if (!empty($groups_and_tags)) {
		$agent_group = db_get_value('id_grupo', 'tagente', 'id_agente', $id_agent);
		if (isset($groups_and_tags[$agent_group]) && ($groups_and_tags[$agent_group] != '')) {
			//~ $tag_filter = " AND ttag_module.id_tag IN (".$groups_and_tags[$agent_group].")";
			$tag_filter = " AND tagente_modulo.id_agente_modulo IN (SELECT id_agente_modulo FROM ttag_module WHERE id_tag IN (".$groups_and_tags[$agent_group]."))";
		}
	}
	
	if ($get_filter_status != -1) {
		$agent_modules_sql = "SELECT ".$select_fields ."
			FROM tagente_modulo, tagente_estado
			WHERE tagente_modulo.id_agente=". $id_agent .
			" AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
			 AND tagente_estado.estado = ".$get_filter_status .
			$tag_filter .
			$filter_sql ."
			ORDER BY nombre";
	}
	else {
		
		$agent_modules_sql = "SELECT ".$select_fields ."
			FROM tagente_modulo 
			WHERE id_agente=". $id_agent .
			$tag_filter .
			$filter_sql ."
			ORDER BY nombre";
	}
	
	$agent_modules = db_get_all_rows_sql($agent_modules_sql);
	
	if ($agent_modules == false) {
		$agent_modules = array();
	}
	
	if ($return_all_fields) {
		$result = array();
		foreach ($agent_modules as $am) {
			$am['status'] = modules_get_agentmodule_status($am['id_agente_modulo']);
			$am['isinit'] = modules_get_agentmodule_is_init($am['id_agente_modulo']);
			if ($am['isinit']) {
				
			}
			$result[$am['id_agente_modulo']] = $am;
		}
		return $result;
	}
	
	$result = array();
	foreach ($agent_modules as $am) {
		$result[$am['id_agente_modulo']] = $am['nombre'];
	}
	
	return $result;
}

function tags_get_module_policy_tags($id_tag, $id_module) {
	if (empty($id_tag))
		return false;
	
	$id_module_policy = db_get_value_filter('id_policy_module',
		'ttag_module',
		array('id_tag' => $id_tag, 'id_agente_modulo' => $id_module));
	
	return $id_module_policy;
}
?>
