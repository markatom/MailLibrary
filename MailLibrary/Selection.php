<?php
/**
 * @author Tomáš Blatný
 */

namespace greeny\MailLibrary;

use ArrayAccess, Countable, Iterator;

class Selection implements ArrayAccess, Countable, Iterator {
	/** @var \greeny\MailLibrary\Connection */
	protected $connection;

	/** @var \greeny\MailLibrary\Mailbox */
	protected $mailbox;

	/** @var array */
	protected $mails = NULL;

	/** @var int */
	protected $iterator = NULL;

	/** @var array */
	protected $mailIndexes = NULL;

	/** @var array */
	protected $filters = array();

	public function __construct(Connection $connection, Mailbox $mailbox)
	{
		$this->connection = $connection;
		$this->mailbox = $mailbox;
	}

	/**
	 * Adds condition to selection
	 *
	 * @param string $key
	 * @param string $value
	 * @return $this
	 */
	public function where($key, $value = NULL)
	{
		$this->connection->getDriver()->checkFilter($key, $value);
		$this->filters[] = array('key' => $key, 'value' => $value);
		return $this;
	}

	/**
	 * Counts mails
	 *
	 * @return int
	 */
	public function countMails()
	{
		$this->mails !== NULL || $this->fetchMails();
		return count($this->mails);
	}

	/**
	 * Gets all mails filtered by conditions
	 *
	 * @return Mail[]
	 */
	public function fetchAll()
	{
		$this->mails !== NULL || $this->fetchMails();
		return $this->mails;
	}

	/**
	 * Fetches mail ids from server
	 */
	protected function fetchMails()
	{
		$this->connection->getDriver()->switchMailbox($this->mailbox->getName());
		$ids = $this->connection->getDriver()->getMailIds($this->filters);
		$i = 0;
		$this->mails = array();
		$this->iterator = 0;
		$this->mailIndexes = array();
		foreach($ids as $id) {
			$this->mails[$id] = new Mail($this->connection, $this->mailbox, $id);
			$this->mailIndexes[$i++] = $id;
		}
	}

	// INTERFACE ArrayAccess

	/**
	 * @param int $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		$this->mails !== NULL || $this->fetchMails();
		return isset($this->mails[$offset]);
	}

	/**
	 * @param int $offset
	 * @throws MailboxException
	 * @return Mail
	 */
	public function offsetGet($offset)
	{
		$this->mails !== NULL || $this->fetchMails();
		if(isset($this->mails[$offset])) {
			return $this->mails[$offset];
		} else {
			throw new MailboxException("There is no email with id '$offset'.");
		}
	}

	/**
	 * @param int   $offset
	 * @param mixed $value
	 * @throws MailboxException
	 */
	public function offsetSet($offset, $value)
	{
		throw new MailboxException("Cannot set a readonly mail.");
	}

	/**
	 * @param int $offset
	 * @throws MailboxException
	 */
	public function offsetUnset($offset)
	{
		throw new MailboxException("Cannot unset a readonly mail.");
	}

	// INTERFACE Countable

	/**
	 * @return int
	 */
	public function count()
	{
		return $this->countMails();
	}

	// INTERFACE Iterator

	/**
	 * @return Mail
	 */
	public function current()
	{
		return $this->mails[$this->mailIndexes[$this->iterator]];
	}

	public function next()
	{
		$this->iterator++;
	}

	/**
	 * @return int
	 */
	public function key()
	{
		return $this->mailIndexes[$this->iterator];
	}

	/**
	 * @return bool
	 */
	public function valid()
	{
		return isset($this->mailIndexes[$this->iterator]);
	}

	public function rewind()
	{
		$this->mails !== NULL || $this->fetchMails();
		$this->iterator = 0;
	}
}
 