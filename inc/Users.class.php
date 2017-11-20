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

	public function getById($theUserId) {
		$aStmt = $this->mDb->getPDO()->prepare('SELECT * FROM users WHERE id = :id');
		$aStmt->bindParam(':id', $theUserId);
        $aStmt->execute();

        $aUser = $aStmt->fetch(\PDO::FETCH_ASSOC);

		return $aUser;
	}

	public function getByLogin($theUserLogin) {
		$aStmt = $this->mDb->getPDO()->prepare('SELECT * FROM users WHERE login = :login');
		$aStmt->bindParam(':login', $theUserLogin);
        $aStmt->execute();

        $aUser = $aStmt->fetch(\PDO::FETCH_ASSOC);

		return $aUser;
	}

	public function create($theName, $theEmail, $theLogin, $thePassword) {
		$aSql = "INSERT INTO users (name, email, login, password) VALUES (:name, :email, :login, :password)";
		$aStmt = $this->mDb->getPDO()->prepare($aSql);

		$aStmt->bindParam(':name', $theName);
		$aStmt->bindParam(':email', $theEmail);
		$aStmt->bindParam(':login', $theLogin);
		$aStmt->bindParam(':password', $thePassword);

		$aOk = $aStmt->execute();
		return $aOk;
	}

	public function findAll($theSimplified = true) {
		$aStmt = $this->mDb->getPDO()->prepare('SELECT '.($theSimplified ? 'id,name,login,email' : '*').' FROM users WHERE id > 0');
        $aStmt->execute();
		$aUsers = array();

        while($aRow = $aStmt->fetch(\PDO::FETCH_ASSOC)) {
            $aUsers[$aRow['id']] = $aRow;
        }

		return $aUsers;
	}

	public function update($theUserId, $theKeyValuePairs) {
		$aOk = $this->mDb->update('users', $theKeyValuePairs, array('id' => $theUserId));
		return $aOk;
	}
}

?>
