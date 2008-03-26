<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Swat/SwatObject.php';
require_once 'Swat/SwatDate.php';
require_once 'Swat/exceptions/SwatClassNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBTransaction.php';
require_once 'SwatDB/exceptions/SwatDBException.php';

/**
 * All public properties correspond to database fields
 *
 * @package   SwatDB
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SwatDBDataObject extends SwatObject implements Serializable
{
	// {{{ private properties

	/**
	 * @var array
	 */
	private $property_hashes = array();

	/**
	 * @var array
	 */
	private $sub_data_objects = array();
	
	/**
	 * @var array
	 */
	private $internal_properties = array();
	
	/**
	 * @var array
	 */
	private $internal_property_autosave = array();

	/**
	 * @var array
	 */
	private $internal_property_classes = array();

	/**
	 * @var array
	 */
	private $date_properties = array();
	
	// }}}
	// {{{ protected properties

	/**
	 * @var MDB2
	 */
	protected $db = null;

	protected $table = null;
	protected $id_field = null;
	
	// }}}
	// {{{ public function __construct()

	/**
	 * @param mixed $data
	 */
	public function __construct($data = null)
	{
		$this->init();

		if ($data !== null)
			$this->initFromRow($data);

		$this->generatePropertyHashes();
	}

	// }}}
	// {{{ public function setTable()

	/**
	 * @param database $table
	 */
	public function setTable($table)
	{
		$this->table = $table;
	}

	// }}}
	// {{{ public function isModified()

	/**
	 * Returns true if this object has been modified since it was loaded
	 *
	 * @return boolean true if this object was modified and false if this
	 *                  object was not modified.
	 */
	public function isModified()
	{
		$property_array = $this->getProperties();

		foreach ($property_array as $name => $value) {
			$hashed_value = md5(serialize($value));
			if (strcmp($hashed_value, $this->property_hashes[$name]) != 0)
				return true;
		}

		foreach ($this->sub_data_objects as $name => $object)
			if (is_object($object) && $object->isModified())
				return true;
		
		return false;
	}

	// }}}
	// {{{ public function getModifiedProperties()

	/**
	 * Gets a list of all the modified properties of this object
	 *
	 * @return array an array of modified properties and their values in the
	 *                form of: name => value
	 */
	public function getModifiedProperties()
	{
		$property_array = $this->getProperties();
		$modified_properties = array();

		foreach ($property_array as $name => $value) {
			$hashed_value = md5(serialize($value));
			if (strcmp($hashed_value, $this->property_hashes[$name]) != 0)
				$modified_properties[$name] = $value;
		}

		return $modified_properties;
	}

	// }}}
	// {{{ public function __toString()

	/**
	 * Gets a string representation of this data-object
	 *
	 * @return string this data-object represented as a string.
	 *
	 * @see SwatObject::__toString()
	 */
	public function __toString()
	{
		// prevent printing of MDB2 object for dataobjects
		$db = $this->db;
		$this->db = null;

		$modified_properties = $this->getModifiedProperties();
		$properties = $this->getPublicProperties();

		foreach ($this->getSerializableSubDataObjects() as $name) {
			if (!isset($properties[$name]))
				$properties[$name] = null;
		}

		ob_start();
		printf('<h3>%s</h3>', get_class($this));
		echo $this->isModified() ? '(modified)' : '(not modified)', '<br />';
		foreach ($properties as $name => $value) {
			if (isset($this->sub_data_objects[$name]))
				$value = $this->sub_data_objects[$name];

			$modified = isset($modified_properties[$name]);

			if ($value instanceof SwatDBDataObject ||
				$value instanceof SwatDBRecordsetWrapper) {
				$modified = $value->isModified();
				$value = get_class($value);
			}

			if ($value === null)
				$value = '<null>';

			printf("%s = %s%s<br />\n",
				$name, $value, $modified ? ' (modified)' : '');
		}
		/*
		$reflector = new ReflectionClass(get_class($this));
		foreach ($reflector->getMethods() as $method) {
			if ($method->isProtected()) {
				$name = $method->getName();
				if (substr($name, 0, 4) === 'load')
					echo $name;
			}
		}
		*/
		$string = ob_get_clean();


		// set db back again
		$this->db = $db;

		return $string;
	}

	// }}}
	// {{{ public function getInternalValue()

	public function getInternalValue($name)
	{
		if (array_key_exists($name, $this->internal_properties))
			return $this->internal_properties[$name];
		else
			return null;
	}

	// }}}
	// {{{ public function hasInternalValue()

	public function hasInternalValue($name)
	{
		return array_key_exists($name, $this->internal_properties);
	}

	// }}}
	// {{{ protected function setInternalValue()

	protected function setInternalValue($name, $value)
	{
		if (array_key_exists($name, $this->internal_properties))
			$this->internal_properties[$name] = $value;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
	}

	// }}}
	// {{{ protected function registerDateProperty()

	protected function registerDateProperty($name)
	{
		$this->date_properties[] = $name;
	}

	// }}}
	// {{{ protected function registerInternalProperty()

	protected function registerInternalProperty($name, $class = null,
		$autosave = false)
	{
		$this->internal_properties[$name] = null;
		$this->internal_property_autosave[$name] = $autosave;

		if ($class === null)
			unset($this->internal_property_classes[$name]);
		else
			$this->internal_property_classes[$name] = $class;
	}

	// }}}
	// {{{ protected function initFromRow()

	/**
	 * Takes a data row and sets the properties of this object according to
	 * the values of the row
	 *
	 * Subclasses can override this method to provide additional
	 * functionality.
	 *
	 * @param mixed $row the row to use as either an array or object.
	 */
	protected function initFromRow($row)
	{
		if ($row === null)
			throw new SwatDBException(
				'Attempting to initialize dataobject with a null row.');

		$property_array = $this->getPublicProperties();

		if (is_object($row))
			$row = get_object_vars($row);

		foreach ($property_array as $name => $value) {
			if (isset($row[$name])) {
				if (in_array($name, $this->date_properties) && $row[$name] !== null)
					$this->$name = new SwatDate($row[$name]);
				else
					$this->$name = $row[$name];
			}
		}

		foreach ($this->internal_properties as $name => $value) {
			if (isset($row[$name]))
				$this->internal_properties[$name] = $row[$name];
		}
	}

	// }}}
	// {{{ protected function generatePropertyHashes()

	/**
	 * Generates the set of md5 hashes for this data object
	 *
	 * The md5 hashes represent all the public properties of this object and
	 * are used to tell if a property has been modified.
	 */
	protected function generatePropertyHashes()
	{
		$property_array = $this->getProperties();

		foreach ($property_array as $name => $value) {
			$hashed_value = md5(serialize($value));
			$this->property_hashes[$name] = $hashed_value;
		}
	}

	// }}}
	// {{{ protected function getId()

	protected function getId()
	{
		if ($this->id_field === null)
			throw new SwatDBException(
				sprintf('Property $id_field is not set for class %s.',
				get_class($this)));

		$id_field = new SwatDBField($this->id_field, 'integer');
		$temp = $id_field->name;
		return $this->$temp;
	}

	// }}}
	// {{{ private function getPublicProperties()

	/**
	 * Gets the public properties of this data-object
	 *
	 * Public properties should correspond directly to database fields.
	 *
	 * @return array a reference to an associative array of public properties
	 *                of this data-object. The array is of the form
	 *                'property name' => 'property value'.
	 */
	private function &getPublicProperties()
	{
		$public_properties = array();
		$reflector = new ReflectionClass(get_class($this));
		foreach ($reflector->getProperties() as $property)
			if ($property->isPublic() && !$property->isStatic())
				$public_properties[$property->getName()] =
					$property->getValue($this);

		return $public_properties;
	}

	// }}}
	// {{{ private function getProperties()

	/**
	 * Gets all the modifyable properties of this data-object
	 *
	 * This includes the public properties that correspond to database fields
	 * and the internal values that also correspond to database fields.
	 *
	 * @return array a reference to an associative array of properties of this
	 *                data-object. The array is of the form
	 *                'property name' => 'property value'.
	 */
	private function &getProperties()
	{
		$property_array = &$this->getPublicProperties();
		$property_array = &array_merge($property_array,
			$this->internal_properties);

		return $property_array;
	}

	// }}}
	// {{{ private function __get()

	private function __get($key)
	{
		if (isset($this->sub_data_objects[$key]))
			return $this->sub_data_objects[$key];

		$loader_method = $this->getLoaderMethod($key);

		if (method_exists($this, $loader_method)) {
			$this->checkDB();
			$this->sub_data_objects[$key] =
				call_user_func(array($this, $loader_method));

			return $this->sub_data_objects[$key];

		} elseif ($this->hasInternalValue($key)) {
			$id = $this->getInternalValue($key);

			if ($id === null)
				return null;

			if (array_key_exists($key, $this->internal_property_classes)) {
				$class = $this->internal_property_classes[$key];

				if (class_exists($class)) {
					$object = new $class();
					$object->setDatabase($this->db);
					$object->load($id);
					$this->sub_data_objects[$key] = $object;
					return $object;
				} else {
					throw new SwatClassNotFoundException(sprintf("Class '%s' ".
						"registered for internal property '%s' does not ".
						'exist.',
						$class, $key), 0, $class);
				}
			}
		}

		throw new SwatDBException(sprintf("A property named '%s' does not ".
			'exist on the %s data-object. If the property corresponds '.
			'directly to a database field it should be added as a public '.
			'property of this data object. If the property should access a '.
			'sub-data-object, either specify a class when registering the '.
			"internal property named '%s' or define a custom loader method ".
			"named '%s()'.",
			$key, get_class($this), $key, $loader_method));
	}

	// }}}
	// {{{ private function __set()

	private function __set($key, $value)
	{
		if (method_exists($this, $this->getLoaderMethod($key))) {
			$this->sub_data_objects[$key] = $value;
		} elseif ($this->hasInternalValue($key)) {
			if (is_object($value)) {
				$this->sub_data_objects[$key] = $value;
				$this->setInternalValue($key, $value->getId());
			} else {
				$this->setInternalValue($key, $value);
			}
		} else {
			throw new SwatDBException(
				"A property named '$key' does not exist on this ".
				'dataobject.  If the property corresponds directly to '.
				'a database field it should be added as a public property '.
				'of this data object.  If the property should access a '.
				'sub-dataobject, specify a class when registering the '.
				"internal field named '$key'.");
		}
	}

	// }}}
	// {{{ private function __isset()

	private function __isset($key)
	{
		return
			isset($this->sub_data_objects[$key]) ||
			method_exists($this, $this->getLoaderMethod($key)) ||
			$this->hasInternalValue($key);
	}

	// }}}
	// {{{ private function getLoaderMethod()

	private function getLoaderMethod($key)
	{
		$loader_method = 'load'.str_replace(' ', '',
			ucwords(str_replace('_', ' ', $key)));

		return $loader_method;
	}

	// }}}

	// database loading and saving
	// {{{ public function setDatabase()

	/**
	 * @param MDB2 $db
	 */
	public function setDatabase($db)
	{
		$this->db = $db;
		$serializable_sub_data_objects = $this->getSerializableSubDataObjects();

		foreach ($this->sub_data_objects as $name => $object)
			if ($object instanceof SwatDBDataObject ||
				$object instanceof SwatDBRecordsetWrapper)
					if (in_array($name, $serializable_sub_data_objects))
						$object->setDatabase($db);
	}

	// }}}
	// {{{ public function load()

	/**
	 * Loads this object's properties from the database given an id
	 *
	 * @param mixed $id the id of the database row to set this object's
	 *               properties with.
	 *
	 * @return boolean whether data was sucessfully loaded.
	 */
	public function load($id)
	{
		$this->checkDB();
		$row = $this->loadInternal($id);

		if ($row === null)
			return false;

		$this->initFromRow($row);
		$this->generatePropertyHashes();
		return true;
	}

	// }}}
	// {{{ public function save()

	/**
	 * Saves this object to the database
	 *
	 * Only modified properties are updated.
	 */
	public function save()
	{
		$this->checkDB();

		$transaction = new SwatDBTransaction($this->db);
		try {
			foreach ($this->internal_property_autosave as $name => $autosave) {
				if ($autosave && isset($this->sub_data_objects[$name])) {
					$object = $this->sub_data_objects[$name];
					$object->save();
					$this->setInternalValue($name, $object->getId());
				}
			}

			$this->saveInternal();

			foreach ($this->sub_data_objects as $name => $object) {
				$saver_method = 'save'.
					str_replace(' ', '', ucwords(strtr($name, '_', ' ')));

				if (method_exists($this, $saver_method))
					call_user_func(array($this, $saver_method));
			}
		} catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}
		$transaction->commit();

		$this->generatePropertyHashes();
	}

	// }}}
	// {{{ public function delete()

	/**
	 * Deletes this object from the database
	 */
	public function delete()
	{
		$this->checkDB();
		$this->deleteInternal();
	}

	// }}}
	// {{{ protected function checkDB()

	protected function checkDB()
	{
		if ($this->db === null)
			throw new SwatDBException(
				sprintf('No database available to this dataobject (%s). '.
					'Call the setDatabase method.', get_class($this)));
	}

	// }}}
	// {{{ protected function loadInternal()

	/**
	 * Loads this object's properties from the database given an id
	 *
	 * @param mixed $id the id of the database row to set this object's
	 *               properties with.
	 *
	 * @return object data row or null.
	 */
	protected function loadInternal($id)
	{
		if ($this->table !== null && $this->id_field !== null) {
			$id_field = new SwatDBField($this->id_field, 'integer');
			$sql = 'select * from %s where %s = %s';

			$sql = sprintf($sql,
				$this->table,
				$id_field->name,
				$this->db->quote($id, $id_field->type));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);

			return $row;
		}
		return null;
	}

	// }}}
	// {{{ protected function saveInternal()

	/**
	 * Saves this object to the database
	 *
	 * Only modified properties are updated.
	 */
	protected function saveInternal()
	{
		if ($this->table === null) {
			trigger_error(
				sprintf('No table defined for %s', get_class($this)),
				E_USER_NOTICE);

			return;
		}

		if ($this->id_field === null) {
			trigger_error(
				sprintf('No id_field defined for %s', get_class($this)),
				E_USER_NOTICE);

			return;
		}

		$id_field = new SwatDBField($this->id_field, 'integer');

		if (!property_exists($this, $id_field->name)) {
			trigger_error(
				sprintf("The id_field '%s' is not defined for %s",
					$id_field->name, get_class($this)),
				E_USER_NOTICE);

			return;
		}

		$modified_properties = $this->getModifiedProperties();

		if (count($modified_properties) == 0)
			return;

		$id_ref = $id_field->name;
		$id = $this->$id_ref;

		$fields = array();
		$values = array();

		foreach ($this->getModifiedProperties() as $name => $value) {
			$type = $this->guessType($name, $value);

			if ($type == 'date')
				$value = $value->getDate();

			$fields[] = sprintf('%s:%s', $type, $name);
			$values[$name] = $value;
		}

		if ($id === null) {
			$this->$id_ref = 
				SwatDB::insertRow($this->db, $this->table, $fields, $values,
					$id_field->__toString());
		} else {
			SwatDB::updateRow($this->db, $this->table, $fields, $values,
				$id_field->__toString(), $id);
		}
	}

	// }}}
	// {{{ protected function deleteInternal()

	/**
	 * Deletes this object from the database
	 */
	protected function deleteInternal()
	{
		if ($this->table === null || $this->id_field === null)
			return;

		$id_field = new SwatDBField($this->id_field, 'integer');

		if (!property_exists($this, $id_field->name))
			return;

		$id_ref = $id_field->name;
		$id = $this->$id_ref;

		if ($id !== null)
			SwatDB::deleteRow($this->db, $this->table,
				$id_field->__toString(), $id);
	}

	// }}}
	// {{{ protected function guessType()

	protected function guessType($name, $value)
	{
		switch (gettype($value)) {
		case 'boolean':
			return 'boolean';
		case 'integer':
			return 'integer';
		case 'float':
			return 'float';
		case 'object':
			if ($value instanceof SwatDate)
				return 'date';
		case 'string':
		default:
			return 'text';
		}
	}

	// }}}

	// serialization
	// {{{ public function serialize()

	public function serialize()
	{
		$data = array();

		$serializable_sub_data_objects = $this->getSerializableSubDataObjects();
		foreach ($this->sub_data_objects as $name => $object)
			if (!in_array($name, $serializable_sub_data_objects))
				unset($this->sub_data_objects[$name]);

		foreach ($this->getSerializablePrivateProperties() as $property)
			$data[$property] = &$this->$property;

		$reflector = new ReflectionObject($this);
		foreach ($reflector->getProperties() as $property) {
			if ($property->isPublic()) {
				$name = $property->getName();
				$data[$name] = &$this->$name;
			}
		}

		return serialize($data);
	}

	// }}}
	// {{{ public function unserialize()
	
	public function unserialize($data)
	{
		$this->wakeup();
		$this->init();

		$data = unserialize($data);

		foreach ($data as $property => $value)
			$this->$property = $value;
	}

	// }}}
	// {{{ protected function wakeup()

	protected function wakeup()
	{
	}

	// }}}
	// {{{ protected function getSerializableSubDataObjects()

	protected function getSerializableSubDataObjects()
	{
		return array();
	}

	// }}}
	// {{{ protected function getSerializablePrivateProperties()

	protected function getSerializablePrivateProperties()
	{
		return array('table', 'id_field',
			'sub_data_objects', 'property_hashes', 'internal_properties',
			'internal_property_classes', 'date_properties');
	}

	// }}}
}

?>