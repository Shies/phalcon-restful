<?php

namespace Models;
use Engine\AbstractModel;

class RuntimeError extends AbstractModel {

	public function initialize() {
		$this->setSource("runtimeError");
	}
}
