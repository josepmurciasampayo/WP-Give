<?php

namespace Give\DonorProfiles\Tabs\ProfileTab;

use Give\DonorProfiles\Tabs\Contracts\Tab as TabAbstract;
use Give\DonorProfiles\Tabs\ProfileTab\Route as ProfileRoute;

class Tab extends TabAbstract {

	public static function id() {
		return 'profile';
	}

	public function routes() {
		return [
			ProfileRoute::class,
		];
	}
}
