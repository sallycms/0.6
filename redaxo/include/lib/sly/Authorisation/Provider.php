<?php

interface sly_Authorisation_Provider {

	public function hasPermission($userId, $operation, $permission, $objectId = null);

}