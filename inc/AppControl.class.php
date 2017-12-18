<?php

namespace Besearcher;

/**
 * Handle external commands aimed at controlling Besearcher, e.g. re-run an specific result/task.
 * Such external commands are usually issued by the command line tool "cmd/bc".
 */
class AppControl {
	const CMD_RERUN_RESULT = 1;

	private $mDb;
	private $mLog;

	public function __construct(Db $theDb, Log $theLog = null) {
		$this->mDb = $theDb;
		$this->mLog = $theLog;
	}

	public function update() {
		$this->handleEnquedCommands();
	}

	private function handleEnquedCommands() {
		$aCommands = $this->findEnquedCommands();

		if(count($aCommands) == 0) {
			return;
		}

		$this->mLog->debug('Processing app control commands');

		foreach($aCommands as $aCmdId => $aCmd) {
			$aOk = $this->runCommand($aCmd);

			if($aOk) {
				$this->mLog->debug('App command performed successfully! (cmd=' . $aCmd['cmd'] . ', params=' . $aCmd['params'] . ')');
			} else {
				$this->mLog->warn('Problem with app command (cmd=' . $aCmd['cmd'] . ', params=' . $aCmd['params'] . ')');
			}

			$aDeleted = $this->deleteEnqueuedCommand($aCmdId);

			if(!$aDeleted) {
				$this->mLog->error('Unable to delete app command with id=' . $aCmdId);
			}
		}
	}

	private function runCommand($theCmd) {
		$this->mLog->info('Running app command (cmd=' . $theCmd['cmd'] . ', params=' . $theCmd['params'] . ')');

		$aOk = false;
		$aParams = @unserialize($theCmd['params']);

		if($aParams === false) {
			$this->mLog->error('Unable to unserialize app command (cmd=' . $theCmd['cmd'] . ', params=' . $theCmd['params'] . ')');
			return;
		}

		switch($theCmd['cmd']) {
			case CMD_RERUN_RESULT: $aOk = cmdRerunResult(); break;
		}

		return $aOk;
	}

	private function cmdRerunResult(array $theParams) {
		// TODO: implement method
		$this->mLog->debug('cmdRerunResult()' . print_r($theParams, true));
		return true;
	}

	private function deleteEnqueuedCommand($theCmdId) {
		$aStmt = $this->mDb->getPDO()->prepare("DELETE FROM control WHERE id = :id");
		$aStmt->bindParam(':id', $theCmdId);
	    $aOk = $aStmt->execute();

		return $aOk;
	}

	private function findEnquedCommands() {
		$aStmt = $this->mDb->getPDO()->prepare("SELECT * FROM control");
		$this->mDb->execute($aStmt);

		$aCmds = array();

		while($aCmd = $aStmt->fetch(\PDO::FETCH_ASSOC)) {
			$aCmds[$aCmd['id']] = $aCmd;
		}

		return $aCmds;
	}
}

?>
