<?php

namespace Besearcher;

class Log {
	const ERROR = 3;
	const WARN = 2;
	const INFO = 1;
	const DEBUG = 0;

	private $mSilent;
	private $mStream;
	private $mLevel;
	private $mStrings = array(
	    Log::ERROR => 'ERROR',
	    Log::WARN => 'WARN',
	    Log::INFO => 'INFO',
	    Log::DEBUG => 'DEBUG'
	);

	public function __construct($thePathLogFile, $theSilent = false) {
		$this->mSilent = $theSilent;
		$this->mStream = empty($thePathLogFile) ? STDOUT : fopen($thePathLogFile, 'a');
		$this->setLevel(Log::DEBUG);
	}

	public function shutdown() {
		if($this->mStream != null) {
		    fclose($this->mStream);
		}
	}

	public function setLevel($theValue) {
		$this->mLevel = $theValue;
	}

	public function error($theMessage) {
		$this->say($theMessage, Log::ERROR);
	}

	public function warn($theMessage) {
		$this->say($theMessage, Log::WARN);
	}

	public function info($theMessage) {
		$this->say($theMessage, Log::INFO);
	}

	public function debug($theMessage) {
		$this->say($theMessage, Log::DEBUG);
	}

	public function say($theMessage, $theType) {
		if($this->mSilent) {
			return;
		}

	    $aLabel = isset($this->mStrings[$theType]) ? $this->mStrings[$theType] : 'UNKNOWN';
	    $aMessage = date('[Y-m-d H:i:s]') . ' [' . $aLabel . '] ' . $theMessage . "\n";

	    if($theType >= $this->mLevel) {
	        fwrite($this->mStream == null ? STDOUT : $this->mStream, $aMessage);
	    }
	}
}

?>
