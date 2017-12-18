<?php

namespace Besearcher;

/**
 * Handle external commands aimed at controlling Besearcher, e.g. re-run an specific result/task.
 * Such external commands are usually issued by the command line tool "cmd/bc".
 */
class AppControl {
	const CMD_RERUN_RESULT = 1;

	private $mApp;

	private function commandConstantToString($theCmdConstant) {
		$aNames = array(
			AppControl::CMD_RERUN_RESULT => 'CMD_RERUN_RESULT'
		);

		$aName = isset($aNames[$theCmdConstant]) ? $aNames[$theCmdConstant] : '***UNKNOWN_APP_CMD***';
		return $aName;
	}

	public function __construct(App $theApp) {
		$this->mApp = $theApp;
	}

	public function enqueue($theCommand, array $theParams = array()) {
		$aStmt = $this->mApp->getDb()->getPDO()->prepare("INSERT INTO control (cmd, params) VALUES (:cmd, :params)");

		$aSerializedParams = serialize($theParams);

		$aStmt->bindParam(':cmd', $theCommand);
		$aStmt->bindParam(':params', $aSerializedParams);
		$aOk = $aStmt->execute();

		return $aOk;
	}

	public function update() {
		$this->handleEnquedCommands();
	}

	private function handleEnquedCommands() {
		$aCommands = $this->findEnquedCommands();

		if(count($aCommands) == 0) {
			return;
		}

		$this->mApp->getLogger()->debug('About to process app control commands.');

		foreach($aCommands as $aCmdId => $aCmd) {
			$aOk = $this->runCommand($aCmd);
			$aCmdDebugInfo = '(id=' . $aCmdId . ', cmd=' . $this->commandConstantToString($aCmd['cmd']) . ')';

			if($aOk) {
				$this->mApp->getLogger()->debug('Successfully performed app command! ' . $aCmdDebugInfo);
			} else {
				$this->mApp->getLogger()->warn('Problem with app command! ' . $aCmdDebugInfo);
			}

			$aDeleted = $this->deleteEnqueuedCommand($aCmdId);

			if(!$aDeleted) {
				$this->mApp->getLogger()->error('Unable to delete app command ' . $aCmdDebugInfo);
			}
		}
	}

	private function runCommand($theCmd) {
		$aOk = false;
		$aParams = @unserialize($theCmd['params']);

		if($aParams === false) {
			$this->mApp->getLogger()->error('Unable to unserialize app command (id=' . $theCmd['id'] . ')');
			return;
		}

		$aCmdDebugInfo = '(id=' . $theCmd['id'] . ', cmd=' . $this->commandConstantToString($theCmd['cmd']) . ', params=' . print_r($aParams, true) . ')';
		$this->mApp->getLogger()->debug('Running app command ' . $aCmdDebugInfo);

		switch($theCmd['cmd']) {
			case AppControl::CMD_RERUN_RESULT: $aOk = $this->cmdRerunResult($aParams); break;
		}

		return $aOk;
	}

	private function cmdRerunResult(array $theParams) {
		// TODO: implement method
		$this->mApp->getLogger()->debug('cmdRerunResult('.print_r($theParams, true).')');
		return true;
	}

	private function deleteEnqueuedCommand($theCmdId) {
		$aStmt = $this->mApp->getDb()->getPDO()->prepare("DELETE FROM control WHERE id = :id");
		$aStmt->bindParam(':id', $theCmdId);
	    $aOk = $aStmt->execute();

		return $aOk;
	}

	private function findEnquedCommands() {
		$aStmt = $this->mApp->getDb()->getPDO()->prepare("SELECT * FROM control");
		$this->mApp->getDb()->execute($aStmt);

		$aCmds = array();

		while($aCmd = $aStmt->fetch(\PDO::FETCH_ASSOC)) {
			$aCmds[$aCmd['id']] = $aCmd;
		}

		return $aCmds;
	}
}

?>
