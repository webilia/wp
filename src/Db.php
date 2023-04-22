<?php
namespace Webilia\WP;

use wpdb;

/**
 * Class Db
 * @package Utils
 */
class Db
{
    /**
     * Constructor method
     */
    public function __construct()
    {
    }

    /**
     * Runs any query
     * @param string $query
     * @param string $type
     * @return mixed
     */
	public function q(string $query, string $type = '')
	{
		// Apply DB prefix
		$query = $this->prefix($query);

		// Converts query type to lowercase
		$type = strtolower($type);

		// Calls select function if query type is select
		if($type == 'select') return $this->select($query);

		// Get WordPress DB object
		$database = $this->get_DBO();

        // If query type is insert, return the insert id
		if($type == 'insert')
		{
			$database->query($query);
			return $database->insert_id;
		}

        // Run the query and return the result
		return $database->query($query);
	}

    /**
     * Returns records count of a query
     * @param string $query
     * @param string $table
     * @return int
     */
	public function num(string $query, string $table = ''): int
	{
        // If table is filled, generate the query
		if(trim($table) != '')
		{
			$query = "SELECT COUNT(*) FROM `#__$table`";
		}

		// Apply DB prefix
		$query = $this->prefix($query);

		// Get WordPress Db object
		$database = $this->get_DBO();
		return (int) $database->get_var($query);
	}

    /**
     * Selects records from Database
     * @param string $query
     * @param string $result
     * @return mixed
     */
	public function select(string $query, string $result = 'loadObjectList')
	{
		// Apply DB prefix
		$query = $this->prefix($query);

		// Get WordPress DB object
		$database = $this->get_DBO();

		if($result == 'loadObjectList') return $database->get_results($query, OBJECT_K);
		else if($result == 'loadObject') return $database->get_row($query, OBJECT);
		else if($result == 'loadAssocList') return $database->get_results($query, ARRAY_A);
		else if($result == 'loadAssoc') return $database->get_row($query, ARRAY_A);
		else if($result == 'loadResult') return $database->get_var($query);
        else if($result == 'loadColumn') return $database->get_col($query);
		else return $database->get_results($query, OBJECT_K);
	}

    /**
     * Get a record from Database
     * @param string|array<string> $selects
     * @param string $table
     * @param string $field
     * @param string $value
     * @param boolean $return_object
     * @param string $condition
     * @return mixed
     */
	public function get($selects, string $table, string $field, string $value, bool $return_object = true, string $condition = '')
	{
		$fields = '';
		if(is_array($selects))
		{
			foreach($selects as $select) $fields .= '`'.$select.'`,';
			$fields = trim($fields, ' ,');
		}
		else
		{
			$fields = $selects;
		}

        // Generate the condition
		if(trim($condition) == '') $condition = "`$field`='$value'";

        // Generate the query
		$query = "SELECT $fields FROM `#__$table` WHERE $condition";

		// Apply DB prefix
		$query = $this->prefix($query);

		// Get WordPress DB object
		$database = $this->get_DBO();

		if($selects != '*' and !is_array($selects)) return $database->get_var($query);
		else if($return_object)
		{
			return $database->get_row($query);
		}
		else
		{
			return $database->get_row($query, ARRAY_A);
		}
	}

    /**
     * Apply WordPress table prefix on queries
     * @param string $query
     * @return string
     */
	public function prefix(string $query): string
    {
        // Get WordPress DB object
		$wpdb = $this->get_DBO();

        $query = str_replace('#__blogs', $wpdb->base_prefix.'blogs', $query);
		return str_replace('#__', $wpdb->prefix, $query);
	}

    /**
     * Returns WordPres DB Object
     * @global wpdb $wpdb
     * @return wpdb
     */
	public function get_DBO(): wpdb
	{
		global $wpdb;
		return $wpdb;
	}

    /**
     * @return string
     */
    public function version(): string
    {
        $query = "SELECT VERSION();";
        return $this->select($query, 'loadResult');
	}
}
