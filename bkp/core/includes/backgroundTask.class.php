<?php

/**
 * backgroundTask class for cron management
 */
class backgroundTask
{
	public $taskName = null;
	public $serverName = 'Unknown';
	public $taskId = null;
	public $taskLogId = null;
	
	public function __construct()
	{
		// get requesting task name
		$taskName = $this->_getCallingCronName();
		if(strlen($taskName) == 0)
		{
			$taskName = 'unknown';
		}
		$this->taskName = $taskName;
		$this->serverName = $this->_getCurrentServerName();
	}
	
    public function start()
    {
        // get database
        $db = Database::getDatabase();

		// update start time
		$sQL = 'INSERT INTO background_task (task, last_update, status) VALUES (:task, NOW(), "running") ON DUPLICATE KEY UPDATE last_update=NOW(), status="running"';
		$db->query($sQL, array('task' => $this->taskName));
		$this->taskId = (int)$db->insertId();
		
		// log record
		$sQL = 'INSERT INTO background_task_log (task_id, start_time, server_name, status) VALUES (:task_id, NOW(), :server_name, "started")';
		$db->query($sQL, array('task_id' => $this->taskId, 'server_name' => $this->serverName));
		$this->taskLogId = (int)$db->insertId();
    }
	
	public function end()
    {
        // get database
        $db = Database::getDatabase();

		// update end time
		$sQL = 'INSERT INTO background_task (task, last_update, status) VALUES (:task, NOW(), "finished") ON DUPLICATE KEY UPDATE last_update=NOW(), status="finished"';
		$db->query($sQL, array('task' => $this->taskName));
		
		// log record
		$sQL = 'UPDATE background_task_log SET task_id = :task_id, end_time = NOW(), status = "finished" WHERE id = :task_log_id';
		$db->query($sQL, array('task_id' => $this->taskId, 'task_log_id' => $this->taskLogId));
    }
	
	private function _getCallingCronName()
	{
		// figure out the name of the cron calling this method
		$callers = debug_backtrace();
		$filePath = $callers[1]['file'];
		$filename = basename($filePath);
		if(strlen($filename))
		{
			return $filename;
		}
		
		return false;
	}
	
	private function _getCurrentServerName()
	{
		$hostName = gethostname();
		if(strlen($hostName))
		{
			return $hostName;
		}
		
		$hostName = php_uname('n');
		if(strlen($hostName))
		{
			return $hostName;
		}
		
		return 'Unknown';
	}
}
