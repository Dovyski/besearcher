<?php

namespace Besearcher;

/**
 * Control things related to users in the web dashboard.
 */
class Users {
	private $mDb;

	public function __construct(Db $theDb) {
		if($theDb == null) {
			throw new \Exception('A valid instance of DB class is required.');
		}
		$this->mDb = $theDb;
	}

	public function removeById($theUserId) {
	    $aStmt = $this->mDb->getPDO()->prepare("DELETE FROM users WHERE id = :id");
		$aStmt->bindParam(':id', $theUserId);
	    $aOk = $aStmt->execute();

		return $aOk;
	}

	public function create($theName, $theLogin, $thePassword) {
		$aSql =
		"INSERT INTO
			tasks (
				cmd,
				log_file,
				working_dir,
				experiment_hash,
				permutation_hash,
				params,
				creation_time,
				priority
			)
		VALUES (
			:cmd,
			:log_file,
			:working_dir,
			:experiment_hash,
			:permutation_hash,
			:params,
			:creation_time,
			:priority
		)";

		$aStmt = $this->mDb->getPDO()->prepare($aSql);
		$aNow = time();
		$aPriority = 10;

		$aStmt->bindParam(':cmd', $theTask['cmd']);
		$aStmt->bindParam(':log_file', $theTask['log_file']);
		$aStmt->bindParam(':working_dir', $theTask['working_dir']);
		$aStmt->bindParam(':experiment_hash', $theTask['experiment_hash']);
		$aStmt->bindParam(':permutation_hash', $theTask['permutation_hash']);
		$aStmt->bindParam(':params', $theTask['params']);
		$aStmt->bindParam(':creation_time', $aNow);
		$aStmt->bindParam(':priority', $aPriority);

		$aOk = $aStmt->execute();
		return $aOk;
	}

	public function findAll($theSimplified = true) {
		$aStmt = $this->mDb->getPDO()->prepare('SELECT '.($theSimplified ? 'name,login' : '*').' FROM users WHERE id > 0');
        $aStmt->execute();
		$aUsers = array();

        while($aRow = $aStmt->fetch(\PDO::FETCH_ASSOC)) {
            $aUsers[$aRow['id']] = $aRow;
        }

		return $aUsers;
	}

	public function createAnalytics($theMetric, $theMin, $theMax) {
		$aNow = time();
		$aStmt = $this->mDb->getPDO()->prepare("INSERT INTO analytics (metric, min, max, last_update) VALUES (:metric, :min, :max, :last_update) ");
		$aStmt->bindParam(':metric', $theMetric);
		$aStmt->bindParam(':min', $theMin);
		$aStmt->bindParam(':max', $theMax);
		$aStmt->bindParam(':last_update', $aNow);
		$aStmt->execute();
	}

	public function update($theUserId, $theKeyValuePairs) {
		$aOk = $this->mDb->update('users', $theKeyValuePairs, array('id' => $theUserId));
		return $aOk;
	}
}

?>
